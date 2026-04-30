This folder is the target for backups created by BackUp_Create.ps1.

What the script does:
- Creates a versioned zip file in this BackUp folder.
- Uses the naming pattern: CodeIgniter4_FrameWork_v1.0.X.zip (auto-incrementing patch).
- Excludes the BackUp folder itself and .vscode to avoid recursion.
- Exception: it includes BackUp_Create.ps1 and BackUp_ReadMe.md in the zip.
- Automatically detects the project root based on the script location, then creates/uses the BackUp folder at the project root.

Auto-detection details:
- Project root is the parent folder of this script (BackUp folder).
- BackUp folder path is created if it does not exist.
- Items to zip are collected from the project root with exclusions for BackUp, .vscode, and the target zip itself.
- Then BackUp_Create.ps1 and BackUp_ReadMe.md are added explicitly.

How to run (from the project root):
powershell -ExecutionPolicy Bypass -File BackUp\\BackUp_Create.ps1

Or inside PowerShell:
.\BackUp\\BackUp_Create.ps1

Output:
- The script prints the full path of the created zip.


