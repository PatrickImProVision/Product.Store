# FrameWork.ModuleManager

## Module Specification
Module Manager Description:

- Core Purpose: Discover, register, enable, disable, install, uninstall, and audit application modules.
- Main Responsibilities:
  - Scan module directories and parse manifests.
  - Validate module metadata, dependencies, compatibility, and integrity.
  - Manage module lifecycle states (`registered`, `installed`, `enabled`, `disabled`, `uninstalled`, `error`).
  - Coordinate module bootstrap for routes, filters, services, events, migrations, and seeders.
  - Control CI4 module discovery behavior (`Config\Modules`) through managed policy.
- Core Components:
  - `ModuleRegistry`
  - `ModuleRepository`
  - `ModuleLoader`
  - `ModuleLifecycleService`
  - `ModuleAuditService`
- Public Interfaces:
  - CLI (localhost/dev): `php spark module:list`, `module:install {Module_Id}`, `module:enable {Module_Id}`, `module:disable {Module_Id}`, `module:uninstall {Module_Id}`
  - Web/Admin (external host fallback): `ControlPanel/ModuleManager/Index.app` (complete module state/result view)
  - Service methods: `discover()`, `register()`, `install()`, `enable()`, `disable()`, `uninstall()`, `bootEnabledModules()`
- Security Rules:
  - Only `Owner` and `Administrator` may mutate module lifecycle state.
  - Module install/upgrade/uninstall actions must require explicit confirmation and audit logging.
  - Deny direct web execution of module internal files.
  - Deny module activation when dependency, compatibility, or integrity checks fail.


## AI Readable Module Acceptance Rules
```yaml
module_acceptance:
  authority: Module_Manager
  minimum_actor_roles: [Administrator, Owner]
  must_validate_before_install_or_enable:
    - manifest
    - compatibility
    - dependencies
    - integrity
    - permissions
  manifest_required_keys:
    - name
    - module_id
    - version
    - description
    - requires_ci_version
    - dependencies
    - entrypoints
  lifecycle:
    allowed_transitions:
      - registered->installed
      - installed->enabled
      - enabled->disabled
      - disabled->enabled
      - disabled->uninstalled
      - any->error
    blocked_conditions:
      - disable_or_uninstall_core_when_module.block_disable_core_true
      - enable_with_missing_or_incompatible_required_dependency
      - uninstall_with_enabled_dependents
  install_pipeline:
    - validate_manifest
    - validate_compatibility
    - validate_dependencies
    - run_migrations_seeders
    - register_routes_filters_services_events
    - persist_state_and_audit
  rollback_on_failure:
    enabled: true
    target_state: error
  traffic_light:
    green:
      - status_enabled
      - dependencies_resolved
      - compatibility_pass
      - integrity_verified
    yellow:
      - status_installed_or_disabled_without_hard_error
      - integrity_unknown
    red:
      - status_error
      - dependency_validation_failed
      - compatibility_failed
      - integrity_failed
  enforcement:
    deny_by_default_when_unmapped: true
    deny_when_role_inactive: true
    deny_when_permission_missing: true
    deny_mutations_in_safe_mode: true
    audit_required_for_mutations: true
```

## Lego Architecture Contract
```yaml
module_lego_contract:
  model: interchangeable_blocks_with_strict_connectors
  block_definition:
    module_package_must_include:
      - manifest
      - entrypoints
      - permission_map
      - optional_routes
      - optional_migrations
      - optional_seeders
  connector_definition:
    required_manifest_keys:
      - module_id
      - name
      - version
      - dependencies
      - entrypoints
    required_lifecycle_hooks:
      - install
      - enable
      - disable
      - uninstall
  plug_in_rule:
    on_discovery:
      assign_new_module_id: true
      initial_state: disabled
      traffic_light_initial: yellow
      requires_admin_review_before_enable: true
  compatibility_pins:
    validate_ci_version: true
    validate_dependency_versions: true
    validate_integrity_hash_or_signature: true
  assembly_pipeline:
    - discover
    - assign_module_id
    - persist_disabled_state
    - validate_contracts
    - install
    - enable_only_if_all_checks_pass
  safe_unplug_rule:
    - disable_first
    - block_if_enabled_dependents_exist
    - uninstall_with_audit_and_rollback
```
## Module Manager Attribute Priority Map
- Layer 1: Core Identity Attributes (base required)
  - Module state keys:
    - `registered`
    - `installed`
    - `enabled`
    - `disabled`
    - `uninstalled`
    - `error`
  - Event keys:
    - `module.discovered`
    - `module.installed`
    - `module.enabled`
    - `module.disabled`
    - `module.uninstalled`
    - `module.install.failed`
    - `module.enable.blocked`
  - Traffic light keys:
    - `green`
    - `yellow`
    - `red`
  - Manifest minimum keys:
    - `name`
    - `module_id`
    - `version`
    - `description`
    - `requires_ci_version`
    - `dependencies`
    - `entrypoints`

- Layer 2: Security Attributes (required)
  - Permission keys:
    - `module.view`
    - `module.install`
    - `module.enable`
    - `module.disable`
    - `module.uninstall`
    - `module.audit.view`
  - Access defaults:
    - `Owner`: full module permissions.
    - `Administrator`: full module permissions except owner-protected modules if flag enabled.
    - `Moderator`, `Author`, `User`, `Guest`: no module lifecycle permissions by default.
  - Safety flags:
    - `module.require_signature_for_external = true`
    - `module.require_dependency_validation = true`
    - `module.require_compatibility_validation = true`
    - `module.require_audit_for_mutation = true`
    - `module.block_disable_core = true`

- Layer 3: Data Integrity Attributes (exact schema required)
  - `modules` (required):
    - Purpose: canonical state and metadata of each known module.
    - Columns:
      - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
      - `name` (VARCHAR 120, NOT NULL)
      - `module_code` (VARCHAR 120, NOT NULL, UNIQUE)
      - `version` (VARCHAR 32, NOT NULL)
      - `description` (VARCHAR 255 NULL)
      - `status` (ENUM `registered`,`installed`,`enabled`,`disabled`,`uninstalled`,`error` NOT NULL DEFAULT `registered`)
      - `is_core` (TINYINT(1) NOT NULL DEFAULT 0)
      - `priority` (INT NOT NULL DEFAULT 100)
      - `manifest_hash` (CHAR 64 NULL)
      - `integrity_status` (ENUM `unknown`,`verified`,`failed` NOT NULL DEFAULT `unknown`)
      - `traffic_light` (ENUM `green`,`yellow`,`red` NOT NULL DEFAULT `yellow`)
      - `traffic_reason` (VARCHAR 255 NULL)
      - `installed_at` (DATETIME NULL)
      - `enabled_at` (DATETIME NULL)
      - `disabled_at` (DATETIME NULL)
      - `updated_at` (DATETIME NOT NULL)
      - `updated_by` (BIGINT UNSIGNED NULL, FK -> `users.id`)
    - Indexes/Constraints:
      - Unique (`module_code`)
      - Index (`status`, `priority`)
      - Index (`is_core`, `status`)
      - Index (`traffic_light`, `status`)
  - `module_dependencies` (required):
    - Purpose: dependency graph and version constraints.
    - Columns:
      - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
      - `module_id` (BIGINT UNSIGNED, NOT NULL, FK -> `modules.id`)
      - `depends_on_module_id` (BIGINT UNSIGNED, NOT NULL, FK -> `modules.id`)
      - `version_constraint` (VARCHAR 60, NOT NULL)
      - `is_optional` (TINYINT(1) NOT NULL DEFAULT 0)
      - `created_at` (DATETIME NOT NULL)
    - Indexes/Constraints:
      - Unique (`module_id`, `depends_on_module_id`)
      - Index (`depends_on_module_id`)
      - FK-like logical rule: both module IDs must exist in `modules.id`
  - `module_audit` (required):
    - Purpose: immutable audit trail for module operations.
    - Columns:
      - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
      - `event_uuid` (CHAR 36, NOT NULL, UNIQUE)
      - `event_key` (VARCHAR 120, NOT NULL)
      - `module_id` (BIGINT UNSIGNED, NOT NULL, FK -> `modules.id`)
      - `actor_user_id` (BIGINT UNSIGNED NULL, FK -> `users.id`)
      - `status_before` (VARCHAR 32 NULL)
      - `status_after` (VARCHAR 32 NULL)
      - `reason_code` (VARCHAR 80 NULL)
      - `details_json` (JSON NULL)
      - `ip_address` (VARCHAR 45 NULL)
      - `user_agent` (VARCHAR 255 NULL)
      - `request_id` (VARCHAR 64 NULL)
      - `created_at` (DATETIME NOT NULL)
    - Indexes/Constraints:
      - Index (`module_id`, `created_at`)
      - Index (`event_key`, `created_at`)
      - Index (`actor_user_id`, `created_at`)

### Layer 3 Logical Fields (Quick View)
| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Module.Id` | `modules.id` | PK, auto increment |
| `Module.Name` | `modules.name` | `VARCHAR(120)` |
| `Module.Code` | `modules.module_code` | `VARCHAR(120)`, unique |
| `Module.Version` | `modules.version` | `VARCHAR(32)` |
| `Module.Description` | `modules.description` | `VARCHAR(255)`, nullable |
| `Module.Status` | `modules.status` | `registered/installed/enabled/disabled/uninstalled/error` |
| `Module.Core` | `modules.is_core` | bool |
| `Module.Priority` | `modules.priority` | int |
| `Module.Manifest.Hash` | `modules.manifest_hash` | `CHAR(64)`, nullable |
| `Module.Integrity.Status` | `modules.integrity_status` | `unknown/verified/failed` |
| `Module.Traffic.Light` | `modules.traffic_light` | `green/yellow/red` |
| `Module.Traffic.Reason` | `modules.traffic_reason` | `VARCHAR(255)`, nullable |
| `Module.InstalledAt` | `modules.installed_at` | datetime, nullable |
| `Module.EnabledAt` | `modules.enabled_at` | datetime, nullable |
| `Module.DisabledAt` | `modules.disabled_at` | datetime, nullable |
| `Module.UpdatedAt` | `modules.updated_at` | datetime |
| `Module.UpdatedBy` | `modules.updated_by` | FK -> `users.id`, nullable |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Module.Dependency.Id` | `module_dependencies.id` | PK, auto increment |
| `Module.Dependency.ModuleId` | `module_dependencies.module_id` | FK -> `modules.id` |
| `Module.Dependency.DependsOnModuleId` | `module_dependencies.depends_on_module_id` | FK -> `modules.id` |
| `Module.Dependency.VersionRule` | `module_dependencies.version_constraint` | `VARCHAR(60)` |
| `Module.Dependency.Optional` | `module_dependencies.is_optional` | bool |
| `Module.Dependency.CreatedAt` | `module_dependencies.created_at` | datetime |
| `Module.Dependency.Constraint` | `unique(module_id, depends_on_module_id)` | both module IDs must exist in `modules.id` |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Module.Audit.Id` | `module_audit.id` | PK, auto increment |
| `Module.Audit.EventId` | `module_audit.event_uuid` | UUID, unique |
| `Module.Audit.EventKey` | `module_audit.event_key` | `VARCHAR(120)` |
| `Module.Audit.ModuleId` | `module_audit.module_id` | FK -> `modules.id` |
| `Module.Audit.ActorUserId` | `module_audit.actor_user_id` | FK -> `users.id`, nullable |
| `Module.Audit.StatusBefore` | `module_audit.status_before` | `VARCHAR(32)`, nullable |
| `Module.Audit.StatusAfter` | `module_audit.status_after` | `VARCHAR(32)`, nullable |
| `Module.Audit.Reason` | `module_audit.reason_code` | `VARCHAR(80)`, nullable |
| `Module.Audit.Details` | `module_audit.details_json` | JSON, nullable |
| `Module.Audit.IpAddress` | `module_audit.ip_address` | `VARCHAR(45)`, nullable |
| `Module.Audit.UserAgent` | `module_audit.user_agent` | `VARCHAR(255)`, nullable |
| `Module.Audit.RequestId` | `module_audit.request_id` | `VARCHAR(64)`, nullable |
| `Module.Audit.CreatedAt` | `module_audit.created_at` | datetime |

- Layer 4: Lifecycle and Execution Rules (required)
  - Lifecycle transitions:
    - `registered -> installed`
    - `installed -> enabled`
    - `enabled -> disabled`
    - `disabled -> enabled`
    - `disabled -> uninstalled`
    - Any state -> `error` when validation/boot fails.
  - Blocked transitions:
    - Cannot disable or uninstall `is_core = 1` when `module.block_disable_core = true`.
    - Cannot enable when required dependency is missing or incompatible.
    - Cannot uninstall when dependent enabled modules exist.
  - Execution order:
    - Resolve by dependency DAG first.
    - Apply `priority` second.
    - Equal priority tie-breaker: lexical `module_code`.
  - Install pipeline:
    - Validate manifest.
    - Validate compatibility.
    - Validate dependencies.
    - Run migrations/seeders.
    - Register routes/filters/services/events.
    - Persist state and audit.
  - Rollback rule:
    - On failure during install/enable, revert partial state, set `status = error`, emit audit event.
  - Traffic light evaluation rules:
    - `green`: module is `enabled`, dependencies are resolved, compatibility passes, integrity is `verified`.
    - `yellow`: module is `installed` or `disabled` without hard errors, or integrity is `unknown`.
    - `red`: module is in `error`, dependency validation fails, compatibility fails, or integrity is `failed`.
    - Traffic light must be recalculated after every lifecycle mutation and stored in `modules.traffic_light`.

- Layer 5: Operational Controls (required for production)
  - Runtime compatibility:
    - Localhost/dev MAY use CLI lifecycle commands.
    - External hosting without CLI MUST use ControlPanel routes.
  - Concurrency controls:
    - Single mutation lock per module `module_id`.
    - Reject concurrent lifecycle operations with conflict response.
  - Safe mode:
    - `module.safe_mode = true` blocks all mutating operations except view/audit.
  - Health rules:
    - Alert when module enters `error` state.
    - Alert when dependency graph has unresolved required nodes.
  - Required config keys:
    - `module.safe_mode`
    - `module.require_signature_for_external`
    - `module.block_disable_core`
    - `module.default_priority`
    - `module.lifecycle.lock_timeout_seconds`

- Layer Progression Rule:
  - Complete Layer 1 before Layer 2.
  - Complete Layer 2 before Layer 3.
  - Complete Layer 3 before Layer 4.
  - Complete Layer 4 before Layer 5.
  - Later layers must not break keys/contracts defined in earlier layers.

## Route-to-Permission Matrix (Module Manager)
| Route | Method | Action | Required Permission | Allowed Roles (Default) | Scope Rule |
|---|---|---|---|---|---|
| `ControlPanel/ModuleManager/Index.app` | `GET` | View complete module dashboard and state | `module.view` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/ModuleManager/Traffic.app` | `GET` | View module traffic light status | `module.view` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/ModuleManager/Audit.app` | `GET` | View module audit log | `module.audit.view` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/ModuleManager/Install.app` | `POST` | Install module | `module.install` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/ModuleManager/Enable.app` | `POST` | Enable module | `module.enable` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/ModuleManager/Disable.app` | `POST` | Disable module | `module.disable` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/ModuleManager/Uninstall.app` | `POST` | Uninstall module | `module.uninstall` | `Owner` (default), `Administrator` (if policy allows) | Full scope |

### Enforcement Rules
- Deny by default when route is not mapped.
- Deny when actor role is inactive.
- Deny when required permission key is missing.
- Deny all mutation routes when `module.safe_mode = true`.
- Log all denied actions to `module_audit` with event key `module.permission.denied`.

## Response/Error Contract (Module Manager)
### Standard JSON Response Envelope
- Success envelope:
  - `success` (bool) = `true`
  - `status` (int)
  - `code` (string)
  - `message` (string)
  - `request_id` (string)
  - `timestamp` (ISO-8601 UTC)
  - `data` (object)
  - `meta` (object, optional)
- Error envelope:
  - `success` (bool) = `false`
  - `status` (int)
  - `code` (string)
  - `message` (string)
  - `request_id` (string)
  - `timestamp` (ISO-8601 UTC)
  - `error.type` (`validation`,`authorization`,`conflict`,`dependency`,`server`)
  - `error.details[]` (`field`,`reason`)

### HTTP Status Mapping
- `GET ControlPanel/ModuleManager/Index.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `GET ControlPanel/ModuleManager/Audit.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `POST ControlPanel/ModuleManager/Install.app`:
  - `201 Created`, `202 Accepted`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`
- `POST ControlPanel/ModuleManager/Enable.app`:
  - `200 OK`, `202 Accepted`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`
- `POST ControlPanel/ModuleManager/Disable.app`:
  - `200 OK`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`
- `POST ControlPanel/ModuleManager/Uninstall.app`:
  - `200 OK`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`

### Error Code Catalog (Minimum)
- `MODULE_PERMISSION_DENIED`
- `MODULE_SAFE_MODE_ENABLED`
- `MODULE_NOT_FOUND`
- `MODULE_DEPENDENCY_MISSING`
- `MODULE_DEPENDENCY_CONFLICT`
- `MODULE_COMPATIBILITY_FAILED`
- `MODULE_INTEGRITY_FAILED`
- `MODULE_STATE_TRANSITION_INVALID`
- `MODULE_CONCURRENT_OPERATION`
- `MODULE_INTERNAL_ERROR`

## Module Manager Status
- Status: Complete
- Completed:
  - Layer 1 Core Identity Attributes
  - Layer 2 Security Attributes
  - Layer 3 Data Integrity Attributes
  - Layer 4 Lifecycle and Execution Rules
  - Layer 5 Operational Controls
  - Route-to-permission matrix
  - Response/Error contract
- Remaining (optional hardening):
  - Signed package intake workflow for remote module sources
  - Blue/green module rollout strategy for zero-downtime upgrades

## Inherited Common Attributes (Module Manager)
- This module inherits shared standards from `FrameWork.md`:
  - `Common Layer Standards (L1-L5)`
  - `Common Security Standards (Shared Attributes)`

