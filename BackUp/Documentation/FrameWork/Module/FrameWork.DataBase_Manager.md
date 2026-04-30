# FrameWork.DatabaseManager

## Module Specification
Database Manager Description:

- Core Purpose: Centralize database connectivity, schema lifecycle, and data integrity rules across all modules.
- Main Responsibilities:
  - Manage database connection profiles and failover settings.
  - Execute and track module-aware migrations and seeders.
  - Enforce transaction patterns for multi-step operations.
  - Provide shared query standards, indexing guidance, and performance monitoring.
  - Control built-in DB behavior (`Config\Database`, Migrations, Seeders, Models) through module policy.
- Core Components:
  - `DatabaseConnectionManager`
  - `MigrationOrchestrator`
  - `SeederOrchestrator`
  - `QueryPolicyService`
- Expected Storage:
  - `migrations` table: migration history per namespace/module
  - `migration_locks` table: lock state for safe concurrent migration runs
  - Optional `query_audit` table: slow query and failure diagnostics
  - `schema_registry` table: canonical module-to-table ownership and compatibility metadata
  - `database_audit` table: immutable audit of migration/seed/restore/admin DB actions
- Database-Compatible Shared Field Standard (for all modules):
  - Identity standard (global default + exceptions):
    - Default primary key: `id` (`BIGINT UNSIGNED`, PK, AUTO_INCREMENT).
    - Exception primary key: `id` (`CHAR(8)`, PK, regex `^[A-Z0-9]{8}$`) only for Content Manager entities and approved User Security identities.
    - Application routes/contracts always use `Id`; joins/FKs use `*_id` matching target PK type.
  - Identity fields:
    - Primary key: `id` (`BIGINT UNSIGNED`, PK, AUTO_INCREMENT)
    - UUID event key (if event table): `event_uuid` (`CHAR 36`, UNIQUE)
  - User reference fields:
    - `*_user_id` fields must be `BIGINT UNSIGNED` and FK to `users.id` (nullable only when actor can be system/guest).
  - Time fields:
    - Use UTC `DATETIME` for `created_at`, `updated_at`, optional `deleted_at`.
  - State fields:
    - Use explicit enum/string state keys, never boolean-only for multi-state workflows.
  - Metadata fields:
    - Use `*_json` (`JSON`) for extensible metadata/payload.
  - Request tracing fields:
    - `request_id` (`VARCHAR 64`) for cross-module traceability where actions are externally triggered.
  - Hash fields:
    - Use `CHAR 64` for SHA-256 style hashes (`*_hash`).
  - Text length conventions:
    - Ids/keys: `VARCHAR 120..190` (or `BIGINT UNSIGNED` for FK IDs)
    - Error/reason code: `VARCHAR 80`
    - Human summary fields: `VARCHAR 255`
- Cross-Module Schema Registry Note (from previously defined modules):
  - User Manager-owned tables:
    - `users`
    - `user_profiles`
    - `user_roles`
    - `roles`
    - `permissions`
    - `role_permissions`
    - `role_permission_effective` (materialized/computed view)
  - Security Manager-owned tables:
    - `security_policies`
    - `security_tokens`
    - `security_lockouts`
    - `security_audit`
  - Module Manager-owned tables:
    - `modules`
    - `module_dependencies`
    - `module_audit`
  - Route Manager-owned tables:
    - `route_manifests`
    - `route_conflicts`
    - `route_rewrite_checks`
    - `route_audit`
  - Environment Manager-owned tables:
    - `environment_executables`
    - `environment_scripts`
    - `environment_commands`
  - E-Mail Manager-owned tables:
    - `email_templates`
    - `email_queue`
    - `email_audit`
    - `email_suppression` (optional)
  - Content Manager - Public-owned tables:
    - `public_pages`
    - `public_page_versions`
    - `public_menu_links` (optional)
  - Content Manager - Community-owned tables:
    - `community_contents`
    - `community_content_versions`
    - `community_content_reports` (optional)
  - Content Manager - Personal-owned tables:
    - `personal_contents`
    - `personal_content_versions`
    - `personal_content_shares` (optional)
  - Database Manager governance rules for shared integrity:
    - Table ownership remains with the source module contract; Database Manager orchestrates migration order and dependency-safe execution.
    - Any table/column change must be versioned in the owning module and coordinated through Database Manager migration policy.
    - Cross-module foreign keys must reference canonical primary tables (for example `users.id`) and must not break audit/history retention.
    - Duplicate table definitions across modules are not allowed; canonical owner module is the source of truth.
- Public Interfaces:
  - CLI (localhost/dev): `php spark migrate`, `migrate --all`, `db:seed`, `db:table`
  - Web/Admin (external host fallback): `ControlPanel/DataBaseManager/Index.app` for controlled migrate/seed/status actions (role-restricted)
  - Service methods: `connection(string $group)`, `runModuleMigrations(string $module)`, `withTransaction(callable $work)`
- Operational Rules:
  - Use module namespace migrations to isolate schema ownership.
  - Require reversible migrations (`up()` and `down()`).
  - Apply seeders only for controlled environments unless explicitly enabled for production.
  - If CLI is unavailable, allow only signed/authorized admin-triggered operations and log all changes.
- Security Rules:
  - Store credentials only in environment variables, never in source code.
  - Use least-privilege DB users by environment.
  - Prevent raw unsafe SQL execution paths in module services.

## Database Manager Module-Local Canonical Definitions (L1-L5)
### L1 Core Identity (Database Local)
- State keys:
  - `ready`, `migrating`, `locked`, `degraded`, `error`
- Event keys:
  - `db.migration.started`
  - `db.migration.completed`
  - `db.migration.failed`
  - `db.seed.started`
  - `db.seed.completed`
  - `db.seed.failed`
  - `db.backup.created`
  - `db.restore.started`
  - `db.restore.completed`
  - `db.restore.failed`

### L2 Security Controls (Database Local)
- Permission keys:
  - `database.view`
  - `database.migrate`
  - `database.seed`
  - `database.backup`
  - `database.restore`
  - `database.audit.view`
  - `database.lock.manage`
- Role defaults:
  - `Owner`: full database permissions
  - `Administrator`: full except owner-protected restore/lock override when policy requires
  - `Moderator`, `Author`, `User`, `Guest`: deny by default

### L3 Data Integrity (Database Local Canonical Schema)
- `migrations` (required):
  - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
  - `version` (VARCHAR 255 NOT NULL)
  - `class` (VARCHAR 255 NOT NULL)
  - `group` (VARCHAR 64 NOT NULL DEFAULT `default`)
  - `namespace` (VARCHAR 255 NOT NULL DEFAULT `App`)
  - `module_id` (BIGINT UNSIGNED NULL, FK -> `modules.id`)
  - `checksum_hash` (CHAR 64 NULL)
  - `executed_by` (BIGINT UNSIGNED NULL, FK -> `users.id`)
  - `executed_at` (DATETIME NOT NULL)
  - Unique: (`class`, `group`)
  - Index: (`namespace`, `module_id`, `executed_at`)
- `migration_locks` (required):
  - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
  - `lock_key` (VARCHAR 120 NOT NULL)
  - `owner_ref` (VARCHAR 120 NOT NULL)
  - `acquired_at` (DATETIME NOT NULL)
  - `expires_at` (DATETIME NOT NULL)
  - `released_at` (DATETIME NULL)
  - `request_id` (VARCHAR 64 NULL)
  - Unique: (`lock_key`)
  - Index: (`expires_at`)
- `query_audit` (optional):
  - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
  - `event_uuid` (CHAR 36 NOT NULL, UNIQUE)
  - `module_id` (BIGINT UNSIGNED NULL, FK -> `modules.id`)
  - `query_fingerprint_hash` (CHAR 64 NOT NULL)
  - `query_kind` (ENUM `select`,`insert`,`update`,`delete`,`ddl`,`other` NOT NULL)
  - `duration_ms` (INT UNSIGNED NOT NULL)
  - `rows_affected` (INT NULL)
  - `result_status` (ENUM `ok`,`warn`,`error` NOT NULL)
  - `error_code` (VARCHAR 80 NULL)
  - `request_id` (VARCHAR 64 NULL)
  - `created_at` (DATETIME NOT NULL)
  - Index: (`module_id`, `created_at`)
  - Index: (`result_status`, `duration_ms`)
- `schema_registry` (required):
  - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
  - `module_id` (BIGINT UNSIGNED NOT NULL, FK -> `modules.id`)
  - `table_name` (VARCHAR 190 NOT NULL)
  - `owner_module` (VARCHAR 120 NOT NULL)
  - `schema_version` (VARCHAR 32 NOT NULL)
  - `compatibility_level` (ENUM `strict`,`compatible`,`legacy` NOT NULL DEFAULT `strict`)
  - `is_active` (TINYINT(1) NOT NULL DEFAULT 1)
  - `registered_by` (BIGINT UNSIGNED NULL, FK -> `users.id`)
  - `registered_at` (DATETIME NOT NULL)
  - Unique: (`table_name`)
  - Index: (`module_id`, `is_active`)
- `database_audit` (required):
  - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
  - `event_uuid` (CHAR 36 NOT NULL, UNIQUE)
  - `event_key` (VARCHAR 120 NOT NULL)
  - `actor_user_id` (BIGINT UNSIGNED NULL, FK -> `users.id`)
  - `target_ref` (VARCHAR 190 NULL)
  - `status` (VARCHAR 32 NOT NULL)
  - `reason_code` (VARCHAR 80 NULL)
  - `request_id` (VARCHAR 64 NULL)
  - `metadata_json` (JSON NULL)
  - `created_at` (DATETIME NOT NULL)
  - Index: (`event_key`, `created_at`)
  - Index: (`actor_user_id`, `created_at`)

### L3 Logical Fields (Quick View)
| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Database.Migration.Version` | `migrations.version` | `VARCHAR(255)` |
| `Database.Migration.Class` | `migrations.class` | `VARCHAR(255)` |
| `Database.Migration.Group` | `migrations.group` | `VARCHAR(64)` |
| `Database.Migration.Namespace` | `migrations.namespace` | `VARCHAR(255)` |
| `Database.Migration.ModuleId` | `migrations.module_id` | FK -> `modules.id`, nullable |
| `Database.Migration.Checksum` | `migrations.checksum_hash` | `CHAR(64)`, nullable |
| `Database.Migration.ExecutedBy` | `migrations.executed_by` | FK -> `users.id`, nullable |
| `Database.Migration.ExecutedAt` | `migrations.executed_at` | datetime |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Database.Lock.Key` | `migration_locks.lock_key` | `VARCHAR(120)`, unique |
| `Database.Lock.Owner` | `migration_locks.owner_ref` | `VARCHAR(120)` |
| `Database.Lock.AcquiredAt` | `migration_locks.acquired_at` | datetime |
| `Database.Lock.ExpiresAt` | `migration_locks.expires_at` | datetime |
| `Database.Lock.ReleasedAt` | `migration_locks.released_at` | datetime, nullable |
| `Database.Lock.RequestId` | `migration_locks.request_id` | `VARCHAR(64)`, nullable |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Database.QueryAudit.EventId` | `query_audit.event_uuid` | UUID, unique |
| `Database.QueryAudit.ModuleId` | `query_audit.module_id` | FK -> `modules.id`, nullable |
| `Database.QueryAudit.QueryHash` | `query_audit.query_fingerprint_hash` | `CHAR(64)` |
| `Database.QueryAudit.QueryKind` | `query_audit.query_kind` | `select/insert/update/delete/ddl/other` |
| `Database.QueryAudit.DurationMs` | `query_audit.duration_ms` | unsigned int |
| `Database.QueryAudit.Result` | `query_audit.result_status` | `ok/warn/error` |
| `Database.QueryAudit.ErrorCode` | `query_audit.error_code` | `VARCHAR(80)`, nullable |
| `Database.QueryAudit.RequestId` | `query_audit.request_id` | `VARCHAR(64)`, nullable |
| `Database.QueryAudit.CreatedAt` | `query_audit.created_at` | datetime |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Database.Schema.ModuleId` | `schema_registry.module_id` | FK -> `modules.id` |
| `Database.Schema.Table` | `schema_registry.table_name` | `VARCHAR(190)`, unique |
| `Database.Schema.OwnerModule` | `schema_registry.owner_module` | `VARCHAR(120)` |
| `Database.Schema.Version` | `schema_registry.schema_version` | `VARCHAR(32)` |
| `Database.Schema.Compatibility` | `schema_registry.compatibility_level` | `strict/compatible/legacy` |
| `Database.Schema.Active` | `schema_registry.is_active` | bool |
| `Database.Schema.RegisteredBy` | `schema_registry.registered_by` | FK -> `users.id`, nullable |
| `Database.Schema.RegisteredAt` | `schema_registry.registered_at` | datetime |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Database.Audit.EventId` | `database_audit.event_uuid` | UUID, unique |
| `Database.Audit.EventKey` | `database_audit.event_key` | `VARCHAR(120)` |
| `Database.Audit.ActorUserId` | `database_audit.actor_user_id` | FK -> `users.id`, nullable |
| `Database.Audit.TargetRef` | `database_audit.target_ref` | `VARCHAR(190)`, nullable |
| `Database.Audit.Status` | `database_audit.status` | `VARCHAR(32)` |
| `Database.Audit.Reason` | `database_audit.reason_code` | `VARCHAR(80)`, nullable |
| `Database.Audit.RequestId` | `database_audit.request_id` | `VARCHAR(64)`, nullable |
| `Database.Audit.Meta` | `database_audit.metadata_json` | JSON, nullable |
| `Database.Audit.CreatedAt` | `database_audit.created_at` | datetime |

### L4 Behavior and Processing (Database Local Canonical)
- Migration flow:
  - Acquire `migration_locks.lock_key = global_migration`.
  - Validate dependency order using `schema_registry`.
  - Execute module migrations in canonical module order.
  - Persist result in `migrations` and `database_audit`.
  - Release lock.
- Seeder flow:
  - Require explicit environment guard (`development/testing` by default).
  - Log every seed execution in `database_audit`.
- Conflict handling:
  - Reject duplicate owner claims for same `schema_registry.table_name`.
  - Set operation status `error` and write audit with reason.

### L5 Operations and Runtime Controls (Database Local Canonical)
- Runtime policy:
  - Localhost/dev may use CLI-first DB operations.
  - External host must use authenticated ControlPanel DB routes with audit.
- Safety controls:
  - Block destructive operations when backup pre-check fails.
  - Enforce single active migration lock.
  - Support read-only safe mode for emergency freeze.
- Required config keys:
  - `database.safe_mode`
  - `database.migration.lock_timeout_seconds`
  - `database.query_audit.enabled`
  - `database.require_backup_before_restore`
  - `database.allow_production_seed`

## Route-to-Permission Matrix (Database Manager)
| Route | Method | Action | Required Permission | Allowed Roles (Default) | Scope Rule |
|---|---|---|---|---|---|
| `ControlPanel/DataBaseManager/Index.app` | `GET` | View DB dashboard/status | `database.view` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/DataBaseManager/Migrate.app` | `POST` | Run migrations | `database.migrate` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/DataBaseManager/Seed.app` | `POST` | Run seeders | `database.seed` | `Administrator`, `Owner` | `development/testing` default |
| `ControlPanel/DataBaseManager/Backup.app` | `POST` | Create backup snapshot | `database.backup` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/DataBaseManager/Restore.app` | `POST` | Restore from backup | `database.restore` | `Administrator`, `Owner` | Requires explicit confirm + policy flag |
| `ControlPanel/DataBaseManager/Audit.app` | `GET` | View DB audit log | `database.audit.view` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/DataBaseManager/Lock.app` | `POST` | Force lock/unlock migration lock | `database.lock.manage` | `Administrator`, `Owner` | Emergency only |

### Enforcement Rules (Database Manager)
- Deny-by-default:
  - If route/action is not explicitly mapped, deny request.
- Accepted role policy:
  - Minimum accepted role is `Administrator`.
  - `Administrator` and higher roles (for example `Owner`) are accepted.
  - Deny all DB manager actions for roles below `Administrator` unless a future explicit policy override is defined.
- Inactive-role deny:
  - Deny request when actor role is inactive, suspended, or otherwise invalid at request time.
- Safe-mode mutation block:
  - If `database.safe_mode = true`, deny all mutating actions (`Migrate`, `Seed`, `Backup`, `Restore`, `Lock`) and allow read-only views only.
- Production seed/restore guards:
  - In `production`, deny `Seed` unless `database.allow_production_seed = true`.
  - In `production`, deny `Restore` unless:
    - explicit confirmation flag is provided,
    - restore pre-checks pass (`database.require_backup_before_restore = true` policy honored),
    - lock state allows operation.
- Mandatory audit for high-impact actions:
  - Always write `database_audit` records for `Migrate`, `Seed`, `Backup`, `Restore`, and `Lock` actions.
  - Audit must include at least: `event_uuid`, `event_key`, `actor_user_id`, `target_ref`, `status`, `reason_code` (if any), `request_id`, `created_at`.
- Conflict/lock enforcement:
  - Deny concurrent mutation attempts when migration lock is active.
  - Return lock/conflict error contract and keep state unchanged on deny.

## Response/Error Contract (Database Manager)
### Standard Success Schema
- `success` (bool, must be `true`)
- `status` (int, HTTP status)
- `code` (string, stable application result code)
- `message` (human-readable summary)
- `request_id` (string)
- `timestamp` (ISO-8601 UTC datetime)
- `data` (object)
- `meta` (object, optional)

### Standard Error Schema
- `success` (bool, must be `false`)
- `status` (int, HTTP status)
- `code` (string, stable application error code)
- `message` (human-readable summary)
- `request_id` (string)
- `timestamp` (ISO-8601 UTC datetime)
- `error` (object):
  - `type` (`validation`, `authorization`, `conflict`, `state`, `lock`, `server`)
  - `details` (array of `{ field, reason }`)

### HTTP Status Mapping (Per Database Endpoint)
- `GET ControlPanel/DataBaseManager/Index.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `POST ControlPanel/DataBaseManager/Migrate.app`:
  - `200 OK`, `202 Accepted`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `423 Locked`, `500 Internal Server Error`
- `POST ControlPanel/DataBaseManager/Seed.app`:
  - `200 OK`, `202 Accepted`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`
- `POST ControlPanel/DataBaseManager/Backup.app`:
  - `200 OK`, `202 Accepted`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `500 Internal Server Error`
- `POST ControlPanel/DataBaseManager/Restore.app`:
  - `200 OK`, `202 Accepted`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `412 Precondition Failed`, `423 Locked`, `500 Internal Server Error`
- `GET ControlPanel/DataBaseManager/Audit.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `POST ControlPanel/DataBaseManager/Lock.app`:
  - `200 OK`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `423 Locked`, `500 Internal Server Error`

### DB Error Code Catalog (Minimum)
- `DB_PERMISSION_DENIED`
- `DB_VALIDATION_FAILED`
- `DB_SAFE_MODE_ENABLED`
- `DB_MIGRATION_LOCKED`
- `DB_MIGRATION_CONFLICT`
- `DB_MIGRATION_FAILED`
- `DB_SEED_NOT_ALLOWED`
- `DB_SEED_FAILED`
- `DB_BACKUP_FAILED`
- `DB_RESTORE_PRECHECK_FAILED`
- `DB_RESTORE_NOT_ALLOWED`
- `DB_RESTORE_FAILED`
- `DB_SCHEMA_OWNERSHIP_CONFLICT`
- `DB_QUERY_AUDIT_DISABLED`
- `DB_INTERNAL_ERROR`

### Optional Examples (Future Reference)
#### Success Example
```json
{
  "success": true,
  "status": 202,
  "code": "DB_MIGRATION_STARTED",
  "message": "Database migration started.",
  "request_id": "req_db_001",
  "timestamp": "2026-02-23T16:00:00Z",
  "data": {
    "lock_key": "global_migration",
    "operation": "migrate",
    "mode": "module_order"
  },
  "meta": {}
}
```

#### Error Example
```json
{
  "success": false,
  "status": 423,
  "code": "DB_MIGRATION_LOCKED",
  "message": "Migration is locked by another operation.",
  "request_id": "req_db_002",
  "timestamp": "2026-02-23T16:00:00Z",
  "error": {
    "type": "lock",
    "details": [
      {
        "field": "lock_key",
        "reason": "global_migration already acquired"
      }
    ]
  }
}
```

#### Validation Error Example
```json
{
  "success": false,
  "status": 422,
  "code": "DB_VALIDATION_FAILED",
  "message": "Restore request validation failed.",
  "request_id": "req_db_003",
  "timestamp": "2026-02-23T16:00:00Z",
  "error": {
    "type": "validation",
    "details": [
      {
        "field": "backup_id",
        "reason": "required"
      }
    ]
  }
}
```

## Database Manager Status
- Status: Complete
- Completed:
  - Module-local canonical definitions (L1-L5)
  - Module-compatible schema contracts and shared DB field standards
  - Cross-module schema ownership registry mapping
  - L3 logical fields quick-view tables
  - Route-to-permission matrix with `ControlPanel/DataBaseManager/*` endpoints
  - Enforcement rules (deny-by-default, safe-mode, production guards, mandatory audit)
  - Response/error contract with endpoint HTTP status mapping and examples
- Remaining:
  - None

## Inherited Common Attributes (Database Manager)
- This module inherits shared standards from `FrameWork.md`:
  - `Common Layer Standards (L1-L5)`
  - `Common Security Standards (Shared Attributes)`

