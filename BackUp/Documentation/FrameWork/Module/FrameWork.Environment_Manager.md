# FrameWork.EnvironmentManager

## Module Specification
Environment Manager Description:

- Core Purpose: Centralize runtime environment configuration and safe environment switching.
- Main Responsibilities:
  - Load and validate `.env` values.
  - Provide typed config access for modules.
  - Support environment profiles (`development`, `testing`, `production`).
  - Detect missing required environment keys at boot.
  - Define and validate executable paths used by scripts and commands.
  - Control command execution policy by environment profile.
  - Control built-in app configuration behavior (`Config\App`, `Config\Boot\*`) by environment profile.
- Core Components:
  - `EnvironmentProfileService`
  - `EnvValidator`
  - `EnvCache` (optional, for fast access to resolved values)
- Required Configuration Domains:
  - Core environment attributes must be persisted in database for runtime consistency and profile switching.
  - Application: `baseURL`, `appTimezone`, locale settings
  - Database: host, user, password, port, driver
  - Security: encryption key, CSP, HTTPS, proxy IPs
  - Mail/Queue/Storage provider keys (as modules require)
  - Executable paths:
    - PHP CLI binary path
    - Composer binary path
    - Spark path
    - Script root path
  - Command policy:
    - Allowed command list by environment
    - Disabled command list by environment

## Environment Database Storage Requirements (Canonical)
- Environment Manager MUST persist executable paths, script list, and command list in database.
- Required tables:
  - `environment_executables`:
    - Purpose: canonical executable path registry by environment.
    - Columns:
      - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
      - `environment` (ENUM `development`,`testing`,`production` NOT NULL)
      - `tool_key` (VARCHAR 80 NOT NULL) (`php_cli`, `composer`, `spark`, `script_root`)
      - `executable_path` (VARCHAR 255 NOT NULL)
      - `is_active` (TINYINT(1) NOT NULL DEFAULT 1)
      - `updated_by` (BIGINT UNSIGNED NULL, FK -> `users.id`)
      - `updated_at` (DATETIME NOT NULL)
    - Constraints:
      - Unique (`environment`, `tool_key`)
  - `environment_scripts`:
    - Purpose: script catalog allowed/managed by environment profile.
    - Columns:
      - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
      - `environment` (ENUM `development`,`testing`,`production` NOT NULL)
      - `script_key` (VARCHAR 120 NOT NULL)
      - `script_path` (VARCHAR 255 NOT NULL)
      - `script_description` (VARCHAR 255 NULL)
      - `is_active` (TINYINT(1) NOT NULL DEFAULT 1)
      - `updated_by` (BIGINT UNSIGNED NULL, FK -> `users.id`)
      - `updated_at` (DATETIME NOT NULL)
    - Constraints:
      - Unique (`environment`, `script_key`)
  - `environment_commands`:
    - Purpose: command policy registry (allowed/disabled) by environment.
    - Columns:
      - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
      - `environment` (ENUM `development`,`testing`,`production` NOT NULL)
      - `command_text` (VARCHAR 255 NOT NULL)
      - `policy` (ENUM `allow`,`deny` NOT NULL)
      - `reason` (VARCHAR 255 NULL)
      - `is_active` (TINYINT(1) NOT NULL DEFAULT 1)
      - `updated_by` (BIGINT UNSIGNED NULL, FK -> `users.id`)
      - `updated_at` (DATETIME NOT NULL)
    - Constraints:
      - Unique (`environment`, `command_text`, `policy`)
- Integrity rules:
  - Paths must be normalized and validated before save.
  - `production` deny policies must override conflicting allow entries.
  - All path/script/command changes must be audit logged.
  - Executable invocation without a script or command argument is invalid and must be blocked.

### Environment Manager Logical Fields (Quick View)
| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Environment.Executable.Id` | `environment_executables.id` | PK, auto increment |
| `Environment.Executable.Profile` | `environment_executables.environment` | `development/testing/production` |
| `Environment.Executable.ToolKey` | `environment_executables.tool_key` | `php_cli/composer/spark/script_root` |
| `Environment.Executable.Path` | `environment_executables.executable_path` | `VARCHAR(255)` |
| `Environment.Executable.Active` | `environment_executables.is_active` | bool |
| `Environment.Executable.UpdatedBy` | `environment_executables.updated_by` | FK -> `users.id`, nullable |
| `Environment.Executable.UpdatedAt` | `environment_executables.updated_at` | datetime |
| `Environment.Executable.Constraint` | `unique(environment, tool_key)` | one tool key per profile |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Environment.Script.Id` | `environment_scripts.id` | PK, auto increment |
| `Environment.Script.Profile` | `environment_scripts.environment` | `development/testing/production` |
| `Environment.Script.Key` | `environment_scripts.script_key` | `VARCHAR(120)` |
| `Environment.Script.Path` | `environment_scripts.script_path` | `VARCHAR(255)` |
| `Environment.Script.Description` | `environment_scripts.script_description` | `VARCHAR(255)`, nullable |
| `Environment.Script.Active` | `environment_scripts.is_active` | bool |
| `Environment.Script.UpdatedBy` | `environment_scripts.updated_by` | FK -> `users.id`, nullable |
| `Environment.Script.UpdatedAt` | `environment_scripts.updated_at` | datetime |
| `Environment.Script.Constraint` | `unique(environment, script_key)` | one script key per profile |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Environment.Command.Id` | `environment_commands.id` | PK, auto increment |
| `Environment.Command.Profile` | `environment_commands.environment` | `development/testing/production` |
| `Environment.Command.Text` | `environment_commands.command_text` | `VARCHAR(255)` |
| `Environment.Command.Policy` | `environment_commands.policy` | `allow/deny` |
| `Environment.Command.Reason` | `environment_commands.reason` | `VARCHAR(255)`, nullable |
| `Environment.Command.Active` | `environment_commands.is_active` | bool |
| `Environment.Command.UpdatedBy` | `environment_commands.updated_by` | FK -> `users.id`, nullable |
| `Environment.Command.UpdatedAt` | `environment_commands.updated_at` | datetime |
| `Environment.Command.Constraint` | `unique(environment, command_text, policy)` | policy entry uniqueness |

### Environment Execution Flow (Canonical)
- Step 1: `Administrator` (or `Owner`) defines the executable list in `environment_executables`.
- Step 2: `Administrator` defines script entries in `environment_scripts`.
- Step 3: `Administrator` defines command policy entries in `environment_commands`.
- Step 4: For each script or command operation, `Administrator` selects which executable from the stored executable list is used for execution.
- Step 5: System requires a non-empty script or command argument; empty argument execution is rejected.
- Step 6: System validates selected executable path + script/command policy before execution.
- Step 7: Execution decision and result are audit logged.
- Public Interfaces:
  - CLI (localhost/dev): `php spark env:check`, `env:show`, `env:profile {name}`, `env:paths:check`, `env:commands:check`
  - Web/Admin (external host fallback): `ControlPanel/EnvironmentManager/Index.app` for validation report and profile status view
  - Service methods: `get(string $key)`, `require(array $keys)`, `validateProfile(string $profile)`, `resolveExecutablePath(string $tool)`, `isCommandAllowed(string $command, string $profile)`
- Security Rules:
  - Minimal allowed role for Environment Manager access is `Administrator` (developer role context) or `Owner`.
  - Never expose secret values in logs or API responses.
  - Fail fast when required secrets are absent in production.
  - Separate writable runtime overrides from committed defaults.
  - Block unsafe commands in `production` by default.

## Inherited Common Attributes (Environment Manager)
- General Standards (shared baseline for Environment Manager) are inherited from `FrameWork.md`:
  - `Common Layer Standards (L1-L5)`
  - `Common Security Standards (Shared Attributes)`
- Correct module identity for this inherited section: `Environment Manager`.
- Full Environment L1-L5 General Standards (inherited common attributes):
  - L1 Core Identity:
    - Define environment keys, profile keys, state keys, and environment event keys.
    - Use stable naming for `development`, `testing`, `production` profiles.
  - L2 Security Controls:
    - Define environment permission keys, role defaults, and deny-by-default enforcement behavior.
    - Require explicit permission checks for environment profile changes and executable/command policy updates.
  - L3 Data Integrity:
    - Define schema contracts for environment attributes, profile values, and change history if persisted.
    - Define index/constraint rules and uniqueness requirements for profile-key pairs.
  - L4 Behavior and Processing:
    - Define deterministic profile validation, profile switch flow, executable path validation, and command policy enforcement.
    - Define strict failure handling for missing required keys in production.
  - L5 Operations and Runtime Controls:
    - Define environment-specific runtime behavior, diagnostics visibility, and strict-mode toggles.
    - Define required config keys and production guardrails.
- Layer progression requirement:
  - L1 MUST be complete before L2.
  - L2 MUST be complete before L3.
  - L3 MUST be complete before L4.
  - L4 MUST be complete before L5.
  - Later layers MUST NOT break earlier-layer contracts.
- Scope rule:
  - Shared baseline rules stay in `FrameWork.md`.
  - This file defines only Environment-specific overrides, integrations, and contracts.

## Route-to-Permission Matrix (Environment Manager)
| Route | Method | Action | Required Permission | Allowed Roles (Default) | Scope Rule |
|---|---|---|---|---|---|
| `ControlPanel/EnvironmentManager/Index.app` | `GET` | View environment dashboard and status | `environment.view` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/EnvironmentManager/Profile.app` | `GET` | View environment profile values | `environment.profile.view` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/EnvironmentManager/Profile.app` | `POST` | Switch/update environment profile | `environment.profile.manage` | `Owner` (default), `Administrator` (if policy allows) | Full scope |
| `ControlPanel/EnvironmentManager/Validate.app` | `POST` | Validate required env keys | `environment.validate` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/EnvironmentManager/Paths.app` | `GET` | View executable/script paths | `environment.path.view` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/EnvironmentManager/Paths.app` | `POST` | Update executable/script paths | `environment.path.manage` | `Owner` (default), `Administrator` (if policy allows) | Full scope |
| `ControlPanel/EnvironmentManager/Commands.app` | `GET` | View command allow/deny policy | `environment.command.view` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/EnvironmentManager/Commands.app` | `POST` | Update command allow/deny policy | `environment.command.manage` | `Owner` (default), `Administrator` (if policy allows) | Full scope |

### Enforcement Rules (Environment Manager)
- Deny by default when route is not mapped.
- Deny when role is inactive or required permission key is missing.
- `Administrator` is minimum role for Environment Manager access by default.
- Profile/path/command policy changes require audit logging and explicit confirmation.
- In `production`, unsafe command policy updates are blocked unless explicitly allowed by Owner policy.

## Response/Error Contract (Environment Manager)
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

### HTTP Status Mapping (Environment Endpoints)
- `GET ControlPanel/EnvironmentManager/Index.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `GET ControlPanel/EnvironmentManager/Profile.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `POST ControlPanel/EnvironmentManager/Profile.app`:
  - `200 OK`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`
- `POST ControlPanel/EnvironmentManager/Validate.app`:
  - `200 OK`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `422 Unprocessable Entity`, `500 Internal Server Error`
- `GET ControlPanel/EnvironmentManager/Paths.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `POST ControlPanel/EnvironmentManager/Paths.app`:
  - `200 OK`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`
- `GET ControlPanel/EnvironmentManager/Commands.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `POST ControlPanel/EnvironmentManager/Commands.app`:
  - `200 OK`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`

### Error Code Catalog (Environment Minimum)
- `ENV_PERMISSION_DENIED`
- `ENV_PROFILE_NOT_FOUND`
- `ENV_PROFILE_INVALID`
- `ENV_PROFILE_SWITCH_BLOCKED`
- `ENV_REQUIRED_KEY_MISSING`
- `ENV_VALUE_INVALID`
- `ENV_PATH_INVALID`
- `ENV_EXECUTABLE_NOT_FOUND`
- `ENV_COMMAND_POLICY_INVALID`
- `ENV_COMMAND_POLICY_BLOCKED`
- `ENV_STRICT_MODE_BLOCKED`
- `ENV_VALIDATION_FAILED`
- `ENV_INTERNAL_ERROR`

### Input Examples (Future Reference)
#### Profile Switch Input Example
```json
{
  "profile": "testing",
  "confirm": true
}
```

#### Paths Update Input Example
```json
{
  "php_cli_path": "C:\\Xampp\\php\\php.exe",
  "composer_path": "C:\\ProgramData\\ComposerSetup\\bin\\composer.bat",
  "spark_path": "A:\\Internal-Drive\\CloudDrive\\SilverSoft.Projects\\CodeIgniter\\spark",
  "script_root_path": "A:\\Internal-Drive\\CloudDrive\\SilverSoft.Projects\\CodeIgniter"
}
```

#### Command Policy Update Input Example
```json
{
  "environment": "production",
  "allowed_commands": [
    "php spark env:check",
    "php spark routes"
  ],
  "disabled_commands": [
    "php spark migrate:refresh",
    "php spark db:wipe"
  ]
}
```

## Environment Module-Local Canonical Definitions (L3-L5)
### L3 Data Integrity (Environment Local Canonical Schema)
- Canonical persisted tables:
  - `environment_executables`
  - `environment_scripts`
  - `environment_commands`
- Canonical integrity requirements:
  - Path normalization/validation before save.
  - Unique key constraints per environment (`tool_key`/`script_key`/command policy entries).
  - Audit logging required for all path/script/command mutations.
  - Production deny policy overrides conflicting allow entries.

### L4 Behavior and Processing (Environment Local Canonical)
- Canonical processing flow:
  - Administrator/Owner defines executable list.
  - Administrator/Owner defines script list and command policy list.
  - For each execution, one executable from stored list must be selected.
  - Empty script/command argument execution is invalid and blocked.
  - Selected executable + policy + profile validation runs before execution.
  - Decision and execution result are audit logged.
- Failure handling:
  - Missing required env keys in production causes hard validation failure.
  - Invalid path/command policy causes validation error and no execution.

### L5 Operations and Runtime Controls (Environment Local Canonical)
- Environment runtime policy:
  - `development`: flexible diagnostics, non-secret output only.
  - `testing`: deterministic policy mode for reproducible checks.
  - `production`: strict validation and unsafe-command blocking by default.
- Access/operations controls:
  - Minimum role: `Administrator` (or `Owner`).
  - High-impact changes (profile switch, path/policy mutation) require confirmation and audit logging.
- Operational requirements:
  - Web/admin control path: `ControlPanel/EnvironmentManager/Index.app`
  - CLI support: `env:check`, `env:show`, `env:profile`, `env:paths:check`, `env:commands:check`

## Environment Module Status
- Status: Complete
- Completed:
  - Core environment responsibilities and profile model (`development`, `testing`, `production`)
  - Database persistence requirements for executable paths, scripts, and command policy
  - Environment execution flow and no-empty-argument execution guard
  - Route-to-permission matrix for `ControlPanel/EnvironmentManager/*`
  - Response/error contract with HTTP status mapping and input examples
  - Explicit module-local canonical definitions (L3/L4/L5)
- Remaining:
  - None
