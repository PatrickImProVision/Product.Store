# FrameWork.ContentManager-Personal

## Module Specification
Content Manager - Personal Description:

- Core Purpose: Manage user-owned personal content (private drafts, personal posts, user-specific resources).
- Minimum Role:
  - Personal content/comments minimum role is `User` (or any higher role).
- Main Responsibilities:
  - Manage personal content CRUD scoped to owner or authorized role.
  - Manage per-user visibility states (`private`, `shared`, `public` where allowed).
  - Manage personal content revisions and restore workflow.
  - Enforce owner-first access control for personal content.
- Core Components:
  - `PersonalContentService`
  - `PersonalContentRepository`
  - `PersonalVisibilityService`
  - `PersonalRevisionService`
- Expected Storage:
  - `personal_contents` table: owner_user_id, title, content, visibility, status, timestamps
  - `personal_content_versions` table: content_id, version_no, snapshot, changed_by, changed_at
- Public Interfaces:
  - ControlPanel route (canonical): `ControlPanel/PersonalContentManager/Index.app`
  - Service methods: `createPersonalContent()`, `updatePersonalContent()`, `setVisibility()`, `restorePersonalVersion()`
- Security Rules:
  - Owner-only access by default.
  - Moderator/Administrator access only with explicit permission and audit trace.
  - Sanitize content and validate visibility transitions.

## Inherited Common Attributes (Content Manager - Personal)
- General Standards (shared baseline for Content Manager - Personal) are inherited from `FrameWork.md`:
  - `Common Layer Standards (L1-L5)`
  - `Common Security Standards (Shared Attributes)`

## Personal Content Module-Local Canonical Definitions (L1-L5)
### L1 Core Identity (Personal Content Local)
- Content state keys:
  - `draft`, `active`, `archived`, `deleted`
- Visibility keys:
  - `private`, `shared`, `public`
- Event keys:
  - `personal_content.created`
  - `personal_content.updated`
  - `personal_content.visibility.changed`
  - `personal_content.archived`
  - `personal_content.version.restored`

### L2 Security Controls (Personal Content Local)
- Permission keys:
  - `personal_content.view.own`
  - `personal_content.edit.own`
  - `personal_content.delete.own`
  - `personal_content.share.own`
  - `personal_content.view.any`
  - `personal_content.edit.any`
  - `personal_content.moderate`
  - `personal_content.version.view`
  - `personal_content.version.restore`
- Role defaults:
  - `Owner`, `Administrator`: full permissions
  - `Moderator`: `view.any` + `moderate` (no owner-only sensitive edits unless granted)
  - `Author`, `User`: own-content permissions only
  - `Guest`: no personal content permissions
- Three-Level Minimum Roles (Content Split Reference):
  - Public level (`Content Manager - Public`): minimum management role `Moderator`
  - Community level (`Content Manager - Community`): minimum participation role `User`
  - Personal level (this module): minimum owner role `User`

### L3 Data Integrity (Personal Content Local Canonical Schema)
- `personal_contents` (required):
  - `id` (CHAR(8), PK, Security-generated, regex `^[A-Z0-9]{8}$`)
  - `owner_user_id` (BIGINT UNSIGNED NOT NULL, FK -> `users.id`)
  - `title` (VARCHAR 190 NOT NULL)
  - `content_html` (LONGTEXT NULL)
  - `content_text` (LONGTEXT NOT NULL)
  - `visibility` (ENUM `private`,`shared`,`public` NOT NULL DEFAULT `private`)
  - `status` (ENUM `draft`,`active`,`archived`,`deleted` NOT NULL DEFAULT `draft`)
  - `shared_scope_json` (JSON NULL)
  - `created_at` (DATETIME NOT NULL)
  - `updated_at` (DATETIME NOT NULL)
  - `deleted_at` (DATETIME NULL)
  - Index: (`owner_user_id`,`status`,`visibility`)
  - Index: (`updated_at`)
- `personal_content_versions` (required):
  - `id` (CHAR(8), PK, Security-generated, regex `^[A-Z0-9]{8}$`)
  - `personal_content_id` (CHAR(8) NOT NULL, FK -> `personal_contents.id`)
  - `version_no` (INT UNSIGNED NOT NULL)
  - `snapshot_json` (JSON NOT NULL)
  - `changed_by` (BIGINT UNSIGNED NULL, FK -> `users.id`)
  - `change_note` (VARCHAR 255 NULL)
  - `created_at` (DATETIME NOT NULL)
  - Unique: (`personal_content_id`,`version_no`)
  - Index: (`created_at`)
- Optional `personal_content_shares`:
  - `id` (CHAR(8), PK, Security-generated, regex `^[A-Z0-9]{8}$`)
  - `personal_content_id` (CHAR(8) NOT NULL, FK -> `personal_contents.id`)
  - `shared_with_user_id` (BIGINT UNSIGNED NOT NULL, FK -> `users.id`)
  - `permission_level` (ENUM `view`,`comment`,`edit` NOT NULL DEFAULT `view`)
  - `created_at` (DATETIME NOT NULL)
  - Unique: (`personal_content_id`,`shared_with_user_id`)

### L3 Logical Fields (Quick View)
| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `PersonalContent.Id` | `personal_contents.id` | `CHAR(8)`, PK, Security-generated |
| `PersonalContent.OwnerUserId` | `personal_contents.owner_user_id` | FK -> `users.id` |
| `PersonalContent.Title` | `personal_contents.title` | `VARCHAR(190)` |
| `PersonalContent.Content.Html` | `personal_contents.content_html` | longtext, nullable |
| `PersonalContent.Content.Text` | `personal_contents.content_text` | longtext |
| `PersonalContent.Visibility` | `personal_contents.visibility` | `private/shared/public` |
| `PersonalContent.Status` | `personal_contents.status` | `draft/active/archived/deleted` |
| `PersonalContent.SharedScope` | `personal_contents.shared_scope_json` | JSON, nullable |
| `PersonalContent.CreatedAt` | `personal_contents.created_at` | datetime |
| `PersonalContent.UpdatedAt` | `personal_contents.updated_at` | datetime |
| `PersonalContent.DeletedAt` | `personal_contents.deleted_at` | datetime, nullable |
| `PersonalContent.Constraint` | `pk(id)` | canonical route identity by primary key |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `PersonalContentVersion.Id` | `personal_content_versions.id` | PK, auto increment |
| `PersonalContentVersion.ContentId` | `personal_content_versions.personal_content_id` | `CHAR(8)`, FK -> `personal_contents.id` |
| `PersonalContentVersion.No` | `personal_content_versions.version_no` | unsigned int |
| `PersonalContentVersion.Snapshot` | `personal_content_versions.snapshot_json` | JSON |
| `PersonalContentVersion.ChangedBy` | `personal_content_versions.changed_by` | FK -> `users.id`, nullable |
| `PersonalContentVersion.ChangeNote` | `personal_content_versions.change_note` | `VARCHAR(255)`, nullable |
| `PersonalContentVersion.CreatedAt` | `personal_content_versions.created_at` | datetime |
| `PersonalContentVersion.Constraint` | `unique(personal_content_id, version_no)` | version uniqueness per content |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `PersonalContentShare.Id` | `personal_content_shares.id` | PK, auto increment |
| `PersonalContentShare.ContentId` | `personal_content_shares.personal_content_id` | `CHAR(8)`, FK -> `personal_contents.id` |
| `PersonalContentShare.SharedWithUserId` | `personal_content_shares.shared_with_user_id` | FK -> `users.id` |
| `PersonalContentShare.PermissionLevel` | `personal_content_shares.permission_level` | `view/comment/edit` |
| `PersonalContentShare.CreatedAt` | `personal_content_shares.created_at` | datetime |
| `PersonalContentShare.Constraint` | `unique(personal_content_id, shared_with_user_id)` | one share row per user/content |

### L4 Behavior and Processing (Personal Content Local Canonical)
- Ownership-first access:
  - default access is owner-only
  - non-owner access requires explicit share/moderation/admin permission
- Visibility transition rules:
  - `private -> shared -> public` allowed when owner or authorized role confirms
  - `public -> private` allowed only if policy permits and link invalidation is handled
- Versioning rules:
  - each content mutation appends a version entry
  - restore creates a new version (history remains append-only)
- Deletion rules:
  - default soft delete (`status = deleted`, `deleted_at` set)
  - hard delete is restricted to elevated roles and policy checks

### L5 Operations and Runtime Controls (Personal Content Local Canonical)
- Runtime controls:
  - per-owner content query scope enforced on all personal-content reads
  - cache invalidation on content/visibility/share updates
- Operational guardrails:
  - visibility/share/restore/moderation actions require audit logging
  - moderation actions require reason metadata
- Required config keys:
  - `personal_content.default_visibility`
  - `personal_content.allow_public_visibility`
  - `personal_content.max_versions_per_item`
  - `personal_content.soft_delete_retention_days`
  - `personal_content.share_enabled`

## Route-to-Permission Matrix (Content Manager - Personal)
| Route | Method | Action | Required Permission | Allowed Roles (Default) | Scope Rule |
|---|---|---|---|---|---|
| `UserPanel/PersonalContent/Index.app` | `GET` | View own personal content list | `personal_content.view.own` | `User`, `Author`, `Moderator`, `Administrator`, `Owner` | Owner scope |
| `UserPanel/PersonalContent/Edit.app` | `POST` | Create/update own personal content | `personal_content.edit.own` | `User`, `Author`, `Moderator`, `Administrator`, `Owner` | Owner scope |
| `UserPanel/PersonalContent/Visibility.app` | `POST` | Update own visibility/share settings | `personal_content.share.own` | `User`, `Author`, `Moderator`, `Administrator`, `Owner` | Owner scope |
| `UserPanel/PersonalContent/Version.app` | `GET` | View own content versions | `personal_content.version.view` | `User`, `Author`, `Moderator`, `Administrator`, `Owner` | Owner scope |
| `UserPanel/PersonalContent/Version.app` | `POST` | Restore own content version | `personal_content.version.restore` | `User`, `Author`, `Moderator`, `Administrator`, `Owner` | Owner scope |
| `ControlPanel/PersonalContentManager/Index.app` | `GET` | View personal content manager dashboard | `personal_content.view.any` | `Moderator`, `Administrator`, `Owner` | Moderation/admin scope |
| `ControlPanel/PersonalContentManager/Moderate.app` | `POST` | Moderate flagged/policy-violating personal content | `personal_content.moderate` | `Moderator`, `Administrator`, `Owner` | Moderation/admin scope |

### Enforcement Rules (Content Manager - Personal)
- Deny by default if route is not mapped.
- Deny if role is inactive or permission key is missing.
- Non-owner personal content access requires explicit `*.any`/`moderate` permission.
- All moderation and visibility transitions must be audit logged.

## Response/Error Contract (Content Manager - Personal)
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
- `errors` (object/array)
- `status` (int, HTTP status)
- `timestamp` (ISO-8601 datetime)
- `request_id` (string)

### HTTP Status Mapping (Personal Content Endpoints)
- `GET UserPanel/PersonalContent/Index.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `POST UserPanel/PersonalContent/Edit.app`:
  - `200 OK`, `201 Created`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`
- `POST UserPanel/PersonalContent/Visibility.app`:
  - `200 OK`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`
- `GET UserPanel/PersonalContent/Version.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `500 Internal Server Error`
- `POST UserPanel/PersonalContent/Version.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `409 Conflict`, `500 Internal Server Error`
- `GET ControlPanel/PersonalContentManager/Index.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `POST ControlPanel/PersonalContentManager/Moderate.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`

### Error Code Catalog (Personal Content Minimum)
- `PERSONAL_CONTENT_PERMISSION_DENIED`
- `PERSONAL_CONTENT_NOT_FOUND`
- `PERSONAL_CONTENT_OWNER_SCOPE_DENIED`
- `PERSONAL_CONTENT_VISIBILITY_INVALID`
- `PERSONAL_CONTENT_SHARE_INVALID`
- `PERSONAL_CONTENT_VERSION_NOT_FOUND`
- `PERSONAL_CONTENT_RESTORE_NOT_ALLOWED`
- `PERSONAL_CONTENT_VALIDATION_FAILED`
- `PERSONAL_CONTENT_INTERNAL_ERROR`

### Examples (Future Reference)
#### Success Example
```json
{
  "success": true,
  "code": "PERSONAL_CONTENT_SAVE_SUCCESS",
  "message": "Personal content saved.",
  "data": {
    "content_id": 34,
    "status": "active",
    "visibility": "private"
  },
  "meta": {},
  "timestamp": "2026-02-23T15:30:00Z",
  "request_id": "req_personal_content_001"
}
```

#### Error Example
```json
{
  "success": false,
  "code": "PERSONAL_CONTENT_OWNER_SCOPE_DENIED",
  "message": "You cannot access this personal content.",
  "errors": [
    {
      "field": "content_id",
      "reason": "owner mismatch"
    }
  ],
  "status": 403,
  "timestamp": "2026-02-23T15:30:00Z",
  "request_id": "req_personal_content_002"
}
```

## Content Manager - Personal Status
- Status: Complete
- Completed:
  - Module-local canonical definitions (L1-L5)
  - Route-to-permission matrix by role
  - Response/error contract and HTTP mapping
  - Future reference examples
- Remaining:
  - None
