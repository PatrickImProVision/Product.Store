# FrameWork.ContentManager-Public

## Module Specification
Content Manager - Public Description:

- Core Purpose: Manage public-facing website pages, navigation, and static/public content delivery.
- Main Responsibilities:
  - Manage public page CRUD and publication state.
  - Manage public route ID bindings.
  - Manage public template/layout assignment.
  - Enforce visibility and publication rules for anonymous/authenticated visitors.
- Core Components:
  - `PublicPageService`
  - `PublicPageRepository`
  - `PublicTemplateResolver`
  - `PublicPublishWorkflowService`
- Expected Storage:
  - `public_pages` table: title, content, template, status, publish dates
  - `public_page_versions` table: page_id, version_no, snapshot, changed_by, changed_at
- Public Interfaces:
  - ControlPanel route (canonical): `ControlPanel/PublicPageManager/Index.app`
  - Service methods: `createPublicPage()`, `publishPublicPage()`, `renderPublicPageById()`, `restorePublicPageVersion()`
- Security Rules:
  - Restrict create/edit/publish actions by permission.
  - Sanitize and validate stored content before render.
  - Protect admin routes with auth + role filters.

## Inherited Common Attributes (Content Manager - Public)
- General Standards (shared baseline for Content Manager - Public) are inherited from `FrameWork.md`:
  - `Common Layer Standards (L1-L5)`
  - `Common Security Standards (Shared Attributes)`

## Public Page Module-Local Canonical Definitions (L1-L5)
### L1 Core Identity (Public Page Local)
- Page state keys:
  - `draft`, `review`, `published`, `archived`
- Visibility keys:
  - `public`, `unlisted`
- Event keys:
  - `public_page.created`
  - `public_page.updated`
  - `public_page.published`
  - `public_page.archived`
  - `public_page.version.restored`

### L2 Security Controls (Public Page Local)
- Permission keys:
  - `public_page.view`
  - `public_page.create`
  - `public_page.edit`
  - `public_page.publish`
  - `public_page.archive`
  - `public_page.version.view`
  - `public_page.version.restore`
- Role defaults:
  - `Owner`, `Administrator`: full public-page permissions
  - `Moderator`: public-content management permissions (`view/edit/publish/archive`) by policy
  - `Author`: read-only on public routes by default
  - `User`, `Guest`: read-only public routes
- Three-Level Minimum Roles (Content Split Reference):
  - Public level (this module): minimum management role `Moderator`
  - Community level (`Content Manager - Community`): minimum participation role `User` (or higher by policy)
  - Personal level (`Content Manager - Personal`): minimum owner role `User` for self content/comments

### L3 Data Integrity (Public Page Local Canonical Schema)
- `public_pages` (required):
  - `id` (CHAR(8), PK, Security-generated, regex `^[A-Z0-9]{8}$`)
  - `title` (VARCHAR 190, NOT NULL)
  - `content_html` (LONGTEXT NULL)
  - `content_text` (LONGTEXT NOT NULL)
  - `template_key` (VARCHAR 120 NOT NULL DEFAULT `default`)
  - `status` (ENUM `draft`,`review`,`published`,`archived` NOT NULL DEFAULT `draft`)
  - `visibility` (ENUM `public`,`unlisted` NOT NULL DEFAULT `public`)
  - `author_user_id` (BIGINT UNSIGNED NULL, FK -> `users.id`)
  - `published_at` (DATETIME NULL)
  - `archived_at` (DATETIME NULL)
  - `created_at` (DATETIME NOT NULL)
  - `updated_at` (DATETIME NOT NULL)
  - Index: (`status`,`visibility`,`published_at`)
  - Index: (`author_user_id`,`created_at`)
- `public_page_versions` (required):
  - `id` (CHAR(8), PK, Security-generated, regex `^[A-Z0-9]{8}$`)
  - `public_page_id` (CHAR(8) NOT NULL, FK -> `public_pages.id`)
  - `version_no` (INT UNSIGNED NOT NULL)
  - `snapshot_json` (JSON NOT NULL)
  - `changed_by` (BIGINT UNSIGNED NULL, FK -> `users.id`)
  - `change_note` (VARCHAR 255 NULL)
  - `created_at` (DATETIME NOT NULL)
  - Unique: (`public_page_id`,`version_no`)
  - Index: (`created_at`)
- Optional `public_menu_links`:
  - `id` (CHAR(8), PK, Security-generated, regex `^[A-Z0-9]{8}$`)
  - `label` (VARCHAR 120 NOT NULL)
  - `target_page_id` (CHAR(8) NOT NULL, FK -> `public_pages.id`)
  - `position` (INT NOT NULL DEFAULT 0)
  - `is_active` (TINYINT(1) NOT NULL DEFAULT 1)
  - `created_at` (DATETIME NOT NULL)
  - `updated_at` (DATETIME NOT NULL)

### L3 Logical Fields (Quick View)
| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `PublicPage.Id` | `public_pages.id` | `CHAR(8)`, PK, Security-generated |
| `PublicPage.Title` | `public_pages.title` | `VARCHAR(190)` |
| `PublicPage.RouteId` | `public_pages.id` | route parameter source (`Id`) |
| `PublicPage.Content.Html` | `public_pages.content_html` | longtext, nullable |
| `PublicPage.Content.Text` | `public_pages.content_text` | longtext |
| `PublicPage.Template.Key` | `public_pages.template_key` | `VARCHAR(120)` |
| `PublicPage.Status` | `public_pages.status` | `draft/review/published/archived` |
| `PublicPage.Visibility` | `public_pages.visibility` | `public/unlisted` |
| `PublicPage.AuthorUserId` | `public_pages.author_user_id` | FK -> `users.id`, nullable |
| `PublicPage.PublishedAt` | `public_pages.published_at` | datetime, nullable |
| `PublicPage.ArchivedAt` | `public_pages.archived_at` | datetime, nullable |
| `PublicPage.CreatedAt` | `public_pages.created_at` | datetime |
| `PublicPage.UpdatedAt` | `public_pages.updated_at` | datetime |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `PublicPageVersion.Id` | `public_page_versions.id` | PK, auto increment |
| `PublicPageVersion.PageId` | `public_page_versions.public_page_id` | `CHAR(8)`, FK -> `public_pages.id` |
| `PublicPageVersion.No` | `public_page_versions.version_no` | unsigned int |
| `PublicPageVersion.Snapshot` | `public_page_versions.snapshot_json` | JSON |
| `PublicPageVersion.ChangedBy` | `public_page_versions.changed_by` | FK -> `users.id`, nullable |
| `PublicPageVersion.ChangeNote` | `public_page_versions.change_note` | `VARCHAR(255)`, nullable |
| `PublicPageVersion.CreatedAt` | `public_page_versions.created_at` | datetime |
| `PublicPageVersion.Constraint` | `unique(public_page_id, version_no)` | version uniqueness per page |

| Logical Field | Table.Column | Type / Rule |
|---|---|---|
| `PublicMenu.Id` | `public_menu_links.id` | PK, auto increment |
| `PublicMenu.Label` | `public_menu_links.label` | `VARCHAR(120)` |
| `PublicMenu.TargetPageId` | `public_menu_links.target_page_id` | `CHAR(8)`, FK -> `public_pages.id` |
| `PublicMenu.Position` | `public_menu_links.position` | int |
| `PublicMenu.Active` | `public_menu_links.is_active` | bool |
| `PublicMenu.CreatedAt` | `public_menu_links.created_at` | datetime |
| `PublicMenu.UpdatedAt` | `public_menu_links.updated_at` | datetime |

### L4 Behavior and Processing (Public Page Local Canonical)
- Page lifecycle:
  - `draft -> review -> published -> archived`
  - `published -> review` allowed for corrections
- Publish rules:
  - `id` must exist and route binding must be valid before publish
  - `published_at` set only on transition to `published`
- Versioning rules:
  - every content mutation creates next `version_no`
  - restore operation creates a new version entry (append-only history)
- Render rules:
  - public routes only render `published` pages
  - `unlisted` pages require exact route and are not included in sitemap/menu by default

### L5 Operations and Runtime Controls (Public Page Local Canonical)
- Runtime behavior:
  - public page read routes must be cache-aware
  - control panel write routes must invalidate affected page cache
- Operational guardrails:
  - publish/archive/restore actions require audit logging
  - bulk publish/archive actions require explicit confirmation
- Required config keys:
  - `public_page.default_template`
  - `public_page.cache_ttl_seconds`
  - `public_page.allow_unlisted`
  - `public_page.require_review_before_publish`

## Route-to-Permission Matrix (Content Manager - Public)
| Route | Method | Action | Required Permission | Allowed Roles (Default) | Scope Rule |
|---|---|---|---|---|---|
| `Index.app` | `GET` | View public website home page | `public_page.view` | `Guest`, `User`, `Author`, `Moderator`, `Administrator`, `Owner` | Public scope |
| `PublicPage/View.app?Id={PublicPage_Id}` | `GET` | View one public page by id | `public_page.view` | `Guest`, `User`, `Author`, `Moderator`, `Administrator`, `Owner` | Public scope |
| `ControlPanel/PublicPageManager/Index.app` | `GET` | View public page manager dashboard | `public_page.view` | `Administrator`, `Owner`, `Moderator` (read-only) | ControlPanel scope |
| `ControlPanel/PublicPageManager/Page.app` | `POST` | Create/update public page | `public_page.edit` | `Moderator`, `Administrator`, `Owner` | Public management scope |
| `ControlPanel/PublicPageManager/Publish.app` | `POST` | Publish or archive page | `public_page.publish` | `Moderator`, `Administrator`, `Owner` | Public management scope |
| `ControlPanel/PublicPageManager/Version.app` | `GET` | View page version history | `public_page.version.view` | `Moderator`, `Administrator`, `Owner` | Public management scope |
| `ControlPanel/PublicPageManager/Version.app` | `POST` | Restore page version | `public_page.version.restore` | `Administrator`, `Owner` | Full scope |

### Enforcement Rules (Content Manager - Public)
- Deny by default if route is not mapped.
- Deny if actor role is inactive or permission key is missing.
- `Guest` and `User` are never allowed on `ControlPanel/*` routes.
- `Author` has no public content mutation rights by default.
- Publish/archive/restore operations must be audit logged.

## Response/Error Contract (Content Manager - Public)
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

### HTTP Status Mapping (Public Page Endpoints)
- `GET Index.app`:
  - `200 OK`, `404 Not Found`, `500 Internal Server Error`
- `GET PublicPage/View.app?Id={PublicPage_Id}`:
  - `200 OK`, `404 Not Found`, `410 Gone`, `500 Internal Server Error`
- `GET ControlPanel/PublicPageManager/Index.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `500 Internal Server Error`
- `POST ControlPanel/PublicPageManager/Page.app`:
  - `200 OK`, `201 Created`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`, `500 Internal Server Error`
- `POST ControlPanel/PublicPageManager/Publish.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `409 Conflict`, `500 Internal Server Error`
- `GET ControlPanel/PublicPageManager/Version.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `500 Internal Server Error`
- `POST ControlPanel/PublicPageManager/Version.app`:
  - `200 OK`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `409 Conflict`, `500 Internal Server Error`

### Error Code Catalog (Public Page Minimum)
- `PUBLIC_PAGE_PERMISSION_DENIED`
- `PUBLIC_PAGE_NOT_FOUND`
- `PUBLIC_PAGE_NOT_PUBLISHED`
- `PUBLIC_PAGE_ROUTE_CONFLICT`
- `PUBLIC_PAGE_VALIDATION_FAILED`
- `PUBLIC_PAGE_VERSION_NOT_FOUND`
- `PUBLIC_PAGE_RESTORE_NOT_ALLOWED`
- `PUBLIC_PAGE_INTERNAL_ERROR`

### Examples (Future Reference)
#### Success Example
```json
{
  "success": true,
  "code": "PUBLIC_PAGE_PUBLISH_SUCCESS",
  "message": "Public page published.",
  "data": {
    "page_id": 12,
    "status": "published"
  },
  "meta": {},
  "timestamp": "2026-02-23T14:00:00Z",
  "request_id": "req_public_page_001"
}
```

#### Error Example
```json
{
  "success": false,
  "code": "PUBLIC_PAGE_PERMISSION_DENIED",
  "message": "Permission denied for this page action.",
  "errors": [
    {
      "field": "permission",
      "reason": "missing public_page.publish"
    }
  ],
  "status": 403,
  "timestamp": "2026-02-23T14:00:00Z",
  "request_id": "req_public_page_002"
}
```

## Content Manager - Public Status
- Status: Complete
- Completed:
  - Module-local canonical definitions (L1-L5)
  - Route-to-permission matrix by role
  - Response/error contract and HTTP mapping
  - Future reference examples
- Remaining:
  - None
