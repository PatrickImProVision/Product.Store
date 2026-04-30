# CI Downloader (CodeIgniter 4)

This script downloads the latest CodeIgniter 4 release zip from GitHub into this folder.

## File
- CI_Download.ps1

## What It Does
- Calls the GitHub Releases API for `codeigniter4/CodeIgniter4`.
- Resolves the latest release tag (e.g., `v4.7.0`).
- Downloads the release zip as:
  `CodeIgniter4_FrameWork_v<version>.zip`
  into this folder.

## Usage
From the repository root:

```powershell
powershell -ExecutionPolicy Bypass -File BackUp\Vendor\CI_Download.ps1
```

Force re-download if the file already exists:

```powershell
powershell -ExecutionPolicy Bypass -File BackUp\Vendor\CI_Download.ps1 -Force
```

## Options
- `-Repo` (string): GitHub repo in `owner/name` form.
  Default: `codeigniter4/CodeIgniter4`
- `-DestinationDir` (string): Output directory.
  Default: the script folder (`BackUp\Vendor`)
- `-Force` (switch): Re-download even if the zip already exists.

## Notes
- Requires internet access.
- The zip is the official release tag source package from GitHub.
