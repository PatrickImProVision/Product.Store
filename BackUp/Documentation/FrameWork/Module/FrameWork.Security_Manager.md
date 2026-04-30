# FrameWork.SecurityManager

## Module Specification
Security Manager Description:

- Core Purpose: Provide centralized security controls across all modules.
- Main Responsibilities:
  - Authentication and authorization integration.
  - Role/permission checks and policy enforcement.
  - Central password hashing/verification service for user credentials.
  - Central token generation/validation/revocation service for user flows (`validate`, `recovery`, `autologin`, and other security tokens).
  - CSRF, XSS protection, secure headers, and request throttling.
  - Security audit logging for sensitive actions.
  - Control built-in security behavior (`Config\Security`, `Config\Filters`, CSP) via centralized policies.
- Core Components:
  - `AccessControlService`
  - `PermissionResolver`
  - `SecurityPolicyRegistry`
  - `SecurityAuditLogger`
- Core Policies:
  - Password policy and session hardening.
  - Least privilege by default for module actions.
  - Route-level permission mapping.
  - Optional 2FA and login anomaly checks.
  - Token TTL, attempt limits, and replay protection policy.
- Public Interfaces:
  - Middleware/Filters: `auth`, `permission:{key}`, `throttle`
  - ControlPanel Route (canonical): `ControlPanel/SecurityManager/Index.app`
  - Service methods: `can($user, $action, $resource)`, `enforcePolicy($policy)`, `hashPassword($plain)`, `verifyPassword($plain, $hash)`, `generateEntityId($type)`, `generateLinkId()`, `issueToken($type, $subject)`, `verifyToken($type, $token)`, `revokeToken($type, $token)`, `buildValidationLink($User_Id)`, `buildRecoveryLink($User_Id)`, `buildAutoLoginLink($User_Id)`
- Security Rules:
  - Deny by default when permission mapping is missing.
  - Log failed auth/authorization attempts.
  - Rotate and protect cryptographic keys.
  - User credential and token secrets must never be exposed in logs, URLs, or error payloads.


## Entity Id Generation Policy (Security Canonical)
- Security Manager is the canonical generator for exception `Id` values (Content Manager IDs and User Security link/security IDs).
- Format:
  - Allowed symbols: `A-Z`, `0-9`
  - Length: `8`
  - Regex: `^[A-Z0-9]{8}$`
- Rules:
  - IDs must be generated with cryptographic randomness.
  - IDs must be unique in target table before commit.
  - Collision handling: regenerate until unique, then persist.
  - IDs are immutable once assigned.
- Scope:
  - This alphanumeric Id policy does NOT replace global numeric IDs for non-exception modules.

## Secure Link Generation Contract (Security Canonical)
- Purpose:
  - Security Manager generates and validates all sensitive user links.
- Covered functions:
  - Password processing: `hashPassword()`, `verifyPassword()`.
  - Link identity: `generateLinkId()` using Security canonical `Id` format.
  - Validation link generation and verification.
  - Recovery link generation and verification.
  - AutoLogin link generation and verification.
- Link Id contract:
  - `Link_Id` format MUST follow Security `Id` format: `^[A-Z0-9]{8}$`.
  - Link records are immutable by `Link_Id` and traceable by audit.
- Canonical link patterns (logical examples):
  - `UserPanel/Validate.app?Id={User_Id}&Link_Id={Link_Id}&Token={User_Token}`
  - `UserPanel/Recovery.app?Id={User_Id}&Link_Id={Link_Id}&Token={User_Recovery}`
  - `UserPanel/AutoLogin.app?Id={User_Id}&Link_Id={Link_Id}&Token={User_AutoLogin}`
- Enforcement rules:
  - Tokens in links MUST be single-use where policy requires (`validate`, `recovery`).
  - AutoLogin token MAY be reusable only within active policy window and session policy.
  - Link verification MUST check: token hash match, token type, expiry, revoke state, attempt limits, lockout state.
  - All link generation/verification/revocation MUST be audit logged.
  - Sensitive link tokens MUST NOT be logged in plain form.
## User Integration Contract (Password and Token Flows)
- User Manager MUST delegate password hashing/verification to Security Manager.
- User Manager MUST delegate token lifecycle operations to Security Manager for:
  - `UserPanel/Validate.app`
  - `UserPanel/Recovery.app`
  - `UserPanel/AutoLogin.app`
  - Any future user token route
- Security Manager is authoritative for:
  - Token format and entropy
  - TTL and max-attempt policy
  - Revocation and replay protection
  - Audit events for token/password operations

## Inherited Common Attributes (Security Manager)
- General Standards (shared baseline for Security Manager) are inherited from `FrameWork.md`:
  - `Common Layer Standards (L1-L5)`
  - `Common Security Standards (Shared Attributes)`
- Correct module identity for this inherited section: `Security Manager`.
- Full Security L1-L5 General Standards (inherited common attributes):
  - L1 Core Identity:
    - Define security keys, state keys, token types, and security event keys.
    - Use stable naming for cross-module compatibility.
  - L2 Security Controls:
    - Define permission keys, role defaults, and deny-by-default enforcement behavior.
    - Require explicit permission mapping for protected routes/actions.
  - L3 Data Integrity:
    - Define exact schema contracts for security-related persistence (policies, tokens, lockout, audit).
    - Define indexes/constraints and integrity checks.
  - L4 Behavior and Processing:
    - Define password/token verification flow, lockout transitions, replay protection, and revocation flow.
    - Define deterministic validation and failure handling rules.
  - L5 Operations and Runtime Controls:
    - Define environment behavior (`development`, `testing`, `production`), numeric thresholds, monitoring, and required config keys.
    - Define strict-mode and safe-mode operational guards.
- Layer progression requirement:
  - L1 MUST be complete before L2.
  - L2 MUST be complete before L3.
  - L3 MUST be complete before L4.
  - L4 MUST be complete before L5.
  - Later layers MUST NOT break earlier-layer contracts.
- Scope rule:
  - Shared baseline rules stay in `FrameWork.md`.
  - This file defines only Security-specific overrides, integrations, and contracts.

## Security Module-Local Canonical Definitions (L1-L5)
- Purpose:
  - This section is the Security Manager canonical source for module-local schema, processing, and runtime controls.

### L1 Core Identity (Security Local)
- Security state keys:
  - `active`, `degraded`, `locked`
- Token type keys:
  - `validate`, `recovery`, `autologin`, `session`, `break_glass`
- Security event keys:
  - `security.password.verified`
  - `security.password.failed`
  - `security.token.issued`
  - `security.token.verified`
  - `security.token.revoked`
  - `security.lockout.triggered`
  - `security.lockout.released`

### L2 Security Controls (Security Local)
- Permission keys:
  - `security.view`
  - `security.policy.view`
  - `security.policy.edit`
  - `security.lockout.view`
  - `security.lockout.manage`
  - `security.token.view`
  - `security.token.manage`
  - `security.audit.view`
- Role defaults:
  - `Owner`: all security permissions
  - `Administrator`: all except owner-protected policy mutation when restricted
  - `Moderator`: read-only subset only when explicitly granted
  - others: deny by default

### L3 Data Integrity (Security Local Canonical Schema)
- `security_policies` (required):
  - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
  - `policy_key` (VARCHAR 120, NOT NULL, UNIQUE)
  - `policy_value_json` (JSON NOT NULL)
  - `environment` (ENUM `development`,`testing`,`production` NOT NULL)
  - `is_active` (TINYINT(1) NOT NULL DEFAULT 1)
  - `updated_by` (BIGINT UNSIGNED NULL, FK -> `users.id`)
  - `updated_at` (DATETIME NOT NULL)
  - Index: (`environment`,`is_active`)
- `security_tokens` (required):
  - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
  - `token_hash` (CHAR 64, NOT NULL, UNIQUE)
  - `token_type` (ENUM `validate`,`recovery`,`autologin`,`session`,`break_glass` NOT NULL)
  - `subject_user_id` (BIGINT UNSIGNED NOT NULL, FK -> `users.id`)
  - `issued_at` (DATETIME NOT NULL)
  - `expires_at` (DATETIME NOT NULL)
  - `revoked_at` (DATETIME NULL)
  - `attempt_count` (INT UNSIGNED NOT NULL DEFAULT 0)
  - `max_attempts` (INT UNSIGNED NOT NULL DEFAULT 5)
  - `metadata_json` (JSON NULL)
  - Index: (`subject_user_id`,`token_type`)
  - Index: (`expires_at`,`revoked_at`)
  - Check: `attempt_count <= max_attempts`
- `security_lockouts` (required):
  - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
  - `scope` (ENUM `email`,`token`,`user`,`ip` NOT NULL)
  - `scope_value_hash` (CHAR 64 NOT NULL)
  - `reason_code` (VARCHAR 80 NOT NULL)
  - `failed_attempts` (INT UNSIGNED NOT NULL DEFAULT 0)
  - `lock_until` (DATETIME NOT NULL)
  - `created_at` (DATETIME NOT NULL)
  - `updated_at` (DATETIME NOT NULL)
  - Unique: (`scope`,`scope_value_hash`)
  - Index: (`lock_until`)
- `security_audit` (required):
  - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
  - `event_uuid` (CHAR 36, NOT NULL, UNIQUE)
  - `event_key` (VARCHAR 120, NOT NULL)
  - `actor_user_id` (BIGINT UNSIGNED NULL, FK -> `users.id`)
  - `target_user_id` (BIGINT UNSIGNED NULL, FK -> `users.id`)
  - `request_id` (VARCHAR 64 NULL)
  - `ip_address` (VARCHAR 45 NULL)
  - `user_agent` (VARCHAR 255 NULL)
  - `metadata_json` (JSON NULL)
  - `created_at` (DATETIME NOT NULL)
  - Index: (`event_key`,`created_at`)
  - Index: (`actor_user_id`,`created_at`)

### L3 Logical Fields (Quick View)
| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Security.Policy.Key` | `security_policies.policy_key` | `VARCHAR(120)`, unique |
| `Security.Policy.Value` | `security_policies.policy_value_json` | `JSON` |
| `Security.Policy.Environment` | `security_policies.environment` | `development/testing/production` |
| `Security.Policy.Active` | `security_policies.is_active` | `bool` |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Security.Token.Hash` | `security_tokens.token_hash` | `CHAR(64)`, unique |
| `Security.Token.Type` | `security_tokens.token_type` | `validate/recovery/autologin/session/break_glass` |
| `Security.Token.UserId` | `security_tokens.subject_user_id` | FK -> `users.id` |
| `Security.Token.ExpiresAt` | `security_tokens.expires_at` | datetime |
| `Security.Token.RevokedAt` | `security_tokens.revoked_at` | nullable datetime |
| `Security.Token.AttemptCount` | `security_tokens.attempt_count` | int |
| `Security.Token.MaxAttempts` | `security_tokens.max_attempts` | int |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Security.Lockout.Scope` | `security_lockouts.scope` | `email/token/user/ip` |
| `Security.Lockout.ScopeValueHash` | `security_lockouts.scope_value_hash` | `CHAR(64)` |
| `Security.Lockout.Reason` | `security_lockouts.reason_code` | `VARCHAR(80)` |
| `Security.Lockout.FailedAttempts` | `security_lockouts.failed_attempts` | int |
| `Security.Lockout.Until` | `security_lockouts.lock_until` | datetime |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Security.Audit.EventId` | `security_audit.event_uuid` | UUID, unique |
| `Security.Audit.EventKey` | `security_audit.event_key` | `VARCHAR(120)` |
| `Security.Audit.ActorUserId` | `security_audit.actor_user_id` | nullable FK -> `users.id` |
| `Security.Audit.TargetUserId` | `security_audit.target_user_id` | nullable FK -> `users.id` |
| `Security.Audit.RequestId` | `security_audit.request_id` | `VARCHAR(64)` |
| `Security.Audit.IpAddress` | `security_audit.ip_address` | `VARCHAR(45)` |
| `Security.Audit.CreatedAt` | `security_audit.created_at` | datetime |

### L4 Behavior and Processing (Security Local Canonical)
- Password flow:
  - Always hash with adaptive algorithm (Argon2id preferred, bcrypt fallback).
  - Verify using constant-time comparison path.
  - On repeated failures, increment lockout scope and trigger lock policy.
- Token flow:
  - Issue token as random high-entropy secret, store only `token_hash`.
  - Verify token by hash lookup, reject revoked/expired tokens.
  - Increment token `attempt_count` on failed verification.
  - Revoke token on successful one-time use for `validate`, `recovery`, `break_glass`.
- Link flow:
  - Generate `Link_Id` via `generateLinkId()` for each sensitive link issuance.
  - Bind link to `User_Id`, `token_type`, and token hash context.
  - Validate link context before token verification (route, type, and actor scope).
  - Deny tampered or mismatched `Link_Id` and write audit event.
- Replay protection:
  - Token re-use after revoke is denied and audited.
  - Break-glass keys are strictly one-time use.
- Deterministic failure handling:
  - Invalid token -> `SECURITY_INVALID_TOKEN`
  - Expired token -> `SECURITY_TOKEN_EXPIRED`
  - Revoked token -> `SECURITY_TOKEN_REVOKED`
  - Lockout active -> `SECURITY_LOCKED_OUT`

### L5 Operations and Runtime Controls (Security Local Canonical)
- Environment modes:
  - `development`: relaxed logging visibility (no secrets), lower lockout strictness.
  - `testing`: deterministic policies for repeatable tests.
  - `production`: strict security policies and hardened headers.
- Required numeric defaults:
  - `password.min_length = 8`
  - `token.default_ttl_minutes = 30`
  - `validate_ttl_minutes = 1440`
  - `recovery_ttl_minutes = 60`
  - `autologin_ttl_minutes = 43200`
  - `max_failed_attempts = 5`
  - `default_lockout_minutes = 1440` (next-day retry policy)
  - `session_idle_timeout_minutes = 30`
  - `session_absolute_timeout_minutes = 720`
- Monitoring and alerts:
  - Alert when lockout spikes exceed baseline threshold.
  - Alert on repeated token replay attempts.
  - Alert on policy changes in production.
- Required config keys:
  - `security.password.algorithm`
  - `security.token.secret`
  - `security.token.ttl.{type}`
  - `security.lockout.max_failed_attempts`
  - `security.lockout.duration_minutes`
  - `security.session.idle_timeout_minutes`
  - `security.session.absolute_timeout_minutes`
  - `security.audit.enabled`

### Examples (Future Reference)
- Token verify decision example:
  - Input: `token_type = recovery`, `attempt_count = 6`, `max_attempts = 5`
  - Output: deny with `SECURITY_LOCKED_OUT`, set/update `security_lockouts.lock_until`
- Policy lookup example:
  - Input: `policy_key = security.lockout.max_failed_attempts`, `environment = production`
  - Output: resolved active policy value from `security_policies`

## Route-to-Permission Matrix (Security Manager)
| Route | Method | Action | Required Permission | Allowed Roles (Default) | Scope Rule |
|---|---|---|---|---|---|
| `ControlPanel/SecurityManager/Index.app` | `GET` | View security dashboard | `security.view` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/SecurityManager/Policy.app` | `GET` | View security policy | `security.policy.view` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/SecurityManager/Policy.app` | `POST` | Update security policy | `security.policy.edit` | `Owner` (default), `Administrator` (if allowed by policy) | Full scope |
| `ControlPanel/SecurityManager/LockOut.app` | `GET` | View lockout/rate-limit state | `security.lockout.view` | `Administrator`, `Owner`, `Moderator` (read-only) | Moderator read-only scope |
| `ControlPanel/SecurityManager/LockOut.app` | `POST` | Update lockout/rate-limit rules | `security.lockout.manage` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/SecurityManager/Tokens.app` | `GET` | View token policy/status | `security.token.view` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/SecurityManager/Tokens.app` | `POST` | Revoke/rotate token policies | `security.token.manage` | `Owner` (default), `Administrator` (if allowed by policy) | Full scope |
| `ControlPanel/SecurityManager/Audit.app` | `GET` | View security audit | `security.audit.view` | `Administrator`, `Owner`, `Moderator` (limited) | Moderator limited to non-sensitive fields |

### Enforcement Rules (Security Manager)
- Deny by default if route is not mapped.
- Deny if role is inactive or required permission is missing.
- `Moderator` access to Security Manager is read-only and limited.
- `Owner` has final authority on policy-changing routes.
- All denied and policy-changing actions must be audit logged.

## Combined Contract From User Manager (Security Canonical)
- Source harmonization:
  - Security-sensitive response/error and token/lockout contracts from User Manager are combined here as canonical security rules.
  - User Manager keeps flow-level behavior and references this security canonical section.

### Security Event Metadata (Auth/Token/Lockout)
- Auth events minimum metadata:
  - `email_hash`
  - `attempt_count`
  - `token_type` (when token flow is used)
- Lockout events minimum metadata:
  - `scope` (`email`, `token`, `user`, `ip`)
  - `lock_until`

## Response/Error Contract (Security Manager)
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

### HTTP Status Mapping (Security-Relevant User Routes)
- `UserPanel/Validate.app`:
  - Success: `200 OK`
  - Invalid token: `400 Bad Request`
  - Expired/revoked token: `410 Gone`
  - Lockout: `423 Locked`
- `UserPanel/Login.app`:
  - Success: `200 OK`
  - Invalid credentials: `401 Unauthorized`
  - Account blocked/inactive: `403 Forbidden`
  - Lockout: `423 Locked`
- `UserPanel/AutoLogin.app`:
  - Success: `200 OK`
  - Invalid token: `401 Unauthorized`
  - Expired/revoked token: `410 Gone`
  - Lockout: `423 Locked`
- `UserPanel/Recovery.app`:
  - Request accepted: `202 Accepted`
  - Invalid token: `400 Bad Request`
  - Expired/revoked token: `410 Gone`
  - Lockout: `423 Locked`
- Control/Moderator security actions:
  - Success: `200 OK`
  - Unauthenticated: `401 Unauthorized`
  - Permission denied: `403 Forbidden`
  - Validation fail: `422 Unprocessable Entity`
  - Lockout policy conflict: `409 Conflict`

### Error Code Catalog (Security Minimum)
- `SECURITY_PERMISSION_DENIED`
- `SECURITY_POLICY_DENIED`
- `SECURITY_INVALID_TOKEN`
- `SECURITY_TOKEN_EXPIRED`
- `SECURITY_TOKEN_REVOKED`
- `SECURITY_LOCKED_OUT`
- `SECURITY_INVALID_CREDENTIALS`
- `SECURITY_ACCOUNT_BLOCKED`
- `SECURITY_VALIDATION_FAILED`
- `SECURITY_INTERNAL_ERROR`

## Security Module Status
- Status: Complete
- Completed:
  - Core security responsibilities and user-integration delegation
  - Inherited common standards (L1-L5 + shared security)
  - Security module-local canonical L1-L5 definitions (schema/process/ops)
  - Route-to-permission matrix
  - Combined response/error and token/lockout contract from User Manager
- Remaining:
  - None


