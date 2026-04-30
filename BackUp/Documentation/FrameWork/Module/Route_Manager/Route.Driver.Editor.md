# Route.Driver.Editor

## Purpose
UI-facing route map driver for viewing and controlled editing.

## Inputs
- Current normalized route manifest
- Editor payload from Route Manager control panel

## Processing
1. Load current route map.
2. Present editable fields only (minimum required fields).
3. Stage add/update/disable actions.
4. Run pre-publish validation through `RouteMapDriver`.
5. Persist approved changes and write audit event.

## Editable Fields (minimum)
- `route`
- `method`
- `module`
- `permission`
- `panel`
- `is_enabled`

## CI Integration
- Reads effective route map from CI runtime registration (`app/Config/Routes.php`) plus Route Manager artifacts.
- Writes only staged edits to Route Manager artifact/store; no direct runtime route overwrite.
- Requires `RouteMapDriver` validation pass before publish to CI route registration.
- Editor UI must display CI publish status and validation errors before confirmation.

## Outputs
- Updated route map snapshot
- Change log / audit payload

## Editor-to-Runtime Flow
1. Administrator edits route in UI.
2. Editor saves staged route change to Route Manager store.
3. `RouteMapDriver` validates and approves/rejects publish.
4. Approved routes are published to runtime route map.
5. User requests are confirmed/refused by existence + role/permission checks.

## Hard Rules
- Must not directly publish changes without `RouteMapDriver` validation pass.
- Must block unknown route patterns and unresolved placeholders.
- Must require explicit confirmation for destructive route mutations.
