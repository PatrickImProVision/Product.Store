# BackUp script for CodeIgniter project
# Creates a versioned 7-Zip archive in the BackUp folder with auto-incrementing patch numbers.
# Requires 7-Zip and splits the archive into GitHub-friendly volumes.

$projectRoot = Split-Path -Parent $PSScriptRoot
$backupDir = Join-Path $projectRoot "BackUp"
$baseName = "CodeIgniter4_FrameWork"
$splitArchiveSize = "45m"

function Get-NextVersionLabel {
    $pattern = "^" + [Regex]::Escape($baseName) + "_v(?<ver>[0-9]+(\.[0-9]+){2})\.7z(?:\.\d{3})?$"
    $existing = @()
    if (Test-Path -Path $backupDir) {
        $existing = Get-ChildItem -Path $backupDir -File -ErrorAction SilentlyContinue |
            Where-Object { $_.Name -match ("^" + [Regex]::Escape($baseName) + "_v[0-9]+(\.[0-9]+){2}\.7z(?:\.\d{3})?$") }
    }

    $versions = @()
    foreach ($file in $existing) {
        $m = [Regex]::Match($file.Name, $pattern)
        if ($m.Success) {
            $versions += $m.Groups["ver"].Value
        }
    }

    if ($versions.Count -eq 0) {
        return "1.0.0"
    }

    $maxVersion = ($versions | Sort-Object { [version]$_ } -Descending | Select-Object -First 1)
    $v = [version]$maxVersion
    $next = [version]::new($v.Major, $v.Minor, $v.Build + 1)
    return $next.ToString()
}

$versionLabel = Get-NextVersionLabel
$archiveName = "${baseName}_v$versionLabel.7z"
$archivePath = Join-Path $backupDir $archiveName
$createdArchivePath = $archivePath

# Ensure BackUp directory exists
if (-not (Test-Path -Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
}

# Remove existing archive if present
if (Test-Path -Path $archivePath) {
    Remove-Item -Path $archivePath -Force
}
Get-ChildItem -Path $backupDir -Filter "$archiveName.*" -File -ErrorAction SilentlyContinue | Remove-Item -Force

# Get all items in project root, excluding the BackUp folder, .vscode, and the archive itself
$items = Get-ChildItem -Path $projectRoot -Force |
    Where-Object { $_.Name -ne 'BackUp' -and $_.Name -ne '.vscode' -and $_.FullName -ne $archivePath } |
    Select-Object -ExpandProperty FullName

# Include BackUp script + readme, but not the BackUp folder contents in general
$backupScript = Join-Path $backupDir 'BackUp_Create.ps1'
$backupReadMe = Join-Path $backupDir 'BackUp_ReadMe.md'
if (Test-Path $backupScript) { $items += $backupScript }
if (Test-Path $backupReadMe) { $items += $backupReadMe }

$sevenZip = Get-Command 7z.exe -ErrorAction SilentlyContinue
if (-not $sevenZip) {
    Write-Host "7-Zip was not found in the system PATH."
    Write-Host "Install 7-Zip and add the folder containing 7z.exe to your Windows Environment PATH."
    throw "Backup creation stopped because 7z.exe is required."
}

Write-Host "7-Zip found. Creating a split 7z backup with maximum compression."
Write-Host "Archive volumes will be limited to $splitArchiveSize each for GitHub-friendly uploads."
$listFile = Join-Path ([System.IO.Path]::GetTempPath()) ("backup-items-" + [guid]::NewGuid().ToString() + ".txt")
$tempArchivePath = Join-Path ([System.IO.Path]::GetTempPath()) ("backup-" + [guid]::NewGuid().ToString() + ".7z")
try {
    $relativeItems = foreach ($item in $items) {
        [System.IO.Path]::GetRelativePath($projectRoot, $item)
    }

    Set-Content -Path $listFile -Value $relativeItems -Encoding utf8

    Push-Location $projectRoot
    try {
        Write-Host "Compression in progress. 7-Zip will display its native progress below."
        & $sevenZip.Source a -t7z $tempArchivePath "@$listFile" -mx=9 -m0=LZMA2 -md=256m -mfb=273 -ms=on -mmt=on "-v$splitArchiveSize"
        if ($LASTEXITCODE -ne 0) {
            throw "7-Zip failed with exit code $LASTEXITCODE."
        }
    }
    finally {
        Pop-Location
    }

    $tempVolumeFiles = Get-ChildItem -Path ([System.IO.Path]::GetDirectoryName($tempArchivePath)) -Filter (([System.IO.Path]::GetFileName($tempArchivePath)) + "*") -File
    foreach ($tempVolumeFile in $tempVolumeFiles) {
        $destinationName = $tempVolumeFile.Name.Replace([System.IO.Path]::GetFileName($tempArchivePath), $archiveName)
        $destinationPath = Join-Path $backupDir $destinationName
        Move-Item -LiteralPath $tempVolumeFile.FullName -Destination $destinationPath -Force
    }

    $createdVolumes = Get-ChildItem -Path $backupDir -Filter "$archiveName*" -File | Sort-Object Name
    if ($createdVolumes.Count -gt 0) {
        $createdArchivePath = $createdVolumes[0].FullName
    }
}
finally {
    if (Test-Path -Path $listFile) {
        Remove-Item -Path $listFile -Force
    }
    Get-ChildItem -Path ([System.IO.Path]::GetDirectoryName($tempArchivePath)) -Filter (([System.IO.Path]::GetFileName($tempArchivePath)) + "*") -File -ErrorAction SilentlyContinue | Remove-Item -Force
}

Write-Host "BackUp created:" $createdArchivePath
Write-Host "To extract the backup later, start with the first volume: $createdArchivePath"
