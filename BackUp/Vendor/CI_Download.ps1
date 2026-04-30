# Latest CodeIgniter downloader
# Downloads the latest CodeIgniter 4 release zip into this folder (BackUp\Vendor)

[CmdletBinding()]
param(
    [string]$Repo = "codeigniter4/CodeIgniter4",
    [string]$DestinationDir = $PSScriptRoot,
    [switch]$Force
)

$ErrorActionPreference = "Stop"
$ProgressPreference = "SilentlyContinue"

try {
    # Prefer modern TLS where available
    $tls12 = [Net.SecurityProtocolType]::Tls12
    [Net.ServicePointManager]::SecurityProtocol = $tls12
} catch {
    # Ignore if not supported
}

function Get-LatestRelease {
    $apiUrl = "https://api.github.com/repos/$Repo/releases/latest"
    $headers = @{ "User-Agent" = "CI-Downloader" }
    return Invoke-RestMethod -Uri $apiUrl -Headers $headers
}

function Normalize-Version([string]$tagName) {
    if ([string]::IsNullOrWhiteSpace($tagName)) {
        throw "Release tag name is empty."
    }
    return $tagName.TrimStart('v', 'V')
}

$release = Get-LatestRelease
$tag = $release.tag_name
$version = Normalize-Version $tag

$zipName = "CodeIgniter4_FrameWork_v$version.zip"
$zipPath = Join-Path $DestinationDir $zipName
$downloadUrl = "https://codeload.github.com/$Repo/zip/refs/tags/$tag"

if (-not (Test-Path $DestinationDir)) {
    New-Item -ItemType Directory -Path $DestinationDir -Force | Out-Null
}

if ((Test-Path $zipPath) -and -not $Force) {
    Write-Host "Already downloaded:" $zipPath
    Write-Host "Use -Force to re-download."
    exit 0
}

try {
    Invoke-WebRequest -Uri $downloadUrl -OutFile $zipPath -Headers @{ "User-Agent" = "CI-Downloader" }
} catch {
    if (Test-Path $zipPath) {
        Remove-Item -Path $zipPath -Force
    }
    throw
}

Write-Host "Downloaded CodeIgniter $version to:" $zipPath
