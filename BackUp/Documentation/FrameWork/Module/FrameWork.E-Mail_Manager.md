# FrameWork.EMailManager

## Module Specification
E-Mail Manager Description:

- Core Purpose: Manage application email delivery, templates, queues, and policy controls for all modules.
- Main Responsibilities:
  - Centralize outbound email sending rules and provider configuration usage.
  - Manage email templates for validation, recovery, notifications, and administrative actions.
  - Enforce send-rate safeguards and delivery retry logic.
  - Provide audit tracking for email dispatch and failures.
- Core Components:
  - `EmailDispatchService`
  - `EmailTemplateService`
  - `EmailQueueService`
  - `EmailAuditService`
- Expected Storage:
  - `email_templates` table: template key, subject, body, locale, active status, timestamps
  - `email_queue` table: recipient, template key, payload, status, attempts, scheduled/send timestamps
  - `email_audit` table: actor, recipient, template key, result, provider response, timestamp
- Public Interfaces:
  - CLI (localhost/dev): `php spark email:test`, `email:queue:run`, `email:queue:retry`
  - Web/Admin (external host fallback):
    - `ModPanel/User/E-Mail.app` (moderator level, scoped user email actions)
    - `ControlPanel/Users/E-Mail.app` (administrator level, full email manager access)
    - Additional email manager pages for queue/templates
  - Service methods: `sendTemplate()`, `queueTemplate()`, `retryFailed()`, `previewTemplate()`
- Security Rules:
  - Mask sensitive tokens in logs and audit payloads.
  - Restrict bulk email actions to `Administrator`/`Owner`.
  - Enforce template validation before activation/sending.
- E-Mail Manager Attribute Priority Map:
  - Layer 1: Core Identity Attributes (base required)
    - Template keys:
      - `user.validate`
      - `user.recovery`
      - `user.lockout`
      - `user.autologin`
      - `admin.alert`
      - `bulk.notice`
    - Queue status keys:
      - `pending`, `processing`, `sent`, `failed`, `dead`
    - Delivery result keys:
      - `accepted`, `delivered`, `bounced`, `rejected`, `deferred`
    - Event keys:
      - `email.send.requested`
      - `email.send.success`
      - `email.send.failed`
      - `email.queue.retry`
      - `email.template.updated`
  - Layer 2: Security Attributes (next required)
    - Permission keys:
      - `email.template.view`
      - `email.template.edit`
      - `email.send.single`
      - `email.send.bulk`
      - `email.queue.manage`
      - `email.audit.view`
    - Access defaults:
      - `Administrator`/`Owner`: full email manager permissions.
      - `Moderator`: read-limited (`email.audit.view`) only if explicitly granted.
      - Undefined permission: deny by default.
    - Sensitive handling flags:
      - `mask_tokens_in_logs = true`
      - `mask_recipient_in_audit = partial`
      - `require_template_validation = true`
      - `require_audit_for_send = true`
  - Layer 3: Data Integrity Attributes (exact schema required)
    - `email_templates` (required):
      - Purpose: canonical template definitions and versioned activation state.
      - Columns:
        - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
        - `template_key` (VARCHAR 120, NOT NULL)
        - `locale` (VARCHAR 16, NOT NULL DEFAULT `en`)
        - `subject` (VARCHAR 255, NOT NULL)
        - `body_html` (LONGTEXT NULL)
        - `body_text` (LONGTEXT NOT NULL)
        - `required_variables_json` (JSON NOT NULL)
        - `version_no` (INT UNSIGNED NOT NULL DEFAULT 1)
        - `is_active` (TINYINT(1) NOT NULL DEFAULT 0)
        - `is_system` (TINYINT(1) NOT NULL DEFAULT 0)
        - `created_by` (BIGINT UNSIGNED NULL, FK -> `users.id`)
        - `updated_by` (BIGINT UNSIGNED NULL, FK -> `users.id`)
        - `created_at` (DATETIME NOT NULL)
        - `updated_at` (DATETIME NOT NULL)
      - Indexes/Constraints:
        - Unique (`template_key`, `locale`, `version_no`)
        - Index (`template_key`, `locale`, `is_active`)
        - Check: at least one of `body_html`/`body_text` must be non-empty
    - `email_queue` (required):
      - Purpose: queued outbound messages with delivery lifecycle state.
      - Columns:
        - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
        - `message_uuid` (CHAR 36, NOT NULL, UNIQUE)
        - `template_key` (VARCHAR 120, NOT NULL)
        - `template_version_no` (INT UNSIGNED NOT NULL)
        - `recipient_email` (VARCHAR 190, NOT NULL)
        - `recipient_name` (VARCHAR 160 NULL)
        - `recipient_user_id` (BIGINT UNSIGNED NULL, FK -> `users.id`)
        - `sender_email` (VARCHAR 190, NOT NULL)
        - `sender_name` (VARCHAR 160 NOT NULL)
        - `sender_user_id` (BIGINT UNSIGNED NULL, FK -> `users.id`)
        - `website_name` (VARCHAR 160 NOT NULL)
        - `website_domain` (VARCHAR 190 NOT NULL)
        - `signature_name` (VARCHAR 160 NULL)
        - `signature_role` (VARCHAR 120 NULL)
        - `payload_json` (JSON NOT NULL)
        - `status` (ENUM `pending`,`processing`,`sent`,`failed`,`dead` NOT NULL DEFAULT `pending`)
        - `result_key` (ENUM `accepted`,`delivered`,`bounced`,`rejected`,`deferred` NULL)
        - `attempt_count` (INT UNSIGNED NOT NULL DEFAULT 0)
        - `max_attempts` (INT UNSIGNED NOT NULL DEFAULT 5)
        - `scheduled_at` (DATETIME NOT NULL)
        - `last_attempt_at` (DATETIME NULL)
        - `sent_at` (DATETIME NULL)
        - `last_error_code` (VARCHAR 80 NULL)
        - `last_error_message` (VARCHAR 255 NULL)
        - `locked_by` (VARCHAR 64 NULL)
        - `locked_at` (DATETIME NULL)
        - `created_at` (DATETIME NOT NULL)
        - `updated_at` (DATETIME NOT NULL)
      - Indexes/Constraints:
        - Index (`status`, `scheduled_at`)
        - Index (`recipient_email`, `created_at`)
        - Index (`template_key`, `template_version_no`)
        - Index (`sender_user_id`, `created_at`)
        - Check: `attempt_count <= max_attempts`
    - `email_audit` (required):
      - Purpose: immutable audit trail of send and management actions.
      - Columns:
        - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
        - `event_uuid` (CHAR 36, NOT NULL, UNIQUE)
        - `event_key` (VARCHAR 120, NOT NULL)
        - `message_uuid` (CHAR 36 NULL)
        - `actor_user_id` (BIGINT UNSIGNED NULL, FK -> `users.id`)
        - `target_user_id` (BIGINT UNSIGNED NULL, FK -> `users.id`)
        - `recipient_email_hash` (CHAR 64 NOT NULL)
        - `recipient_domain` (VARCHAR 120 NULL)
        - `sender_email` (VARCHAR 190 NULL)
        - `sender_user_id` (BIGINT UNSIGNED NULL, FK -> `users.id`)
        - `website_domain` (VARCHAR 190 NULL)
        - `signature_name` (VARCHAR 160 NULL)
        - `template_key` (VARCHAR 120 NULL)
        - `template_version_no` (INT UNSIGNED NULL)
        - `status` (VARCHAR 32 NOT NULL)
        - `reason_code` (VARCHAR 80 NULL)
        - `provider_response_code` (VARCHAR 80 NULL)
        - `ip_address` (VARCHAR 45 NULL)
        - `user_agent` (VARCHAR 255 NULL)
        - `request_id` (VARCHAR 64 NULL)
        - `metadata_json` (JSON NULL)
        - `created_at` (DATETIME NOT NULL)
      - Indexes/Constraints:
        - Index (`event_key`, `created_at`)
        - Index (`actor_user_id`, `created_at`)
        - Index (`message_uuid`)
        - Index (`template_key`, `template_version_no`)
    - `email_suppression` (optional):
      - Purpose: block sending to addresses with hard bounce/complaint/unsubscribe states.
      - Columns:
        - `id` (BIGINT UNSIGNED, PK, AUTO_INCREMENT)
        - `email` (VARCHAR 190 NOT NULL, UNIQUE)
        - `email_hash` (CHAR 64 NOT NULL, UNIQUE)
        - `reason` (ENUM `unsubscribe`,`hard_bounce`,`complaint`,`manual_block` NOT NULL)
        - `source` (VARCHAR 80 NOT NULL)
        - `is_active` (TINYINT(1) NOT NULL DEFAULT 1)
        - `expires_at` (DATETIME NULL)
        - `created_by` (BIGINT UNSIGNED NULL, FK -> `users.id`)
        - `created_at` (DATETIME NOT NULL)
        - `updated_at` (DATETIME NOT NULL)
      - Indexes/Constraints:
        - Index (`reason`, `is_active`)
        - Index (`expires_at`)
    - Common Integrity Rules:
      - All email addresses must be normalized to lowercase before write.
      - `recipient_email` and `sender_email` must pass email format validation.
      - Signing identity must be explicit (`sender_user_id` or `signature_name` present).
      - Website identity must be explicit on outbound records (`website_name`, `website_domain`).
      - Deleting users must not break audit integrity; use nullable FKs or restricted delete policy.

### Layer 3 Logical Fields (Quick View)
| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Email.Template.Id` | `email_templates.id` | PK, auto increment |
| `Email.Template.Key` | `email_templates.template_key` | `VARCHAR(120)` |
| `Email.Template.Locale` | `email_templates.locale` | `VARCHAR(16)` |
| `Email.Template.Subject` | `email_templates.subject` | `VARCHAR(255)` |
| `Email.Template.BodyHtml` | `email_templates.body_html` | longtext, nullable |
| `Email.Template.BodyText` | `email_templates.body_text` | longtext |
| `Email.Template.RequiredVars` | `email_templates.required_variables_json` | JSON |
| `Email.Template.Version` | `email_templates.version_no` | unsigned int |
| `Email.Template.Active` | `email_templates.is_active` | bool |
| `Email.Template.System` | `email_templates.is_system` | bool |
| `Email.Template.CreatedBy` | `email_templates.created_by` | FK -> `users.id`, nullable |
| `Email.Template.UpdatedBy` | `email_templates.updated_by` | FK -> `users.id`, nullable |
| `Email.Template.CreatedAt` | `email_templates.created_at` | datetime |
| `Email.Template.UpdatedAt` | `email_templates.updated_at` | datetime |
| `Email.Template.Constraint` | `unique(template_key, locale, version_no)` | template version uniqueness |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Email.Queue.Id` | `email_queue.id` | PK, auto increment |
| `Email.Queue.MessageId` | `email_queue.message_uuid` | UUID, unique |
| `Email.Queue.TemplateKey` | `email_queue.template_key` | `VARCHAR(120)` |
| `Email.Queue.TemplateVersion` | `email_queue.template_version_no` | unsigned int |
| `Email.Queue.RecipientEmail` | `email_queue.recipient_email` | `VARCHAR(190)` |
| `Email.Queue.RecipientName` | `email_queue.recipient_name` | `VARCHAR(160)`, nullable |
| `Email.Queue.RecipientUserId` | `email_queue.recipient_user_id` | FK -> `users.id`, nullable |
| `Email.Queue.SenderEmail` | `email_queue.sender_email` | `VARCHAR(190)` |
| `Email.Queue.SenderName` | `email_queue.sender_name` | `VARCHAR(160)` |
| `Email.Queue.SenderUserId` | `email_queue.sender_user_id` | FK -> `users.id`, nullable |
| `Email.Queue.WebSiteName` | `email_queue.website_name` | `VARCHAR(160)` |
| `Email.Queue.WebSiteDomain` | `email_queue.website_domain` | `VARCHAR(190)` |
| `Email.Queue.SignatureName` | `email_queue.signature_name` | `VARCHAR(160)`, nullable |
| `Email.Queue.SignatureRole` | `email_queue.signature_role` | `VARCHAR(120)`, nullable |
| `Email.Queue.Payload` | `email_queue.payload_json` | JSON |
| `Email.Queue.Status` | `email_queue.status` | `pending/processing/sent/failed/dead` |
| `Email.Queue.Result` | `email_queue.result_key` | `accepted/delivered/bounced/rejected/deferred`, nullable |
| `Email.Queue.AttemptCount` | `email_queue.attempt_count` | unsigned int |
| `Email.Queue.MaxAttempts` | `email_queue.max_attempts` | unsigned int |
| `Email.Queue.ScheduledAt` | `email_queue.scheduled_at` | datetime |
| `Email.Queue.LastAttemptAt` | `email_queue.last_attempt_at` | datetime, nullable |
| `Email.Queue.SentAt` | `email_queue.sent_at` | datetime, nullable |
| `Email.Queue.LastErrorCode` | `email_queue.last_error_code` | `VARCHAR(80)`, nullable |
| `Email.Queue.LastErrorMessage` | `email_queue.last_error_message` | `VARCHAR(255)`, nullable |
| `Email.Queue.LockedBy` | `email_queue.locked_by` | `VARCHAR(64)`, nullable |
| `Email.Queue.LockedAt` | `email_queue.locked_at` | datetime, nullable |
| `Email.Queue.CreatedAt` | `email_queue.created_at` | datetime |
| `Email.Queue.UpdatedAt` | `email_queue.updated_at` | datetime |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Email.Audit.Id` | `email_audit.id` | PK, auto increment |
| `Email.Audit.EventId` | `email_audit.event_uuid` | UUID, unique |
| `Email.Audit.EventKey` | `email_audit.event_key` | `VARCHAR(120)` |
| `Email.Audit.MessageId` | `email_audit.message_uuid` | UUID, nullable |
| `Email.Audit.ActorUserId` | `email_audit.actor_user_id` | FK -> `users.id`, nullable |
| `Email.Audit.TargetUserId` | `email_audit.target_user_id` | FK -> `users.id`, nullable |
| `Email.Audit.RecipientEmailHash` | `email_audit.recipient_email_hash` | `CHAR(64)` |
| `Email.Audit.RecipientDomain` | `email_audit.recipient_domain` | `VARCHAR(120)`, nullable |
| `Email.Audit.SenderEmail` | `email_audit.sender_email` | `VARCHAR(190)`, nullable |
| `Email.Audit.SenderUserId` | `email_audit.sender_user_id` | FK -> `users.id`, nullable |
| `Email.Audit.WebSiteDomain` | `email_audit.website_domain` | `VARCHAR(190)`, nullable |
| `Email.Audit.SignatureName` | `email_audit.signature_name` | `VARCHAR(160)`, nullable |
| `Email.Audit.TemplateKey` | `email_audit.template_key` | `VARCHAR(120)`, nullable |
| `Email.Audit.TemplateVersion` | `email_audit.template_version_no` | unsigned int, nullable |
| `Email.Audit.Status` | `email_audit.status` | `VARCHAR(32)` |
| `Email.Audit.Reason` | `email_audit.reason_code` | `VARCHAR(80)`, nullable |
| `Email.Audit.ProviderCode` | `email_audit.provider_response_code` | `VARCHAR(80)`, nullable |
| `Email.Audit.IpAddress` | `email_audit.ip_address` | `VARCHAR(45)`, nullable |
| `Email.Audit.UserAgent` | `email_audit.user_agent` | `VARCHAR(255)`, nullable |
| `Email.Audit.RequestId` | `email_audit.request_id` | `VARCHAR(64)`, nullable |
| `Email.Audit.Meta` | `email_audit.metadata_json` | JSON, nullable |
| `Email.Audit.CreatedAt` | `email_audit.created_at` | datetime |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `Email.Suppression.Id` | `email_suppression.id` | PK, auto increment |
| `Email.Suppression.Email` | `email_suppression.email` | `VARCHAR(190)`, unique |
| `Email.Suppression.EmailHash` | `email_suppression.email_hash` | `CHAR(64)`, unique |
| `Email.Suppression.Reason` | `email_suppression.reason` | `unsubscribe/hard_bounce/complaint/manual_block` |
| `Email.Suppression.Source` | `email_suppression.source` | `VARCHAR(80)` |
| `Email.Suppression.Active` | `email_suppression.is_active` | bool |
| `Email.Suppression.ExpiresAt` | `email_suppression.expires_at` | datetime, nullable |
| `Email.Suppression.CreatedBy` | `email_suppression.created_by` | FK -> `users.id`, nullable |
| `Email.Suppression.CreatedAt` | `email_suppression.created_at` | datetime |
| `Email.Suppression.UpdatedAt` | `email_suppression.updated_at` | datetime |
  - Layer 4: Processing Rules (required before module implementation)
    - Queue selection rules:
      - Worker pulls only records where `status = pending` and `scheduled_at <= now()`.
      - Lock record with `locked_by` + `locked_at` before provider call.
      - If lock is older than 10 minutes, lock can be reclaimed by another worker.
    - Retry/backoff rules:
      - Default `max_attempts = 5`.
      - Attempt 1: immediate.
      - Attempt 2: +5 minutes.
      - Attempt 3: +15 minutes.
      - Attempt 4: +60 minutes.
      - Attempt 5: +360 minutes.
      - After max attempts: set `status = dead` and create `email.send.failed` audit.
    - Idempotency rules:
      - `message_uuid` is immutable and unique.
      - Duplicate dispatch for same `message_uuid` must be ignored and logged.
      - Worker must never send when row is already `sent` or `dead`.
    - Template rendering rules:
      - Resolve active template by `template_key`, `locale`, latest active `version_no`.
      - If missing variable from `required_variables_json`, reject enqueue with validation error.
      - Render both `body_text` and `body_html` when both are defined.
    - Suppression rules:
      - Before send, check `email_suppression` active state.
      - If suppressed, mark queue record `failed` with reason `suppressed` and do not call provider.
  - Layer 5: Operational Controls (required for production)
    - Runtime compatibility:
      - Localhost/dev MAY run worker by CLI (`php spark email:queue:run`).
      - External hosting without CLI MUST use web-admin trigger endpoint restricted to `ControlPanel` and protected by role + CSRF + audit.
    - Throughput and limits:
      - Default batch size: 100 queued emails per execution.
      - Per-domain throttle: 30 emails/minute/domain.
      - Global throttle: 300 emails/minute/application.
      - Bulk sends must be enqueued; no direct synchronous provider calls for bulk.
    - Health and alerting:
      - Alert if `dead` emails in last 60 minutes > 20.
      - Alert if provider timeout ratio > 10% in last 15 minutes.
      - Alert if queue oldest `pending` age > 30 minutes.
    - Configuration keys (minimum):
      - `email.default_from_email`
      - `email.default_from_name`
      - `email.default_signature_name`
      - `email.default_signature_role`
      - `email.queue.batch_size`
      - `email.queue.max_attempts`
      - `email.queue.lock_timeout_seconds`
      - `email.rate.per_domain_per_minute`
      - `email.rate.global_per_minute`
      - `email.provider.timeout_seconds`
    - Disaster controls:
      - `email.sending_enabled` global kill switch.
      - `email.bulk_enabled` separate kill switch for campaigns.
      - Switch changes must create audit records.
  - Layer Progression Rule:
    - Complete Layer 1 before Layer 2.
    - Complete Layer 2 before Layer 3.
    - Complete Layer 3 before Layer 4.
    - Complete Layer 4 before Layer 5.
    - Later layers may extend but must not break keys/contracts defined in earlier layers.

## E-Mail Module Status
- Status: Complete
- Completed:
  - Layer 1 Core Identity Attributes
  - Layer 2 Security Attributes
  - Layer 3 Data Integrity Attributes
  - Layer 4 Processing Rules
  - Layer 5 Operational Controls
  - Route-to-permission matrix for all E-Mail endpoints
  - Response/Error contract (success/error schema + HTTP mapping)
- Remaining (optional hardening):
  - None

## Route-to-Permission Matrix (E-Mail)
| Route | Method | Action | Required Permission | Allowed Roles (Default) | Scope Rule |
|---|---|---|---|---|---|
| `ModPanel/User/E-Mail.app` | `GET` | View user email history | `email.audit.view` | `Moderator`, `Administrator`, `Owner` | Moderator can view users in assigned scope only |
| `ModPanel/User/E-Mail.app` | `POST` | Send email to one user | `email.send.single` | `Moderator`, `Administrator`, `Owner` | Moderator cannot send bulk |
| `ControlPanel/Users/E-Mail.app` | `GET` | View E-Mail manager dashboard | `email.audit.view` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/Users/E-Mail.app?Templates=Get` | `GET` | View templates | `email.template.view` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/Users/E-Mail.app?Templates=Post` | `POST` | Create or update template | `email.template.edit` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/Users/E-Mail.app?Queue=Get` | `GET` | View queue and dead letters | `email.queue.manage` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/Users/E-Mail.app?Queue=Post&Retry=True` | `POST` | Retry failed or dead queue entries | `email.queue.manage` | `Administrator`, `Owner` | Full scope |
| `ControlPanel/Users/E-Mail.app` | `POST` | Send bulk or campaign email | `email.send.bulk` | `Administrator`, `Owner` | Full scope |

### Enforcement Rules
- Deny by default if route is not mapped in this matrix.
- Deny if actor role is inactive.
- Deny if required permission key is missing from actor role.
- For `ModPanel/*`, enforce moderation scope filter before action.
- Log all denied actions in audit with event key `email.permission.denied`.

## Response/Error Contract (E-Mail)
### Standard JSON Response Envelope
- Success envelope:
  - `success` (bool) = `true`
  - `status` (int) = HTTP status code
  - `code` (string) = stable application result code
  - `message` (string) = human-readable summary
  - `request_id` (string) = request correlation id
  - `timestamp` (string, ISO-8601 UTC)
  - `data` (object) = response payload
  - `meta` (object) = paging/extra context (optional)
- Error envelope:
  - `success` (bool) = `false`
  - `status` (int) = HTTP status code
  - `code` (string) = stable application error code
  - `message` (string) = human-readable summary
  - `request_id` (string) = request correlation id
  - `timestamp` (string, ISO-8601 UTC)
  - `error` (object):
    - `type` (string): `validation`, `authorization`, `not_found`, `rate_limit`, `provider`, `server`
    - `details` (array of objects): `field`, `reason`

### Example: Success
```json
{
  "success": true,
  "status": 202,
  "code": "EMAIL_ENQUEUED",
  "message": "E-Mail request queued.",
  "request_id": "req_01JABCXYZ123",
  "timestamp": "2026-02-22T12:00:00Z",
  "data": {
    "message_uuid": "2ecfce95-5d69-4ec3-96d8-8f6a07f79c6e",
    "queue_status": "pending"
  },
  "meta": {}
}
```

### Example: Error
```json
{
  "success": false,
  "status": 403,
  "code": "EMAIL_PERMISSION_DENIED",
  "message": "You do not have permission for this action.",
  "request_id": "req_01JABCXYZ123",
  "timestamp": "2026-02-22T12:00:00Z",
  "error": {
    "type": "authorization",
    "details": [
      {
        "field": "permission",
        "reason": "missing: email.send.bulk"
      }
    ]
  }
}
```

### Example: Validation Error
```json
{
  "success": false,
  "status": 422,
  "code": "EMAIL_VALIDATION_FAILED",
  "message": "Input validation failed.",
  "request_id": "req_01JABCXYZ123",
  "timestamp": "2026-02-22T12:00:00Z",
  "error": {
    "type": "validation",
    "details": [
      {
        "field": "template_key",
        "reason": "required"
      }
    ]
  }
}
```

### HTTP Status Mapping (Per E-Mail Endpoint)
- `GET ModPanel/User/E-Mail.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `500 Internal Server Error`
- `POST ModPanel/User/E-Mail.app`:
  - `202 Accepted`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `429 Too Many Requests`, `500 Internal Server Error`
- `GET ControlPanel/Users/E-Mail.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `GET ControlPanel/Users/E-Mail.app?Templates=Get`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `POST ControlPanel/Users/E-Mail.app?Templates=Post`:
  - `201 Created`, `200 OK`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`
- `GET ControlPanel/Users/E-Mail.app?Queue=Get`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `POST ControlPanel/Users/E-Mail.app?Queue=Post&Retry=True`:
  - `202 Accepted`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`
- `POST ControlPanel/Users/E-Mail.app`:
  - `202 Accepted`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `413 Payload Too Large`, `422 Unprocessable Entity`, `429 Too Many Requests`, `500 Internal Server Error`

### Error Code Catalog (Minimum)
- `EMAIL_PERMISSION_DENIED`
- `EMAIL_SCOPE_DENIED`
- `EMAIL_VALIDATION_FAILED`
- `EMAIL_TEMPLATE_NOT_FOUND`
- `EMAIL_TEMPLATE_INACTIVE`
- `EMAIL_QUEUE_NOT_FOUND`
- `EMAIL_RETRY_NOT_ALLOWED`
- `EMAIL_RATE_LIMITED`
- `EMAIL_PROVIDER_TIMEOUT`
- `EMAIL_PROVIDER_REJECTED`
- `EMAIL_INTERNAL_ERROR`

## Provider Failover Policy and Signed Webhook Event Intake
### Provider Failover Policy
- Provider priority:
  - `primary_provider = provider_a`
  - `secondary_provider = provider_b`
  - `tertiary_provider = provider_c` (optional)
- Failover triggers:
  - Timeout (`EMAIL_PROVIDER_TIMEOUT`)
  - Connection failure
  - Provider `5xx` response
- Non-failover errors:
  - Provider `4xx` invalid recipient/content errors must not trigger provider switch.
  - These errors are stored as failed delivery attempts.
- Circuit breaker:
  - Open circuit when failures >= `5` inside `60` seconds for one provider.
  - Cooldown: `300` seconds.
  - Half-open trial: `3` sends; if all succeed, close circuit.
- Queue behavior:
  - Keep original `message_uuid` during failover attempts.
  - Increment `attempt_count` per attempt.
  - Record provider switch in `email_audit` using `event_key = email.provider.failover`.
  - Stop at `max_attempts`; set queue `status = dead`.

### Provider Selection Rules
- Default selection uses highest-priority healthy provider.
- If a provider circuit is open, skip it and try next healthy provider.
- Bulk sending must use queue and this same provider policy.

### Signed Webhook Event Intake
- Webhook endpoint:
  - `POST ControlPanel/Users/E-Mail.app?Webhook=Post`
- Required headers:
  - `X-Email-Provider`
  - `X-Webhook-Timestamp`
  - `X-Webhook-Signature`
  - `X-Webhook-Event-Id`
- Signature verification:
  - Algorithm: `HMAC-SHA256`
  - Signed content: `{timestamp}.{raw_body}`
  - Secret source: `email.webhook.{provider}.secret`
  - Reject when timestamp drift > `300` seconds.
- Replay protection:
  - Store `{provider,event_id}` fingerprint for `86400` seconds.
  - Duplicate webhook event must be idempotent and must not create duplicate queue/audit updates.
- Event mapping:
  - `delivered` -> queue `result_key = delivered`, audit `email.send.success`
  - `bounced` -> queue `result_key = bounced`, audit `email.send.failed`
  - `rejected` -> queue `result_key = rejected`, audit `email.send.failed`
  - `deferred` -> queue `result_key = deferred`, audit `email.queue.retry`
  - `complaint`/`unsubscribe` -> insert or update `email_suppression`
- Webhook response contract:
  - `200 OK`: processed
  - `202 Accepted`: duplicate/idempotent already processed
  - `400 Bad Request`: invalid payload
  - `401 Unauthorized`: invalid signature
  - `500 Internal Server Error`: unexpected processing failure

### Required Config Keys
- `email.providers.order`
- `email.provider.{name}.enabled`
- `email.provider.{name}.timeout_seconds`
- `email.provider.{name}.circuit.failure_threshold`
- `email.provider.{name}.circuit.window_seconds`
- `email.provider.{name}.circuit.cooldown_seconds`
- `email.webhook.{provider}.secret`
- `email.webhook.max_timestamp_drift_seconds`
- `email.webhook.replay_ttl_seconds`

## Inherited Common Attributes (E-Mail Manager)
- This module inherits shared standards from `FrameWork.md`:
  - `Common Layer Standards (L1-L5)`
  - `Common Security Standards (Shared Attributes)`
