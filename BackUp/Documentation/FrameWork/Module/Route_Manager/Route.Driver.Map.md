# Route.Driver.Map

## Purpose
Deterministic route mapping driver for Route Manager.

## Inputs
- `Route.BasicData.json` (canonical baseline route contracts)
- Enabled module state (Module Manager)
- Environment/policy flags (Environment Manager)

## Processing
1. Load canonical route baseline.
2. Filter by enabled modules and environment policy.
3. Validate route pattern and method contracts.
4. Validate panel prefix policy (`UserPanel`, `ModPanel`, `ControlPanel`, explicit public routes).
5. Detect conflicts and invalid permission mapping.
6. Emit normalized route manifest for runtime registration.

## CI Integration
- Reads CI files: `app/Config/Routing.php`, `app/Config/Routes.php`, `app/Config/Filters.php`.
- Reads Route Manager artifact: `Route.BasicData.json`.
- Publishes validated route registration to `app/Config/Routes.php` (or approved route include chain).
- Must keep CI routing policy aligned with `autoRoute=false` and explicit route mapping only.

## Outputs
- Runtime route manifest (normalized)
- Validation report (`pass/fail`, errors, warnings)

## Access Decision Logic
- Request-time allow only when all pass:
  - route exists and is enabled in published map
  - permission key is mapped for the route
  - actor role is active and allowed for route permission
- Deny conditions:
  - unknown route pattern or disabled route -> `404`
  - missing authentication -> `401`
  - role/permission mismatch -> `403`

## Hard Rules
- Do not accept unknown wildcard `/{RouteName}` style patterns.
- Deny route activation when required permission mapping is missing.
- Enforce `.app` extension contract for managed endpoints.
