# FrameWork

## Module Files
- `Module/FrameWork.User_Manager.md`
- `Module.UI/FrameWork.User_Manager.UI.md` (Active: UI In Progress)
- `Module/FrameWork.Module_Manager.md`
- `Module.UI/FrameWork.Module_Manager.UI.md` (optional, per-module UI build plan for later stages)
- `Module/FrameWork.Security_Manager.md`
- `Module.UI/FrameWork.Security_Manager.UI.md` (optional, per-module UI build plan for later stages)
- `Module/FrameWork.DataBase_Manager.md`
- `Module.UI/FrameWork.DataBase_Manager.UI.md` (optional, per-module UI build plan for later stages)
- `Module/FrameWork.Route_Manager.md`
- `Module.UI/FrameWork.Route_Manager.UI.md` (optional, per-module UI build plan for later stages)
- `Module/FrameWork.Environment_Manager.md`
- `Module.UI/FrameWork.Environment_Manager.UI.md` (optional, per-module UI build plan for later stages)
- `Module/FrameWork.E-Mail_Manager.md`
- `Module.UI/FrameWork.E-Mail_Manager.UI.md` (optional, per-module UI build plan for later stages)
- `Module/FrameWork.Content_Manager-Public.md`
- `Module.UI/FrameWork.Content_Manager-Public.UI.md` (optional, per-module UI build plan for later stages)
- `Module/FrameWork.Content_Manager-Community.md`
- `Module.UI/FrameWork.Content_Manager-Community.UI.md` (optional, per-module UI build plan for later stages)
- `Module/FrameWork.Content_Manager-Personal.md`
- `Module.UI/FrameWork.Content_Manager-Personal.UI.md` (optional, per-module UI build plan for later stages)
- `Module/FrameWork.Page_Manager.md` (legacy compatibility note only; do not use as canonical module spec)

## Global Module Status
| Module Name | Core Status | UI Status |
| :--- | :--- | :--- |
| User Manager | `Complete` | `In Progress` |
| Security Manager | `Complete` | `Not Started` |
| Module Manager | `Complete` | `Not Started` |
| Database Manager | `Complete` | `Not Started` |
| Route Manager | `Complete` | `Not Started` |
| Environment Manager | `Complete` | `Not Started` |
| E-Mail Manager | `Complete` | `Not Started` |
| Content Manager - Public | `Complete` | `Not Started` |
| Content Manager - Community | `Complete` | `Not Started` |
| Content Manager - Personal | `Complete` | `Not Started` |

## AI Readable Module Acceptance Reference
- Canonical acceptance rules for new modules are defined in:
  - `Module/FrameWork.Module_Manager.md` -> `AI Readable Module Acceptance Rules` (YAML)
- Acceptance summary (normative):
  - Actor role must be `Administrator` or `Owner`
  - Required checks: `manifest`, `compatibility`, `dependencies`, `integrity`, `permissions`
  - Lifecycle allowed: `registered -> installed -> enabled -> disabled -> uninstalled` plus `any -> error`
  - Install/enable is blocked on failed dependency/compatibility/integrity checks
  - Mutations require audit and are denied in safe mode

## AI Module Loading Instructions
- AI must load `FrameWork.md` first for shared rules and canonical order.
- AI must load module files from `Module/` only when the task touches that module domain.
- AI should load the minimal set of module files needed for the current task.
- If UI/UX implementation is requested, AI should load `Module.UI/FrameWork.{Module_Key}.UI.md` for that module after loading the base module spec.
- AI must follow canonical module order unless task scope explicitly restricts to one module.
- If a task crosses module boundaries, AI must load all affected `Module/FrameWork.{Module_Key}.md` files before design or implementation.
- If shared rules conflict with a module file, `FrameWork.md` is authoritative for shared/global policy.

## Core Attributes (Shared Only)

## AI Compact Global Values
- Project:
  - Name: CodeIgniter v4
  - Type: Module
  - Description: Create CodeIgniter Application That Can Accept Modules.
- Runtime:
  - Language: PHP
  - Framework: CodeIgniter 4
  - HTML Version: HTML 5
- UI Baseline:
  - CSS Framework: Bootstrap 5
  - Version Policy: Bootstrap 5.x (latest stable patch within major version 5)
  - Scope: Applies to all module UI plans and implementations by default
  - Override Rule: Module-specific styling may extend Bootstrap but must not replace the global baseline unless explicitly approved
  - Field Exposure Rule: UI MUST show only the minimum required fields for input and output views (hide non-required fields by default).
- Database Global Value:
  - Engine: MySQL
  - Host: LocalHost
  - User: Root
  - Password: Empty
- CI4 Integration Surface:
  - Routing: `app/Config/Routes.php`, `app/Config/Routing.php`
  - Security/Filters: `app/Config/Filters.php`, `app/Config/Security.php`, `app/Config/ContentSecurityPolicy.php`
  - Services/Events: `app/Config/Services.php`, `app/Config/Events.php`
  - Data Layer: `app/Config/Database.php`, `app/Models/*`, `app/Database/Migrations/*`, `app/Database/Seeds/*`
  - Runtime Entry: `public/index.php`, `spark`
  - Integration Rule: module drivers MUST map framework contracts to these CI files before runtime publish.
- Deployment:
  - Localhost/Dev: PHP CLI (`php spark`) is available.
  - External Hosting/Test: PHP CLI may be unavailable; all critical operations must have Web/Admin or automated boot alternatives.
- Canonical Module Order:
  - User Manager, Security Manager, Module Manager, Database Manager, Route Manager, Environment Manager, E-Mail Manager, Content Manager - Public, Content Manager - Community, Content Manager - Personal

Built-In Framework Features (CodeIgniter v4):
- Routing (`app/Config/Routes.php`, `app/Config/Routing.php`)
- Filters/Middleware (`app/Config/Filters.php`)
- Services Container (`app/Config/Services.php`)
- Events (`app/Config/Events.php`)
- Database + Model Layer (`app/Config/Database.php`, `app/Models/*`)
- Migrations/Seeders (`app/Database/Migrations/*`, `app/Database/Seeds/*`)
- Validation (`app/Config/Validation.php`, `app/Language/*/Validation.php`)
- Session (`app/Config/Session.php`)
- Security (`app/Config/Security.php`, `app/Config/ContentSecurityPolicy.php`)
- Cache (`app/Config/Cache.php`)
- Logging/Exceptions (`app/Config/Logger.php`, `app/Config/Exceptions.php`)
- Localization (`app/Language/*`, `app/Config/App.php`)
- Email (`app/Config/Email.php`)
- CLI/Spark (`spark`, command classes)

Global Rules:
- Prefer built-in feature usage before creating custom logic.
- If built-in feature exists, custom module acts as driver/controller for that feature.
- If built-in feature does not exist, new custom module may be created.
- Do not modify `system/*`; use `app/Config/*`, services, filters, events, routes, models, migrations.
- CI integration is mandatory: each module driver MUST define which CI config/runtime file(s) it reads/writes and validation gates before apply.


## Shared Identifier Contract
- Purpose: keep naming human-readable while preserving strict implementation identity.
- Naming placeholders:
  - `{Module_Key}`: module file/key identifier used in documentation and file templates (example: `User_Manager`, `Route_Manager`).
  - `{Module_Id}`: canonical primary identifier used in routes/data references.
  - `{User_Id}`, `{Security_Id}`, `{Role_Id}`, `{Route_Id}`: canonical entity identity placeholders.
- Canonical identity model (global default):
  - `id`: numeric `BIGINT UNSIGNED` `AUTO_INCREMENT` primary key (default for all modules).
  - Public/API/route contracts MAY expose this numeric `Id` directly unless module exception applies.
- Global exception policy (approved):
  - Content Manager modules (`Public`, `Community`, `Personal`) MUST use alphanumeric content `Id` format `^[A-Z0-9]{8}$` generated by Security Manager.
  - User Security identities (link/security flow IDs such as validation/recovery/autologin link IDs) MAY use `^[A-Z0-9]{8}$` generated by Security Manager.
  - All other modules use default numeric auto-increment `id`.
- Id format standard (global):
  - Default module `Id`: numeric auto-increment (`BIGINT UNSIGNED`).
  - Exception format (Content/User Security): `A-Z`, `0-9`.
  - Exception length (Content/User Security): `8` characters.
  - Regex contract for exception IDs: `^[A-Z0-9]{8}$`.
- Route and data rule:
  - Route/query/path identity MUST use `*_Id` placeholders.
  - Database foreign keys MUST use `*_id` columns and match referenced PK type (numeric default, alphanumeric only for approved exceptions).
- Human-readable rule:
  - Keep logical labels for readability (example: `User.Name`, `Database.Schema.ModuleId`) mapped to canonical columns.


## Global Exception Reference Names
- User:
  - Default `User_Id`: numeric (`BIGINT UNSIGNED`, auto-increment)
  - Security-linked flows: `PassWord`, `Link_Id`, `Link_Validate`, `Link_Recovery`
- Security:
  - Default `Security_Id`: numeric (`BIGINT UNSIGNED`, auto-increment)
  - Security contracts:
    - `PassWord`: `A-Z`, `a-z`, `0-9`, safe special chars, min length `8`
    - `Link_Id`: `A-Z`, `0-9`, length `8`
    - `Link_Validate`: `Link_Token`
    - `Link_Recovery`: `Link_Token`
    - `Link_AutoLogin`: `Link_Token`
- Module:
  - Default `Module_Id`: numeric (`BIGINT UNSIGNED`, auto-increment)
- DataBase:
  - Default `DataBase_Id`: numeric (`BIGINT UNSIGNED`, auto-increment)
- Route:
  - Default `Route_Id`: numeric (`BIGINT UNSIGNED`, auto-increment)
- Environment:
  - Default `Environment_Id`: numeric (`BIGINT UNSIGNED`, auto-increment)
- E-Mail:
  - Default `Mail_Id`: numeric (`BIGINT UNSIGNED`, auto-increment)
- Any Level Content:
  - `Content_Id`: alphanumeric (`A-Z`, `0-9`), length `8`, Security-generated
  - `Link_Id` is reserved for security link flows only


## AI Readable Canonical Mapping
```yaml
id_policy:
  default:
    pk_column: id
    pk_type: BIGINT UNSIGNED
    pk_mode: AUTO_INCREMENT
  exceptions:
    content_manager:
      modules: [Content_Manager-Public, Content_Manager-Community, Content_Manager-Personal]
      id_type: CHAR(8)
      id_regex: "^[A-Z0-9]{8}$"
      id_generator: Security_Manager
    user_security_links:
      id_name: Link_Id
      id_type: CHAR(8)
      id_regex: "^[A-Z0-9]{8}$"
      id_generator: Security_Manager
  route_placeholder_rule: "*_Id"
  fk_rule:
    column_pattern: "*_id"
    type_must_match_referenced_pk: true

reference_names:
  User: User_Id
  Security: Security_Id
  Module: Module_Id
  DataBase: DataBase_Id
  Route: Route_Id
  Environment: Environment_Id
  EMail: Mail_Id
  Content: Content_Id
  SecurityLink: Link_Id

password_policy:
  allowed_sets: [A-Z, a-z, 0-9, safe_special]
  min_length: 8
```
Custom Module Strategy:
- Each module must declare target built-in features, control surface, and fallback for non-CLI hosts.
- Each module must provide function specification + flow/behavior specification before implementation.
- Admin high-impact actions must require role checks, explicit confirmation, and audit logging.

## Common Layer Standards (L1-L5)
- These are universal module attribute standards and apply to all `Module/FrameWork.{Module_Key}.md` files.
- Global schema decision policy applies to all modules: if fields are unspecified, AI must propose recommendations and wait for user confirmation before finalization.
- L1 Core Identity (required first):
  - Module keys, state keys, event keys, and minimum naming contracts.
- L2 Security (required second):
  - Permission keys, role defaults, deny-by-default behavior, and mandatory enforcement flags.
- L3 Data Integrity (required third):
  - Exact database schema: tables, columns, types, indexes, constraints, and integrity rules.
- L4 Behavior and Processing (required fourth):
  - Execution flow, transitions, validation logic, retries/backoff (if used), and rollback behavior.
- L5 Operations and Runtime Controls (required fifth):
  - Environment behavior, limits, monitoring/alerts, required config keys, and safe-mode/kill-switch rules.
- Progression rule:
  - L1 MUST be complete before L2.
  - L2 MUST be complete before L3.
  - L3 MUST be complete before L4.
  - L4 MUST be complete before L5.
  - Later layers MUST NOT break earlier-layer contracts.

## Common Security Standards (Shared Attributes)
- These standards apply to every module by default.
- Access control:
  - Deny-by-default when permission mapping is missing.
  - Every protected route MUST require explicit permission keys.
  - Role and permission checks MUST execute before business logic.
- Credential and token handling:
  - Password hashing/verification MUST use Security Manager services.
  - Token issue/verify/revoke MUST use Security Manager services.
  - Secrets/tokens/passwords MUST never be logged in plain form.
- Transport and request protection:
  - CSRF protection required for state-changing web routes.
  - Input validation and output encoding are required for all user input surfaces.
  - Security headers and CSP must be enforced by environment policy.
- Audit and traceability:
  - Sensitive actions MUST be audit logged with actor, action, target, timestamp, and request id.
  - Permission denials and auth failures MUST be logged.
- Rate-limit and lockout baseline:
  - Auth/token-sensitive endpoints MUST have defined rate-limit and lockout policy.
  - Lockout/attempt limits MUST be numeric and documented in module contract.
- Environment policy baseline:
  - `development`, `testing`, and `production` security modes MUST be explicitly defined.
  - Production MUST run strict mode for secret presence and unsafe command restrictions.

Implementation Sequence (Canonical):
1. User Manager
2. Security Manager
3. Module Manager
4. Database Manager
5. Route Manager
6. Environment Manager
7. E-Mail Manager
8. Content Manager - Public
9. Content Manager - Community
10. Content Manager - Personal

Dependency Map (Canonical):
`User Manager -> Security Manager -> Module Manager -> Database Manager -> Route Manager -> Environment Manager -> E-Mail Manager -> Content Manager - Public -> Content Manager - Community -> Content Manager - Personal`

Installation Lifecycle (Canonical):
1. Initialize (`.env` + DB config validation)
2. Persist installation state (`is_configured`, `is_installed`, `owner_user_id`, `installed_at`, `schema_version`)
3. First-run Owner registration (User Manager MUST run first and create the Owner account)
4. Mark bootstrap complete (`owner_user_id` present and active Owner role assigned)
5. Lock installer
6. Install/enable modules through Module Manager standards (authorized users only)
7. Runtime detection (installed vs installer flow)

Universal AI Execution Standard:
- `MUST`, `SHOULD`, `MAY` semantics apply.
- Follow canonical sequence + lifecycle.
- Deny-by-default on missing permission mapping.
- Update this file when architecture/order/lifecycle/global policy changes.

## Continuity Protocol (Step-by-Step Exact Execution)
- AI MUST execute work in canonical module order unless user explicitly requests a different module.
- AI MUST load `FrameWork.md` first, then load only required module files before making changes.
- AI MUST not skip steps inside a module: define base attributes first, then security, then data integrity, then behavior/operations, then contracts/status.
- AI MUST enforce bootstrap gate: User Manager first-run Owner registration is required before Module Manager accepts any non-core module.
- AI MUST enforce acceptance gate: Module Manager validates standards (manifest, dependency, compatibility, integrity, permissions) before a module can be installed/enabled.
- AI MUST keep module status explicit in each module file (`Not Started`, `In Progress`, `Complete`).
- AI MUST record completion gates before marking a module `Complete`:
  - Route/permission mapping defined.
  - Data schema/integrity defined.
  - Security/enforcement rules defined.
  - Response/error contract defined.
  - Remaining items listed as `None` or clearly marked optional.
- AI MUST treat completed modules as locked baseline; changes must be additive or explicitly versioned.
- AI MUST update cross-file references when folder structure or module naming changes.
- AI MUST provide a short progress summary after each major update:
  - Current module
  - Completed step
  - Next exact step
- AI SHOULD avoid duplicate definitions; if a rule already exists, reference it instead of rewriting.
- AI MAY open optional hardening items only after core completion of the current module.

## Global Chunked Processing Request
- This is a global default for all modules and all future AI tasks.
- AI MUST process work in small chunks:
  - Maximum one table OR one route matrix OR one contract block per chunk.
- After each chunk AI MUST output checkpoint fields:
  - Chunk Target
  - Edits Applied
  - Validation Result (PASS or FAIL)
  - Resume Point
- If validation is FAIL, AI MUST stop the current chunk, apply minimal fix, and re-run validation before continuing.
- If validation is PASS, AI MUST continue to the next chunk automatically unless user explicitly pauses.
- AI MUST avoid full-file rewrites when a local patch is possible.
- AI MUST preserve continuity state between chunks:
  - Current_Module, Current_Section, Completed_Items, Pending_Items, Blocking_Issues.
- AI MUST keep this protocol active unless user explicitly overrides it for a specific task.
### Continuity Checkpoint Template
- `Current Module:` `{Module_Key}`
- `Current Step:` `{Step_Name}`
- `Status:` `Not Started | In Progress | Complete`
- `Completed In This Step:` `{What was finalized}`
- `Next Step:` `{Exact next action}`
- `Blocking Issues:` `{None or concrete blockers}`

## Deferred Improvement Note
- After the full core structure of the application is completed, return for a readability and structure review pass.
- Review goals:
  - Improve human readability and learning flow.
  - Reduce duplicate wording while preserving canonical contracts.
  - Keep logic and standards unchanged unless explicitly approved.

## Scope Boundary
- `FrameWork.md` contains only shared framework attributes.
- All module-specific details (routes, permissions, schemas, contracts) are defined in `Module/FrameWork.{Module_Key}.md` files.
- UI implementation flow for each module may be defined in `Module.UI/FrameWork.{Module_Key}.UI.md` files for step-by-step development and production readiness.


## Universal Entity Lifecycle Policy (Id-Centric)
- Create:
  - Every new record MUST generate a new primary identity `Id`.
  - Creation responses SHOULD return the generated `Id` as canonical reference.
- Update:
  - Every edit/mutation MUST target an existing record by `Id`.
  - Update operations MUST NOT create replacement identities when editing existing data.
- Delete:
  - Delete operations MUST target record `Id`.
  - Default delete mode SHOULD be reversible soft delete (`deleted_at`, status/state flag).
  - Hard delete MAY be allowed only by explicit policy/permission and audit logging.
- Restore (UnDelete):
  - Reversible records MUST be restorable under the same original `Id`.
  - Restore actions MUST be permission-controlled and audit logged.
- Traceability:
  - All lifecycle actions (`create`, `update`, `delete`, `restore`) MUST be audit logged with actor, target `Id`, timestamp, and request id.
## AI Enforcement Directive (Id Standard)
- This directive is mandatory for any AI processing this framework.
- AI MUST treat `Id` as canonical entity identity across all modules (numeric by default; `^[A-Z0-9]{8}$` only for approved exceptions).
- AI MUST use existing `Id` for update and delete operations.
- AI MUST preserve original `Id` when restoring soft-deleted records.
- AI MUST use `*_Id` placeholders in route/query/path contracts.
- AI MUST use `*_id` for database foreign-key columns and match referenced PK type.
- AI MUST NOT introduce alternate identity contracts (`slug`, name-key, replacement-id) as primary control keys.
- AI MUST document any exception explicitly and require policy approval before applying it.
- AI MUST propose recommended database fields when schema fields are unspecified, and MUST wait for user confirmation before marking schema complete.

### AI Compliance Checklist (Required)
- `Create` returns/generated `Id`: Yes/No
- `Update` targets existing `Id`: Yes/No
- `Delete` targets `Id` with reversible default: Yes/No
- `Restore` keeps original `Id`: Yes/No
- `Route placeholders` use `*_Id`: Yes/No
- `DB references` use `*_id`: Yes/No
- `Audit` logs include target `Id`: Yes/No
- `Unspecified DB fields` proposed and confirmed: Yes/No
## Universal Field Naming and Order Standard
- Goal:
  - Keep all module contracts and data models human-readable and consistent.
- Canonical logical field order (when applicable):
  - `Id`
  - `Title`
  - `Name`
  - `Content`
  - `Description`
- Extended common order (optional by domain):
  - `Status`, `Type`, `Category`, `Visibility`
  - `CreatedAt`, `UpdatedAt`, `DeletedAt`
  - `CreatedBy`, `UpdatedBy`
- Rules:
  - All logical contracts SHOULD present fields in this canonical order.
  - If a field is not relevant, omit it; do not reorder remaining canonical fields.
  - Public/API docs and UI labels SHOULD use readable names (`Id`, `Title`, `Name`, `Content`, `Description`).
  - Database physical columns MAY be normalized by domain, but mappings to logical fields MUST be explicit.
- Unspecified database fields decision rule (GLOBAL):
  - If database fields are not specified, AI MUST propose a recommended field set in canonical order (Id, Title, Name, Content, Description, plus relevant optional fields).
  - AI MUST present recommendations as choices and require user confirmation before finalizing schema contracts.
  - Until user confirms, schema state remains `In Progress` and MUST NOT be marked `Complete`.
  - Identity rules from `Shared Identifier Contract` and `Universal Entity Lifecycle Policy` remain mandatory.
