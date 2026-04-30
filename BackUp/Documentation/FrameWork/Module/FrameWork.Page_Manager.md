# FrameWork.PageManager (Legacy Compatibility)

## Status
- Deprecated (do not use as canonical module spec)

## Reason
- The original page/content scope has been split into three canonical modules to avoid overlap:
  - `FrameWork.Content_Manager-Public.md` (highest priority content control)
  - `FrameWork.Content_Manager-Community.md` (shared community content)
  - `FrameWork.Content_Manager-Personal.md` (personal comments/content)

## Canonical Mapping
- Public website pages and owner/administrator/moderator content control:
  - `Module/FrameWork.Content_Manager-Public.md`
- Community content used by all role levels:
  - `Module/FrameWork.Content_Manager-Community.md`
- Personal comments and owner-scoped personal content:
  - `Module/FrameWork.Content_Manager-Personal.md`

## Three-Level Minimum Roles (Canonical)
- Public: minimum management role `Moderator`
- Community: minimum participation role `User`
- Personal: minimum owner role `User`

## Legacy Attribute Migration Note
- Legacy Page Manager attributes were migrated into canonical modules:
  - Public module: public CRUD, publish workflow, route/id rules, public rendering.
  - Community module: shared CRUD, id mapping, workflow, versioning, moderation-safe rendering/cache.
  - Personal module: owner-scoped CRUD/comments, visibility/share rules, versioning.

## Rule
- Do not add new schema/routes/contracts to this legacy file.
- Use the three canonical module files above for all future changes.
