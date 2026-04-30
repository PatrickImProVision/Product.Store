# FrameWork.ContentManager-Community

## Module Specification
Content Manager - Community Description:

- Core Purpose: Manage shared community content that can be created and interacted with by all role levels (with permission scope).
- Priority:
  - Below `Content Manager - Public`
  - Above `Content Manager - Personal`
- Minimum Role:
  - Community participation minimum role is `User` (or any higher role).
- Main Responsibilities:
  - Manage community content CRUD and publication state.
  - Manage community route ID mapping.
  - Manage content workflow (`draft`, `review`, `published`, `archived`) for moderation-safe publishing.
  - Manage content versioning and rollback.
  - Manage community reactions/comments and moderation state.
  - Provide shared content feed for public/member views.
- Core Components:
  - `CommunityContentService`
  - `CommunityContentRepository`
  - `CommunityTemplateResolver`
  - `CommunityPublishWorkflowService`
- Expected Storage:
  - `community_contents` table: author, title, body, status, visibility, timestamps
  - `community_content_versions` table: content_id, version_no, snapshot, changed_by, changed_at
  - Optional `community_content_reports` table: content_id, reported_by, reason, status, timestamps
- Public Interfaces:
  - `Index.app` (community feed view, role-scoped)
  - `UserPanel/CommunityContent/Index.app` (user create/manage own)
  - `ModPanel/CommunityContent/Index.app` (moderation view/actions)
  - `ControlPanel/CommunityContentManager/Index.app` (full control/configuration)
- Security Rules:
  - Deny by default for unmapped routes/actions.
  - Role and permission checks required before create/edit/delete/moderate actions.
  - Moderation actions must be audit logged.
  - Content must be sanitized and validated before render.

## Route Hierarchy (Canonical)
- Three-Level Minimum Roles (Content Split Reference):
  - Public level (`Content Manager - Public`): minimum management role `Moderator`
  - Community level (this module): minimum participation role `User`
  - Personal level (`Content Manager - Personal`): minimum owner role `User`
- Highest level (module control):
  - `ControlPanel/CommunityContentManager/Index.app`
- Middle level (application moderation by module):
  - `ModPanel/CommunityContent/Index.app`
- Low level (user self-managed content):
  - `UserPanel/CommunityContent/Index.app`
- Public/shared view:
  - `Index.app`

## Behavior Baseline (Migrated From Legacy Page Manager)
- Route ID and routing:
  - Community route must resolve by content `Id` and follow role visibility rules.
  - Route binding must resolve to `.app` endpoints only.
- Workflow:
  - `draft -> review -> published -> archived`
  - `published -> review` allowed for moderation corrections.
- Versioning:
  - Each content mutation appends a version row.
  - Restore creates a new version row (append-only history).
- Rendering/cache:
  - Published content is renderable in community feed/details.
  - Moderation/content state changes must invalidate affected cache entries.

## L3 Data Integrity (Content Manager - Community Canonical Schema)
- `community_contents` (required):
  - `id` (CHAR(8), PK, Security-generated, regex `^[A-Z0-9]{8}$`)
  - `author_user_id` (BIGINT UNSIGNED NOT NULL, FK -> `users.id`)
  - `title` (VARCHAR 190 NOT NULL)
  - `content_html` (LONGTEXT NULL)
  - `content_text` (LONGTEXT NOT NULL)
  - `status` (ENUM `draft`,`review`,`published`,`archived`,`deleted` NOT NULL DEFAULT `draft`)
  - `visibility` (ENUM `public`,`members`,`unlisted` NOT NULL DEFAULT `public`)
  - `category_key` (VARCHAR 120 NULL)
  - `tags_json` (JSON NULL)
  - `published_at` (DATETIME NULL)
  - `archived_at` (DATETIME NULL)
  - `deleted_at` (DATETIME NULL)
  - `created_at` (DATETIME NOT NULL)
  - `updated_at` (DATETIME NOT NULL)
  - Index: (`status`,`visibility`,`published_at`)
  - Index: (`author_user_id`,`created_at`)
- `community_content_versions` (required):
  - `id` (CHAR(8), PK, Security-generated, regex `^[A-Z0-9]{8}$`)
  - `community_content_id` (CHAR(8) NOT NULL, FK -> `community_contents.id`)
  - `version_no` (INT UNSIGNED NOT NULL)
  - `snapshot_json` (JSON NOT NULL)
  - `changed_by` (BIGINT UNSIGNED NULL, FK -> `users.id`)
  - `change_note` (VARCHAR 255 NULL)
  - `created_at` (DATETIME NOT NULL)
  - Unique: (`community_content_id`,`version_no`)
  - Index: (`created_at`)
- `community_content_reports` (optional):
  - `id` (CHAR(8), PK, Security-generated, regex `^[A-Z0-9]{8}$`)
  - `community_content_id` (CHAR(8) NOT NULL, FK -> `community_contents.id`)
  - `reported_by_user_id` (BIGINT UNSIGNED NULL, FK -> `users.id`)
  - `reason_code` (VARCHAR 80 NOT NULL)
  - `details` (VARCHAR 255 NULL)
  - `status` (ENUM `open`,`reviewing`,`resolved`,`rejected` NOT NULL DEFAULT `open`)
  - `resolved_by_user_id` (BIGINT UNSIGNED NULL, FK -> `users.id`)
  - `resolved_at` (DATETIME NULL)
  - `created_at` (DATETIME NOT NULL)
  - `updated_at` (DATETIME NOT NULL)
  - Index: (`status`,`created_at`)
  - Index: (`community_content_id`,`status`)

## L3 Logical Fields (Quick View)
| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `CommunityContent.Id` | `community_contents.id` | `CHAR(8)`, PK, Security-generated |
| `CommunityContent.AuthorUserId` | `community_contents.author_user_id` | FK -> `users.id` |
| `CommunityContent.Title` | `community_contents.title` | `VARCHAR(190)` |
| `CommunityContent.Content.Html` | `community_contents.content_html` | longtext, nullable |
| `CommunityContent.Content.Text` | `community_contents.content_text` | longtext |
| `CommunityContent.Status` | `community_contents.status` | `draft/review/published/archived/deleted` |
| `CommunityContent.Visibility` | `community_contents.visibility` | `public/members/unlisted` |
| `CommunityContent.Category` | `community_contents.category_key` | `VARCHAR(120)`, nullable |
| `CommunityContent.Tags` | `community_contents.tags_json` | JSON, nullable |
| `CommunityContent.PublishedAt` | `community_contents.published_at` | datetime, nullable |
| `CommunityContent.ArchivedAt` | `community_contents.archived_at` | datetime, nullable |
| `CommunityContent.DeletedAt` | `community_contents.deleted_at` | datetime, nullable |
| `CommunityContent.CreatedAt` | `community_contents.created_at` | datetime |
| `CommunityContent.UpdatedAt` | `community_contents.updated_at` | datetime |
| `CommunityContent.Constraint` | `pk(id)` | canonical route identity by primary key |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `CommunityVersion.Id` | `community_content_versions.id` | PK, auto increment |
| `CommunityVersion.ContentId` | `community_content_versions.community_content_id` | `CHAR(8)`, FK -> `community_contents.id` |
| `CommunityVersion.No` | `community_content_versions.version_no` | unsigned int |
| `CommunityVersion.Snapshot` | `community_content_versions.snapshot_json` | JSON |
| `CommunityVersion.ChangedBy` | `community_content_versions.changed_by` | FK -> `users.id`, nullable |
| `CommunityVersion.ChangeNote` | `community_content_versions.change_note` | `VARCHAR(255)`, nullable |
| `CommunityVersion.CreatedAt` | `community_content_versions.created_at` | datetime |
| `CommunityVersion.Constraint` | `unique(community_content_id, version_no)` | version uniqueness per content |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `CommunityReport.Id` | `community_content_reports.id` | PK, auto increment |
| `CommunityReport.ContentId` | `community_content_reports.community_content_id` | `CHAR(8)`, FK -> `community_contents.id` |
| `CommunityReport.ReportedBy` | `community_content_reports.reported_by_user_id` | FK -> `users.id`, nullable |
| `CommunityReport.Reason` | `community_content_reports.reason_code` | `VARCHAR(80)` |
| `CommunityReport.Details` | `community_content_reports.details` | `VARCHAR(255)`, nullable |
| `CommunityReport.Status` | `community_content_reports.status` | `open/reviewing/resolved/rejected` |
| `CommunityReport.ResolvedBy` | `community_content_reports.resolved_by_user_id` | FK -> `users.id`, nullable |
| `CommunityReport.ResolvedAt` | `community_content_reports.resolved_at` | datetime, nullable |
| `CommunityReport.CreatedAt` | `community_content_reports.created_at` | datetime |
| `CommunityReport.UpdatedAt` | `community_content_reports.updated_at` | datetime |

## Route-to-Permission Matrix (Content Manager - Community)
| Route | Method | Purpose | Minimum Role | Required Permission | Scope Rule |
|---|---|---|---|---|---|
| `Index.app` | `GET` | View community feed | `Guest` | `community.view` | Public scope |
| `Community/View.app?Id={CommunityContent_Id}` | `GET` | View one community item | `Guest` | `community.view` | Visibility and status filtered |
| `UserPanel/CommunityContent/Index.app` | `GET` | View own community content | `User` | `community.view.own` | Owner scope |
| `UserPanel/CommunityContent/Edit.app` | `POST` | Create/update own content | `User` | `community.edit.own` | Owner scope |
| `UserPanel/CommunityContent/Version.app` | `GET` | View own versions | `User` | `community.version.view.own` | Owner scope |
| `UserPanel/CommunityContent/Version.app` | `POST` | Restore own version | `User` | `community.version.restore.own` | Owner scope |
| `UserPanel/CommunityContent/Report.app` | `POST` | Report community content | `User` | `community.report.create` | User/report scope |
| `ModPanel/CommunityContent/Index.app` | `GET` | View moderation queue | `Moderator` | `community.moderate.view` | Moderation scope |
| `ModPanel/CommunityContent/Moderate.app` | `POST` | Moderate content/report | `Moderator` | `community.moderate.action` | Moderation scope |
| `ControlPanel/CommunityContentManager/Index.app` | `GET` | Full dashboard/settings | `Administrator` | `community.manage` | Full scope |
| `ControlPanel/CommunityContentManager/Rules.app` | `POST` | Update moderation/publication rules | `Administrator` | `community.rules.manage` | Full scope |
| `ControlPanel/CommunityContentManager/Audit.app` | `GET` | View community audit | `Administrator` | `community.audit.view` | Full scope |

### Enforcement Rules (Content Manager - Community)
- Deny by default if route/action is not mapped.
- Deny when actor role is inactive or required permission is missing.
- `Guest` and `User` cannot access `ModPanel/*` or `ControlPanel/*` routes.
- `UserPanel/*` write actions are owner-scoped and must not affect other users' content.
- Moderation and rules changes must be audit logged with actor, target, reason, and timestamp.

## Response/Error Contract (Content Manager - Community)
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

### HTTP Status Mapping (Content Manager - Community)
- `GET Index.app`:
  - `200 OK`, `404 Not Found`, `500 Internal Server Error`
- `GET Community/View.app?Id={CommunityContent_Id}`:
  - `200 OK`, `404 Not Found`, `410 Gone`, `500 Internal Server Error`
- `GET UserPanel/CommunityContent/Index.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `POST UserPanel/CommunityContent/Edit.app`:
  - `200 OK`, `201 Created`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`
- `GET UserPanel/CommunityContent/Version.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `500 Internal Server Error`
- `POST UserPanel/CommunityContent/Version.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `409 Conflict`, `500 Internal Server Error`
- `POST UserPanel/CommunityContent/Report.app`:
  - `202 Accepted`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`
- `GET ModPanel/CommunityContent/Index.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `POST ModPanel/CommunityContent/Moderate.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`
- `GET ControlPanel/CommunityContentManager/Index.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `POST ControlPanel/CommunityContentManager/Rules.app`:
  - `200 OK`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`
- `GET ControlPanel/CommunityContentManager/Audit.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`

### Error Code Catalog (Content Manager - Community Minimum)
- `COMMUNITY_PERMISSION_DENIED`
- `COMMUNITY_NOT_FOUND`
- `COMMUNITY_VISIBILITY_DENIED`
- `COMMUNITY_OWNER_SCOPE_DENIED`
- `COMMUNITY_ROUTE_CONFLICT`
- `COMMUNITY_VALIDATION_FAILED`
- `COMMUNITY_VERSION_NOT_FOUND`
- `COMMUNITY_RESTORE_NOT_ALLOWED`
- `COMMUNITY_REPORT_INVALID`
- `COMMUNITY_MODERATION_DENIED`
- `COMMUNITY_INTERNAL_ERROR`

## Content Manager - Community Status
- Status: Complete
- Completed:
  - Canonical naming and role-level route hierarchy
  - Canonical table ownership names for Database Manager compatibility
  - L3 data integrity schema and logical fields quick-view tables
  - Route-to-permission matrix and enforcement rules
  - Response/error contract with HTTP status mapping and error code catalog
- Remaining:
  - None
