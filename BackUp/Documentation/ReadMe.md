# CodeIgniter Modular Framework Project

## Overview
This project defines a modular CodeIgniter 4 architecture where shared standards live in one framework file and module-specific contracts live in module files.

Primary framework source:
- `BackUp/FrameWork/FrameWork.md`

## Canonical Module Priority
1. User Manager
2. Security Manager
3. Module Manager
4. Database Manager
5. Route Manager
6. Environment Manager
7. E-Mail Manager
8. Community Content Manager

Dependency map:
- `User Manager -> Security Manager -> Module Manager -> Database Manager -> Route Manager -> Environment Manager -> E-Mail Manager -> Community Content Manager`

## Framework Structure

### Shared (Global) Rules
- `BackUp/FrameWork/FrameWork.md`

Contains shared-only definitions:
- module order and dependency map
- installation lifecycle
- continuity protocol
- common layer standards (L1-L5)
- common security standards
- scope boundaries

### Module Contracts
- `BackUp/FrameWork/Module/FrameWork.User_Manager.md`
- `BackUp/FrameWork/Module/FrameWork.Security_Manager.md`
- `BackUp/FrameWork/Module/FrameWork.Module_Manager.md`
- `BackUp/FrameWork/Module/FrameWork.DataBase_Manager.md`
- `BackUp/FrameWork/Module/FrameWork.Route_Manager.md`
- `BackUp/FrameWork/Module/FrameWork.Environment_Manager.md`
- `BackUp/FrameWork/Module/FrameWork.E-Mail_Manager.md`
- `BackUp/FrameWork/Module/FrameWork.Page_Manager.md`

### UI Planning Contracts
- `BackUp/FrameWork/Module.UI/FrameWork.User_Manager.UI.md`
- `BackUp/FrameWork/Module.UI/FrameWork.Security_Manager.UI.md`
- `BackUp/FrameWork/Module.UI/FrameWork.Module_Manager.UI.md`
- `BackUp/FrameWork/Module.UI/FrameWork.DataBase_Manager.UI.md`
- `BackUp/FrameWork/Module.UI/FrameWork.Route_Manager.UI.md`
- `BackUp/FrameWork/Module.UI/FrameWork.Environment_Manager.UI.md`
- `BackUp/FrameWork/Module.UI/FrameWork.E-Mail_Manager.UI.md`
- `BackUp/FrameWork/Module.UI/FrameWork.Page_Manager.UI.md`

## Current Module Status
- User Manager: Complete
- Security Manager: Complete
- Module Manager: Complete
- E-Mail Manager: Complete
- Database Manager: Draft (needs full L1-L5 expansion)
- Route Manager: Draft (needs full L1-L5 expansion)
- Environment Manager: Draft (needs full L1-L5 expansion)
- Community Content Manager: Draft (needs full L1-L5 expansion)

## Core Governance Rules
- Shared standards stay in `BackUp/FrameWork/FrameWork.md`.
- Module files contain module-local contracts only.
- All modules inherit common attributes and common security standards.
- User + Security are fundamental first.
- Module Manager controls acceptance standards for other modules.

## Installation Lifecycle (Canonical)
1. Initialize environment and database config.
2. Persist installation state.
3. First-run owner registration via User Manager.
4. Mark bootstrap complete.
5. Lock installer.
6. Install/enable other modules through Module Manager standards.
7. Runtime detection for installed mode.

## Routing and Entry Notes
- Canonical route style includes `.app` endpoints as defined in module contracts.
- Security Manager control panel canonical route:
  - `ControlPanel/SecurityManager/Index.app`
- Routing rewrite ownership and `.htaccess` governance:
  - `BackUp/FrameWork/Module/FrameWork.Route_Manager.md`

## Continuity Rule
For any future implementation task:
1. Load `BackUp/FrameWork/FrameWork.md` first.
2. Load only required module file(s).
3. Follow canonical order unless user explicitly overrides.
4. Keep shared/global rules in `FrameWork.md` only.

## Notes
- This README summarizes architecture state.
- Detailed technical contracts are authoritative in `BackUp/FrameWork/Module/*.md`.

