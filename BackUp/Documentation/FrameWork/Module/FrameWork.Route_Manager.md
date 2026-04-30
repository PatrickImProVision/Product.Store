# FrameWork.RouteManager

## Module Specification
Route Manager Description:

- Core Purpose: Register and maintain all routes contributed by core app and modules.
- Main Responsibilities:
  - Merge route definitions from enabled modules.
  - Apply route groups, prefixes, and versioning (`/api/v1`).
  - Bind filters/middleware per route and per module.
  - Detect route conflicts and enforce priority.
  - Own and manage web-server rewrite prerequisites (`.htaccess`) so requests reach `public/index.php`.
  - Define and enforce canonical URI/routing contracts used by all modules.
  - Control built-in routing behavior (`Config\Routing`, `Config\Routes`) with module-aware policy.
- Core Components:
  - `RouteAggregator`
  - `RouteConflictResolver`
  - `RouteManifestProvider` (per module route manifest)
- Route Conventions:
  - Canonical extension: `.app` for routed endpoints.
  - Highest level (module control routes):
    - `ControlPanel/{Module_Id}/Index.app`
    - Used when route controls module configuration/policy/lifecycle.
  - Middle level (application control by module, moderation/staff scope):
    - `ModPanel/{Module_Id}/Index.app`
    - Used when module controls application content/users in moderation scope.
  - Low level (self-service/user scope):
    - `UserPanel/{Module_Id}/Index.app`
    - Used when module controls user-owned/self-service behavior.
  - Public level (open/public content):
    - `Index.app` and module-defined public `.app` routes (for example: `PublicPage/View.app?Id={PublicPage_Id}`).
  - Route names must remain module-scoped for uniqueness and conflict prevention.
- Public Interfaces:
  - CLI (localhost/dev): `php spark routes`, `routes:check-conflicts`
  - Web/Admin (external host fallback): `ControlPanel/RouteManager/Index.app` for route map and conflict diagnostics
  - Service methods: `loadModuleRoutes()`, `registerGroup()`, `assertNoConflicts()`
- Rewrite Rules Scope:
  - Route Manager is authoritative for `.htaccess` rewrite policy in Apache-based deployments.
  - Validate required `.htaccess` rewrite rules for Apache-based deployments.
  - Document equivalent rewrite rules for Nginx/IIS when `.htaccess` is not used.
  - Block route activation if rewrite prerequisites are missing in strict mode.
- Security Rules:
  - Enforce auth filters for protected route groups.
  - Block unresolved dynamic route placeholders.
  - Disable legacy auto-routing for production unless explicitly required.

## Inherited Common Attributes (Route Manager)
- General Standards (shared baseline for Route Manager) are inherited from `FrameWork.md`:
  - `Common Layer Standards (L1-L5)`
  - `Common Security Standards (Shared Attributes)`
- Correct module identity for this inherited section: `Route Manager`.
- Full Route L1-L5 General Standards (inherited common attributes):
  - L1 Core Identity:
    - Define route keys, route state keys, and route event keys.
    - Use stable route naming and URI contracts for cross-module consistency.
  - L2 Security Controls:
    - Define route permission keys, role defaults, and deny-by-default behavior.
    - Require explicit filter/middleware mapping for protected routes.
  - L3 Data Integrity:
    - Define schema contracts for route manifests/conflicts/audit if persisted.
    - Define index/constraint rules and uniqueness requirements for route keys.
  - L4 Behavior and Processing:
    - Define deterministic route merge order, conflict resolution, and rewrite validation flow.
    - Define canonical extension/URI normalization policy and failure handling.
  - L5 Operations and Runtime Controls:
    - Define environment-specific route behavior, cache/diagnostics controls, and strict-mode toggles.
    - Define required config keys and operational guardrails for production.
- Layer progression requirement:
  - L1 MUST be complete before L2.
  - L2 MUST be complete before L3.
  - L3 MUST be complete before L4.
  - L4 MUST be complete before L5.
  - Later layers MUST NOT break earlier-layer contracts.
- Scope rule:
  - Shared baseline rules stay in `FrameWork.md`.
  - This file defines only Route-specific overrides, integrations, and contracts.

## Route Ownership Boundary Note (Route Manager)
- Route Manager owns global routing governance:
  - route merge/loading order
  - route conflict detection/resolution
  - rewrite/`.htaccess` policy and validation
  - canonical extension and URI normalization rules
  - global route diagnostics and strict-mode checks
- Feature modules (User Manager, E-Mail Manager, Module Manager, Content Manager - Community, etc.) own their domain endpoint contracts and route maps.
- Route Manager validates and orchestrates those module routes but does not replace module business-flow route ownership.

## Route Baseline Data Artifact (Canonical)
- Ownership: Route Manager
- Canonical file: `BackUp/FrameWork/Module/Route_Manager/Route.BasicData.json`
- Purpose: machine-readable baseline route dataset aggregated from module contracts.
- Rule: route driver/UI must read and update this artifact under Route Manager governance.
- Scope: supports `UserPanel/*`, `ModPanel/*`, `ControlPanel/*`, and explicit public `.app` routes only.

## Dual Driver Contract (Route Manager)
- Driver 1: `RouteMapDriver` (system mapping/compile driver)
  - Responsibility: aggregate canonical routes from module contracts and enabled-module state.
  - Input: `BackUp/FrameWork/Module/Route_Manager/Route.BasicData.json` + module enablement/policy context.
  - Output: normalized route manifest for runtime registration (no UI concerns).
  - Rules: enforce `.app` contracts, panel prefix policy, conflict detection, and permission mapping validation.
- Driver 2: `RouteMapEditorDriver` (UI edit/view driver)
  - Responsibility: show route map, apply controlled edits, and stage changes for validation.
  - Input: current route manifest + editor payload from `ControlPanel/RouteManager/*`.
  - Output: approved changes written back to Route Manager artifact/store with audit record.
  - Rules: deny-by-default for unmapped permissions, block unknown wildcard routes, require confirmation for mutations.
- Separation Rule:
  - `RouteMapDriver` MUST remain deterministic and non-interactive.
  - `RouteMapEditorDriver` MUST not bypass validation rules owned by `RouteMapDriver`.
  - Final publish flow: `Edit -> Validate(RouteMapDriver) -> Persist -> Audit`.

## CI Integration Contract (Dual Driver)
- `RouteMapDriver` CI file integration:
  - Read: `app/Config/Routing.php`, `app/Config/Routes.php`, `app/Config/Filters.php`.
  - Read: `BackUp/FrameWork/Module/Route_Manager/Route.BasicData.json`.
  - Write target (publish): controlled route registration in `app/Config/Routes.php` (or approved include chain).
- `RouteMapEditorDriver` CI file integration:
  - Read: effective runtime map from `app/Config/Routes.php` and Route Manager artifacts.
  - Write: staged route edits to Route Manager artifacts only; never direct raw writes to runtime routes.
  - Publish: must call `RouteMapDriver` validation before any CI route publish step.
- Publish Validation Gates (required):
  - Gate 1: route syntax/placeholder validation (`*_Id` contracts, `.app` extension policy).
  - Gate 2: conflict and duplicate detection (method + pattern uniqueness).
  - Gate 3: permission/filter mapping completeness for protected routes.
  - Gate 4: environment policy checks (`development/testing/production`) and safe-mode restrictions.
  - Gate 5: audit log write before and after publish (`route_audit`).
- Safety rule: Route Manager integration MUST NOT modify `system/*`; CI integration is limited to `app/Config/*`, route artifacts, and approved runtime registration paths.

## Runtime Access Decision Flow (Canonical)
- Step 1 (Editor): `Administrator` (or `Owner`) updates route through `RouteMapEditorDriver`.
- Step 2 (Persist): route record is saved in Route Manager storage (`route_manifests`/artifact) with audit log.
- Step 3 (Validate): `RouteMapDriver` validates syntax, existence, uniqueness, permission map, and environment policy.
- Step 4 (Publish): only validated routes are published to CI runtime registration.
- Step 5 (Request-time decision): when user requests a link, Route Manager evaluates:
  - Route existence (registered and enabled).
  - Required permission mapping exists.
  - Request actor role is allowed and active.
- Step 6 (Decision):
  - Confirm/Allow: route exists + permission mapped + role allowed.
  - Refuse/Deny: route missing/disabled, permission missing, or role not allowed/inactive.
- Required response behavior:
  - Missing route: `404 Not Found`.
  - Unauthenticated: `401 Unauthorized`.
  - Authenticated but role/permission denied: `403 Forbidden`.
  - Validation/policy conflict: `409 Conflict` or `422 Unprocessable Entity` as defined by contract.

## Route Manager Standard Database Design (Future Routing Data)
- Purpose:
  - Provide persistent routing intelligence for diagnostics, conflict management, policy history, and environment checks.
- Tables:
  - `route_manifests` (required):
    - Purpose: snapshot of merged route map by module/version/environment.
    - Columns:
      - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
      - `manifest_uuid` (CHAR 36, NOT NULL, UNIQUE)
      - `environment` (ENUM `development`,`testing`,`production` NOT NULL)
      - `module_id` (BIGINT UNSIGNED NOT NULL, FK -> `modules.id`)
      - `module_version` (VARCHAR 32 NOT NULL)
      - `route_count` (INT UNSIGNED NOT NULL DEFAULT 0)
      - `manifest_json` (JSON NOT NULL)
      - `manifest_hash` (CHAR 64 NOT NULL)
      - `is_active` (TINYINT(1) NOT NULL DEFAULT 1)
      - `created_by` (BIGINT UNSIGNED NULL, FK -> `users.id`)
      - `created_at` (DATETIME NOT NULL)
    - Indexes/Constraints:
      - Unique (`manifest_uuid`)
      - Index (`environment`,`module_id`,`is_active`)
      - Index (`created_at`)
  - `route_conflicts` (required):
    - Purpose: store detected route collisions and resolution lifecycle.
    - Columns:
      - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
      - `conflict_uuid` (CHAR 36, NOT NULL, UNIQUE)
      - `environment` (ENUM `development`,`testing`,`production` NOT NULL)
      - `route_pattern` (VARCHAR 255 NOT NULL)
      - `http_method` (VARCHAR 16 NOT NULL)
      - `module_a_id` (BIGINT UNSIGNED NOT NULL, FK -> `modules.id`)
      - `module_b_id` (BIGINT UNSIGNED NOT NULL, FK -> `modules.id`)
      - `resolution_status` (ENUM `open`,`resolved`,`ignored` NOT NULL DEFAULT `open`)
      - `resolution_strategy` (VARCHAR 80 NULL)
      - `resolved_by` (BIGINT UNSIGNED NULL, FK -> `users.id`)
      - `resolved_at` (DATETIME NULL)
      - `metadata_json` (JSON NULL)
      - `created_at` (DATETIME NOT NULL)
      - `updated_at` (DATETIME NOT NULL)
    - Indexes/Constraints:
      - Unique (`conflict_uuid`)
      - Index (`resolution_status`,`environment`)
      - Index (`route_pattern`,`http_method`)
  - `route_rewrite_checks` (required):
    - Purpose: persist `.htaccess`/rewrite validation results by environment.
    - Columns:
      - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
      - `check_uuid` (CHAR 36, NOT NULL, UNIQUE)
      - `environment` (ENUM `development`,`testing`,`production` NOT NULL)
      - `server_type` (ENUM `apache`,`nginx`,`iis`,`other` NOT NULL)
      - `is_valid` (TINYINT(1) NOT NULL DEFAULT 0)
      - `strict_mode` (TINYINT(1) NOT NULL DEFAULT 1)
      - `failure_count` (INT UNSIGNED NOT NULL DEFAULT 0)
      - `details_json` (JSON NULL)
      - `checked_by` (BIGINT UNSIGNED NULL, FK -> `users.id`)
      - `checked_at` (DATETIME NOT NULL)
    - Indexes/Constraints:
      - Unique (`check_uuid`)
      - Index (`environment`,`server_type`,`checked_at`)
      - Index (`is_valid`,`strict_mode`)
  - `route_audit` (required):
    - Purpose: immutable audit log for route policy, cache, and conflict actions.
    - Columns:
      - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
      - `event_uuid` (CHAR 36, NOT NULL, UNIQUE)
      - `event_key` (VARCHAR 120 NOT NULL)
      - `actor_user_id` (BIGINT UNSIGNED NULL, FK -> `users.id`)
      - `request_id` (VARCHAR 64 NULL)
      - `target_ref` (VARCHAR 190 NULL)
      - `status` (VARCHAR 32 NOT NULL)
      - `reason_code` (VARCHAR 80 NULL)
      - `ip_address` (VARCHAR 45 NULL)
      - `user_agent` (VARCHAR 255 NULL)
      - `metadata_json` (JSON NULL)
      - `created_at` (DATETIME NOT NULL)
    - Indexes/Constraints:
      - Unique (`event_uuid`)
      - Index (`event_key`,`created_at`)
      - Index (`actor_user_id`,`created_at`)
- Integrity Rules:
  - `environment` values must use canonical set: `development`, `testing`, `production`.
  - `manifest_hash` and UUID fields must be immutable after insert.
  - Conflict rows can transition only `open -> resolved|ignored`.
  - Route audit rows are append-only (no destructive updates).

### Route Manager Logical Fields (Quick View)
| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Route.Manifest.Id` | `route_manifests.id` | PK, auto increment |
| `Route.Manifest.Uuid` | `route_manifests.manifest_uuid` | UUID, unique |
| `Route.Manifest.Environment` | `route_manifests.environment` | `development/testing/production` |
| `Route.Manifest.ModuleId` | `route_manifests.module_id` | FK -> `modules.id` |
| `Route.Manifest.ModuleVersion` | `route_manifests.module_version` | `VARCHAR(32)` |
| `Route.Manifest.RouteCount` | `route_manifests.route_count` | unsigned int |
| `Route.Manifest.Json` | `route_manifests.manifest_json` | JSON |
| `Route.Manifest.Hash` | `route_manifests.manifest_hash` | `CHAR(64)` |
| `Route.Manifest.Active` | `route_manifests.is_active` | bool |
| `Route.Manifest.CreatedBy` | `route_manifests.created_by` | FK -> `users.id`, nullable |
| `Route.Manifest.CreatedAt` | `route_manifests.created_at` | datetime |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Route.Conflict.Id` | `route_conflicts.id` | PK, auto increment |
| `Route.Conflict.Uuid` | `route_conflicts.conflict_uuid` | UUID, unique |
| `Route.Conflict.Environment` | `route_conflicts.environment` | `development/testing/production` |
| `Route.Conflict.Pattern` | `route_conflicts.route_pattern` | `VARCHAR(255)` |
| `Route.Conflict.Method` | `route_conflicts.http_method` | `VARCHAR(16)` |
| `Route.Conflict.ModuleAId` | `route_conflicts.module_a_id` | FK -> `modules.id` |
| `Route.Conflict.ModuleBId` | `route_conflicts.module_b_id` | FK -> `modules.id` |
| `Route.Conflict.Status` | `route_conflicts.resolution_status` | `open/resolved/ignored` |
| `Route.Conflict.Strategy` | `route_conflicts.resolution_strategy` | `VARCHAR(80)`, nullable |
| `Route.Conflict.ResolvedBy` | `route_conflicts.resolved_by` | FK -> `users.id`, nullable |
| `Route.Conflict.ResolvedAt` | `route_conflicts.resolved_at` | datetime, nullable |
| `Route.Conflict.Meta` | `route_conflicts.metadata_json` | JSON, nullable |
| `Route.Conflict.CreatedAt` | `route_conflicts.created_at` | datetime |
| `Route.Conflict.UpdatedAt` | `route_conflicts.updated_at` | datetime |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Route.RewriteCheck.Id` | `route_rewrite_checks.id` | PK, auto increment |
| `Route.RewriteCheck.Uuid` | `route_rewrite_checks.check_uuid` | UUID, unique |
| `Route.RewriteCheck.Environment` | `route_rewrite_checks.environment` | `development/testing/production` |
| `Route.RewriteCheck.ServerType` | `route_rewrite_checks.server_type` | `apache/nginx/iis/other` |
| `Route.RewriteCheck.Valid` | `route_rewrite_checks.is_valid` | bool |
| `Route.RewriteCheck.StrictMode` | `route_rewrite_checks.strict_mode` | bool |
| `Route.RewriteCheck.FailureCount` | `route_rewrite_checks.failure_count` | unsigned int |
| `Route.RewriteCheck.Details` | `route_rewrite_checks.details_json` | JSON, nullable |
| `Route.RewriteCheck.CheckedBy` | `route_rewrite_checks.checked_by` | FK -> `users.id`, nullable |
| `Route.RewriteCheck.CheckedAt` | `route_rewrite_checks.checked_at` | datetime |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Route.Audit.Id` | `route_audit.id` | PK, auto increment |
| `Route.Audit.EventId` | `route_audit.event_uuid` | UUID, unique |
| `Route.Audit.EventKey` | `route_audit.event_key` | `VARCHAR(120)` |
| `Route.Audit.ActorUserId` | `route_audit.actor_user_id` | FK -> `users.id`, nullable |
| `Route.Audit.RequestId` | `route_audit.request_id` | `VARCHAR(64)`, nullable |
| `Route.Audit.TargetRef` | `route_audit.target_ref` | `VARCHAR(190)`, nullable |
| `Route.Audit.Status` | `route_audit.status` | `VARCHAR(32)` |
| `Route.Audit.Reason` | `route_audit.reason_code` | `VARCHAR(80)`, nullable |
| `Route.Audit.IpAddress` | `route_audit.ip_address` | `VARCHAR(45)`, nullable |
| `Route.Audit.UserAgent` | `route_audit.user_agent` | `VARCHAR(255)`, nullable |
| `Route.Audit.Meta` | `route_audit.metadata_json` | JSON, nullable |
| `Route.Audit.CreatedAt` | `route_audit.created_at` | datetime |

## Route-to-Permission Matrix (Route Manager)
| Route | Method | Action | Required Permission | Allowed Roles (Default) | Scope Rule |
|---|---|---|---|---|---|
| `ControlPanel/RouteManager/Index.app` | `GET` | View route dashboard | `route.view` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/RouteManager/Manifest.app` | `GET` | View merged route manifest | `route.manifest.view` | `Administrator`, `Owner`, `Moderator` (read-only) | Moderator read-only scope |
| `ControlPanel/RouteManager/Conflicts.app` | `GET` | View route conflicts | `route.conflict.view` | `Administrator`, `Owner`, `Moderator` (read-only) | Moderator read-only scope |
| `ControlPanel/RouteManager/Conflicts.app` | `POST` | Resolve route conflict | `route.conflict.manage` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/RouteManager/Rewrite.app` | `GET` | View rewrite/`.htaccess` diagnostics | `route.rewrite.view` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/RouteManager/Rewrite.app` | `POST` | Apply rewrite policy/validation mode | `route.rewrite.manage` | `Owner` (default), `Administrator` (if policy allows) | Full scope |
| `ControlPanel/RouteManager/Cache.app` | `POST` | Rebuild or clear route cache | `route.cache.manage` | `Administrator`, `Owner` | Full scope |

### Enforcement Rules (Route Manager)
- Deny by default when route is not mapped.
- Deny when actor role is inactive or required permission is missing.
- `Moderator` access in Route Manager is strictly read-only.
- Policy-changing actions require audit logging with actor, diff, and timestamp.
- Rewrite-changing actions require explicit confirmation and strict-mode pre-check.

## Response/Error Contract (Route Manager)
### Standard Success Schema
- `success` (bool, must be `true`)
- `code` (string, stable application code)
- `message` (human-readable summary)
- `data` (object)
- `meta` (object, optional)
- `timestamp` (ISO-8601 datetime)
- `request_id` (string)

### Standard Error Schema
- `success` (bool, must be `false`)
- `code` (string, stable application error code)
- `message` (human-readable summary)
- `errors` (object/array, field-level or detail errors)
- `status` (int, HTTP status)
- `timestamp` (ISO-8601 datetime)
- `request_id` (string)

### HTTP Status Mapping (Route Manager)
- `GET ControlPanel/RouteManager/Index.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `GET ControlPanel/RouteManager/Manifest.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `GET ControlPanel/RouteManager/Conflicts.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `POST ControlPanel/RouteManager/Conflicts.app`:
  - `200 OK`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`
- `GET ControlPanel/RouteManager/Rewrite.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `POST ControlPanel/RouteManager/Rewrite.app`:
  - `200 OK`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`
- `POST ControlPanel/RouteManager/Cache.app`:
  - `202 Accepted`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `500 Internal Server Error`

### Error Code Catalog (Route Minimum)
- `ROUTE_PERMISSION_DENIED`
- `ROUTE_MANIFEST_INVALID`
- `ROUTE_CONFLICT_DETECTED`
- `ROUTE_CONFLICT_RESOLUTION_FAILED`
- `ROUTE_REWRITE_INVALID`
- `ROUTE_REWRITE_MISSING_PREREQUISITE`
- `ROUTE_CACHE_OPERATION_FAILED`
- `ROUTE_VALIDATION_FAILED`
- `ROUTE_INTERNAL_ERROR`

### Examples (Future Reference)
#### Success Example
```json
{
  "success": true,
  "code": "ROUTE_CONFLICTS_LISTED",
  "message": "Route conflicts loaded.",
  "data": {
    "count": 2
  },
  "meta": {},
  "timestamp": "2026-02-23T12:00:00Z",
  "request_id": "req_route_001"
}
```

#### Error Example
```json
{
  "success": false,
  "code": "ROUTE_PERMISSION_DENIED",
  "message": "Permission denied for route action.",
  "errors": [
    {
      "field": "permission",
      "reason": "missing route.conflict.manage"
    }
  ],
  "status": 403,
  "timestamp": "2026-02-23T12:00:00Z",
  "request_id": "req_route_002"
}
```

#### Validation Error Example
```json
{
  "success": false,
  "code": "ROUTE_VALIDATION_FAILED",
  "message": "Rewrite mode value is invalid.",
  "errors": [
    {
      "field": "rewrite_mode",
      "reason": "allowed values: strict, warn, off"
    }
  ],
  "status": 422,
  "timestamp": "2026-02-23T12:00:00Z",
  "request_id": "req_route_003"
}
```

## Route Module Status
- Status: Complete
- Completed:
  - Inherited common standards with correct module naming
  - Route ownership boundary contract
  - Route-to-permission matrix by role
  - Response/error contract with HTTP mapping
  - Practical request/response examples
- Remaining:
  - None


