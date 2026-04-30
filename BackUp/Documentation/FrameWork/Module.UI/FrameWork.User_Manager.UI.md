# FrameWork.UserManager.UI

## Purpose
UI flow specification for User Manager implementation phases.

## Status
- In Progress

## Source Contracts
- Base module: `BackUp/FrameWork/Module/FrameWork.User_Manager.md`
- Security canonical (status/error/token): `BackUp/FrameWork/Module/FrameWork.Security_Manager.md`
- Global UI rule: show minimum required fields only.

## Common Validation Baseline (All Pages)
- Input must be UTF-8 clean.
- Trim string fields before validation.
- Do not log raw password/token/recovery secrets.
- `User_Id` validation: required when used, numeric integer, `>= 1`.
- `Link_Id` validation: required when used, regex `^[A-Z0-9]{8}$`.
- `PassWord` validation: min length `8`, must follow Security policy (A-Z, a-z, 0-9, safe special).
- Token validation: opaque string accepted by Security Manager `verifyToken()`, deny empty/malformed transport values.

## Page-by-Page UI Specification (Chunk 1: UserPanel)

### Page 1: `UserPanel/Register.app`
- Method: `POST`
- Goal: register new user account.
- Actor: `Guest`.
- Input fields and validation:
  - `User.UserName` (required): string, length `3..32`, regex `^[A-Za-z0-9](?:[A-Za-z0-9._-]{1,30}[A-Za-z0-9])?$`, case-insensitive unique.
  - `User.E-Mail` (required): string email, max `254`, valid RFC-style email format, lowercase normalize, unique.
  - `PassWord` (required): string, min `8`, Security policy compliant.
- Output view data (minimum):
  - `User.Id`, `User.UserName`, `User.E-Mail`, `User.Status`, `User.Validate.Active`
- Response rules:
  - Success: `201`
  - Validation: `422`
  - Duplicate user/email: `409`

### Page 2: `UserPanel/Validate.app`
- Method: `GET` or `POST` (link verify flow)
- Goal: validate account/token.
- Actor: `Guest` or `User`.
- Input fields and validation:
  - `User_Id` (required): numeric id, `>= 1`.
  - `Link_Id` (required): regex `^[A-Z0-9]{8}$`.
  - `Token` (required): non-empty string, pass Security token verify for `validate` type.
- Output view data (minimum):
  - `User.Id`, `User.Validate.Active`, `User.Validate.At`, `User.Status`
- Response rules:
  - Success: `200`
  - Invalid token: `400`
  - Expired token: `410`
  - Lockout: `423`

### Page 3: `UserPanel/Login.app`
- Method: `POST`
- Goal: authenticate user session.
- Actor: `Guest`.
- Input fields and validation:
  - `User.E-Mail` (required): valid email format, lowercase normalize.
  - `PassWord` (required): string, min `8`.
  - `User.UserName` (output-only after login): shown for profile/display, not accepted as login credential.
- Login rule:
  - authentication credential is `User.E-Mail` only; `User.UserName` is used after login for display/profile contexts.
- Output view data (minimum):
  - `User.Id`, `User.Role`, `User.Status`, `User.Login.LastAt`, `User.UserName`
- Response rules:
  - Success: `200`
  - Invalid credentials: `401`
  - Forbidden/inactive: `403`
  - Lockout: `423`

### Page 4: `UserPanel/AutoLogin.app`
- Method: `GET` or `POST`
- Goal: restore login from auto-login token.
- Actor: `Guest`.
- Input fields and validation:
  - `User_Id` (required): numeric id, `>= 1`.
  - `Link_Id` (required): regex `^[A-Z0-9]{8}$`.
  - `Token` (required): non-empty string, pass Security token verify for `autologin` type.
- Output view data (minimum):
  - `User.Id`, `User.Role`, `User.Status`
- Response rules:
  - Success: `200`
  - Invalid token: `401`
  - Expired/revoked: `410`
  - Lockout: `423`

### Page 5: `UserPanel/Recovery.app`
- Method: `POST`
- Goal: account recovery request/confirm.
- Actor: `Guest` or `User`.
- Input fields and validation:
  - Request mode:
    - `User.E-Mail` (required): valid email format, lowercase normalize.
  - Confirm mode:
    - `User_Id` (required): numeric id, `>= 1`.
    - `Link_Id` (required): regex `^[A-Z0-9]{8}$`.
    - `Token` (required): non-empty string, pass Security token verify for `recovery` type.
- Cross-field rule:
  - exactly one mode allowed per request (`request` or `confirm`).
- Output view data (minimum):
  - `request_id`, `status`, recovery flow state
- Response rules:
  - Request accepted: `202`
  - Invalid token: `400`
  - Expired/revoked: `410`
  - Lockout: `423`

### Page 6: `UserPanel/Logout.app`
- Method: `POST`
- Goal: terminate current session.
- Actor: authenticated user.
- Input fields and validation:
  - session/auth context (required): valid active session.
  - `csrf_token` (required for web form POST): valid CSRF token.
- Output view data (minimum):
  - `success`, `code`, `request_id`
- Response rules:
  - Success: `200`
  - No active session: `401`

### Page 7: `UserPanel/ProFile.app`
- Method: `GET`, `POST`
- Goal: view/update own profile.
- Actor: authenticated user.
- Input fields and validation (POST write):
  - `User.Name` (required): length `1..80`, regex `^[A-Za-z][A-Za-z\s'\-]{0,79}$`.
  - `User.SurName` (required): length `1..80`, regex `^[A-Za-z][A-Za-z\s'\-]{0,79}$`.
  - `User.Phone` (optional): length `7..20`, regex `^\+?[0-9][0-9\s\-().]{6,19}$`, normalize.
  - `User.Locale` (optional): regex `^[a-z]{2}(?:_[A-Z]{2})?$`, allowed-list check.
  - `User.TimeZone` (optional): regex `^[A-Za-z]+(?:\/[A-Za-z_+-]+)+$`, must be valid PHP timezone id.
  - `User.Avatar.Url` (optional): valid URL or local path, max `255`, allow schemes `https` and `http`, local path allowed by policy.
- Output view data (minimum):
  - `User.Id`, `User.E-Mail`, `User.UserName`, `User.Name`, `User.SurName`, `User.Phone`, `User.Locale`, `User.TimeZone`, `User.Avatar.Url`
- Response rules:
  - Success: `200`
  - Validation: `422`
  - Unauthorized: `401`
  - Forbidden: `403`

### Page 8: `UserPanel/User/Address.app`
- Method: `GET`, `POST`
- Goal: manage own address.
- Actor: authenticated user.
- Input fields and validation (POST write):
  - `User.Address.FlatNumber` (optional): string, max `20`, safe characters `[A-Za-z0-9\-\/ ]`.
  - `User.Address.HouseNumber` (required): string, max `20`, safe characters `[A-Za-z0-9\-\/ ]`.
  - `User.Address.StreetName` (required): string, length `2..120`.
  - `User.Address.City` (required): string, length `2..80`.
  - `User.Address.CountryCode` (required): ISO-2 uppercase country code, regex `^[A-Z]{2}$`.
  - `User.Address.PostCode` (required): country-specific validation using selected country rule set.
    - Example `CZ`: numeric-only policy.
    - Example `UK`: alphanumeric postcode policy.
- Output view data (minimum):
  - address fields above, `User.Id`
- Response rules:
  - Success: `200`
  - Validation: `422`
  - Unauthorized/Forbidden: `401/403`

### Page 9: `UserPanel/User/Access.app`
- Method: `GET`, `POST`
- Goal: manage own access/session/security preferences.
- Actor: authenticated user.
- Input fields and validation:
  - `action` (required for POST): enum `view|revoke|update` (UI may hide `view` for POST).
  - `session_target_id` (required for revoke/update): opaque id string, max `128`.
  - `current_password` (required for high-risk update): min `8`, verify via Security Manager.
- Cross-field rule:
  - `session_target_id` required only when action targets a specific session.
- Output view data (minimum):
  - `User.Id`, active sessions/devices summary, role/permission effective summary (read-only)
- Response rules:
  - Success: `200`
  - Unauthorized: `401`
  - Forbidden: `403`


## Panel Access Gate Policy (Canonical)
- `ModPanel` login rule:
  - No separate `ModPanel` login page is allowed.
  - Access to `ModPanel/*` is granted only after `UserPanel/Login.app` succeeds and role/permission checks pass.
- `ControlPanel` exception rule:
  - `ControlPanel` must include an explicit admin-rights confirmation login page.
  - Canonical page: `ControlPanel/Login.app`.
  - Purpose: re-authenticate and confirm `Administrator`/`Owner` rights before entering `ControlPanel/*`.
  - If credentials are valid but role is not `Administrator` or `Owner`, response is `403 Forbidden`.

### Page 10: `ControlPanel/Login.app`
- Method: `GET`, `POST`
- Goal: confirm administrator rights before `ControlPanel/*` access.
- Actor: authenticated user requiring privileged area entry or session re-auth.
- Input fields and validation (POST):
  - `User.E-Mail` (required): valid email format, lowercase normalize.
  - `PassWord` (required): string, min `8`.
  - `csrf_token` (required for web form POST): valid CSRF token.
- Access decision rule:
  - Credentials must be valid, and resolved role must be `Administrator` or `Owner`.
  - Valid credentials with insufficient role => `403 Forbidden`.
- Output view data (minimum):
  - `User.Id`, `User.Role`, `request_id`, control-panel access grant flag
- Response rules:
  - Success: `200`
  - Invalid credentials: `401`
  - Role not allowed: `403`
    - User message (required): `You are logged in, but your account does not have permission to access Control Panel.`
    - Error code (recommended): `AUTH_ROLE_INSUFFICIENT_FOR_CONTROLPANEL`
    - Security note: message must not disclose internal permission map details.
  - Lockout: `423`

## Universal Standard Fields (Cross-App Comparable)
- Purpose:
  - Ensure every page uses comparable baseline fields used in common applications.
- Baseline Field Groups:
  - Identity: `Id`
  - Human label: `Name` (or nearest equivalent, e.g. `UserName`)
  - State: `Status`
  - Time: `CreatedAt`, `UpdatedAt` (when available)
  - Actor trace: `CreatedBy`, `UpdatedBy` (staff/control pages where available)
  - Response meta: `success`, `code`, `message`, `request_id`, `timestamp`
- Rule:
  - If a page does not own a field domain (example: no `CreatedAt` in login response), omit it but keep remaining baseline groups.

### Standard Fields By Page

#### `UserPanel/Register.app`
- Standard input fields:
  - `UserName`, `E-Mail`, `PassWord`
- Standard output fields:
  - `Id`, `UserName`, `E-Mail`, `Status`, `CreatedAt`, `request_id`, `timestamp`

#### `UserPanel/Validate.app`
- Standard input fields:
  - `User_Id`, `Link_Id`, `Token`
- Standard output fields:
  - `Id`, `Status`, `ValidateAt`, `request_id`, `timestamp`

#### `UserPanel/Login.app`
- Standard input fields:
  - `E-Mail`, `PassWord`
- Standard output fields:
  - `Id`, `UserName`, `Role`, `Status`, `LastLoginAt`, `request_id`, `timestamp`

#### `UserPanel/AutoLogin.app`
- Standard input fields:
  - `User_Id`, `Link_Id`, `Token`
- Standard output fields:
  - `Id`, `UserName`, `Role`, `Status`, `request_id`, `timestamp`

#### `UserPanel/Recovery.app`
- Standard input fields:
  - `E-Mail` (request mode) or `User_Id`, `Link_Id`, `Token` (confirm mode)
- Standard output fields:
  - `Id` (when resolvable), `Status`, `request_id`, `timestamp`

#### `UserPanel/Logout.app`
- Standard input fields:
  - `csrf_token` + active session context
- Standard output fields:
  - `Id`, `Status`, `request_id`, `timestamp`

#### `UserPanel/ProFile.app`
- Standard input fields (write):
  - `Name`, `SurName`, `Phone`, `Locale`, `TimeZone`, `AvatarUrl`
- Standard output fields:
  - `Id`, `UserName`, `E-Mail`, `Name`, `SurName`, `Status`, `UpdatedAt`, `request_id`, `timestamp`

#### `UserPanel/User/Address.app`
- Standard input fields (write):
  - `HouseNumber`, `StreetName`, `City`, `CountryCode`, `PostCode`, `Primary`
- Standard output fields:
  - `Id`, `Name` (address label if present), `Status`, `UpdatedAt`, `request_id`, `timestamp`

#### `UserPanel/User/Access.app`
- Standard input fields:
  - `action`, `session_target_id`, `current_password` (when required)
- Standard output fields:
  - `Id`, `Role`, `Status`, `UpdatedAt`, `request_id`, `timestamp`

#### `ControlPanel/Login.app`
- Standard input fields:
  - `E-Mail`, `PassWord`, `csrf_token`
- Standard output fields:
  - `Id`, `Role`, `Status`, `request_id`, `timestamp`, `access_grant`

## Message Catalog (Canonical)
- Format rule:
  - Every response SHOULD include `code`, `message`, and optional `description` for UI display.
  - Messages must be user-safe and must not leak internal security details.

### 1) `UserPanel/Register.app`
- Success (`201`):
  - Code: `AUTH_REGISTER_SUCCESS`
  - Message: `Account created successfully.`
  - Description: `Your account was created. Check your email to validate your account.`
- Error (`422`):
  - Code: `AUTH_REGISTER_VALIDATION_FAILED`
  - Message: `Please correct the highlighted fields.`
  - Description: `One or more registration fields are invalid or missing.`
- Error (`409`):
  - Code: `AUTH_REGISTER_DUPLICATE`
  - Message: `Account already exists.`
  - Description: `This email or username is already in use.`

### 2) `UserPanel/Validate.app`
- Success (`200`):
  - Code: `AUTH_VALIDATE_SUCCESS`
  - Message: `Account validated successfully.`
  - Description: `Your account is now active.`
- Error (`400`):
  - Code: `AUTH_VALIDATE_INVALID_TOKEN`
  - Message: `Validation link is invalid.`
  - Description: `The validation token is not valid for this account.`
- Error (`410`):
  - Code: `AUTH_VALIDATE_TOKEN_EXPIRED`
  - Message: `Validation link has expired.`
  - Description: `Request a new validation link and try again.`
- Error (`423`):
  - Code: `AUTH_VALIDATE_LOCKED`
  - Message: `Validation is temporarily locked.`
  - Description: `Too many attempts were detected. Try again later.`

### 3) `UserPanel/Login.app`
- Success (`200`):
  - Code: `AUTH_LOGIN_SUCCESS`
  - Message: `Login successful.`
  - Description: `Welcome back. Your session is now active.`
- Error (`401`):
  - Code: `AUTH_INVALID_CREDENTIALS`
  - Message: `Invalid email or password.`
  - Description: `Check your credentials and try again.`
- Error (`403`):
  - Code: `AUTH_ACCOUNT_NOT_ALLOWED`
  - Message: `Your account cannot sign in right now.`
  - Description: `Your account is inactive, suspended, or blocked.`
- Error (`423`):
  - Code: `AUTH_LOGIN_LOCKED`
  - Message: `Login is temporarily locked.`
  - Description: `Too many failed attempts were detected. Try again later.`

### 4) `UserPanel/AutoLogin.app`
- Success (`200`):
  - Code: `AUTH_AUTOLOGIN_SUCCESS`
  - Message: `Automatic login successful.`
  - Description: `Your saved login link was accepted.`
- Error (`401`):
  - Code: `AUTH_AUTOLOGIN_INVALID`
  - Message: `Automatic login failed.`
  - Description: `The auto-login credentials are invalid.`
- Error (`410`):
  - Code: `AUTH_AUTOLOGIN_EXPIRED`
  - Message: `Automatic login link expired.`
  - Description: `Please sign in manually.`
- Error (`423`):
  - Code: `AUTH_AUTOLOGIN_LOCKED`
  - Message: `Automatic login is temporarily locked.`
  - Description: `Too many attempts were detected. Try again later.`

### 5) `UserPanel/Recovery.app`
- Success (`202`):
  - Code: `AUTH_RECOVERY_REQUEST_ACCEPTED`
  - Message: `Recovery request accepted.`
  - Description: `If the account exists, recovery instructions were sent.`
- Error (`400`):
  - Code: `AUTH_RECOVERY_INVALID_TOKEN`
  - Message: `Recovery token is invalid.`
  - Description: `The provided recovery token cannot be verified.`
- Error (`410`):
  - Code: `AUTH_RECOVERY_TOKEN_EXPIRED`
  - Message: `Recovery token has expired.`
  - Description: `Request a new recovery link and try again.`
- Error (`423`):
  - Code: `AUTH_RECOVERY_LOCKED`
  - Message: `Recovery is temporarily locked.`
  - Description: `Too many attempts were detected. Try again later.`

### 6) Panel Access / Bypass Messages
- Rule:
  - Any direct URL bypass attempt to `ModPanel/*` or `ControlPanel/*` MUST return explicit access feedback with proper status code.

#### `ModPanel/*` Access Results
- Guest (`401`):
  - Code: `MODPANEL_AUTH_REQUIRED`
  - Message: `Please sign in to continue.`
  - Description: `You must be logged in to access moderator pages.`
- User/Author insufficient role (`403`):
  - Code: `MODPANEL_ROLE_INSUFFICIENT`
  - Message: `You do not have permission to access this page.`
  - Description: `Moderator or higher role is required.`
- Moderator/Administrator/Owner success (`200`):
  - Code: `MODPANEL_ACCESS_GRANTED`
  - Message: `Access granted.`
  - Description: `Your role has permission for this moderator page.`

#### `ControlPanel/*` Access Results
- Guest (`401`):
  - Code: `CONTROLPANEL_AUTH_REQUIRED`
  - Message: `Please sign in to continue.`
  - Description: `You must be logged in to access control panel pages.`
- Authenticated but no admin confirmation (`401` or policy-defined `403`):
  - Code: `CONTROLPANEL_CONFIRMATION_REQUIRED`
  - Message: `Please confirm your administrator access.`
  - Description: `Enter your credentials in Control Panel Login to continue.`
- Insufficient role (`403`):
  - Code: `AUTH_ROLE_INSUFFICIENT_FOR_CONTROLPANEL`
  - Message: `You are logged in, but your account does not have permission to access Control Panel.`
  - Description: `Administrator or Owner role is required.`
- Administrator/Owner confirmed (`200`):
  - Code: `CONTROLPANEL_ACCESS_GRANTED`
  - Message: `Access granted.`
  - Description: `Administrator rights confirmed for control panel access.`

## Next Chunk
- Define `ModPanel/*` and `ControlPanel/*` user-management pages with role/status/audit attributes.
