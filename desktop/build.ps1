<#
.SYNOPSIS
    Builds the BMPC Project Management desktop app (Windows, WebView2).

.DESCRIPTION
    Produces, in desktop\dist:
      BMPC-ProjectManagement-<version>-Setup.exe     one installer that asks: Install, or Portable (extract only)
      BMPC-ProjectManagement-<version>-Portable.zip  unzip-and-run copy, no installation at all

    The "full" flavor bundles an offline server (PHP 8.3, MySQL 8.4, the Laravel
    app) so the app runs without internet; it can also connect to a hosted BMPC
    server. The "client" flavor ships only the WebView2 window for a hosted server.

.EXAMPLE
    pwsh desktop\build.ps1
    pwsh desktop\build.ps1 -Version 1.2.0
    pwsh desktop\build.ps1 -Flavor client -ServerUrl https://pm.example.coop
    pwsh desktop\build.ps1 -LauncherOnly      # recompile the launcher into the existing stage

    # One-click installer for the "Install the desktop app?" offer on the login page:
    pwsh desktop\build.ps1 -WebInstaller -ServerUrl https://pm.example.coop
#>
[CmdletBinding()]
param(
    [string] $Version = '1.0.0',
    [ValidateSet('full', 'client')] [string] $Flavor = 'full',
    # Preset server address; the app then skips the first-run choice.
    [string] $ServerUrl = '',
    [switch] $LauncherOnly,
    [switch] $SkipAssets,
    [switch] $NoInstaller,
    [switch] $NoPortableZip,
    # Build the one-click installer offered on the login page: window-only (client)
    # flavor, preset to ServerUrl, no wizard pages. Copied to PublishDir for Laravel to serve.
    [switch] $WebInstaller,
    [string] $PublishDir = '',
    [string] $PhpDir = '',
    [string] $MySqlDir = '',
    [string] $NodeDir = ''
)

$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'

$ProductName = 'BMPC Project Management'
$ExeName = 'BMPCProjectManagement.exe'
$WebView2SdkVersion = '1.0.2903.40'
$InnoSetupVersion = '6.5.4'

$DesktopDir = $PSScriptRoot
$RepoDir = Split-Path $DesktopDir -Parent
$ToolsDir = Join-Path $DesktopDir '.tools'
$BuildDir = Join-Path $DesktopDir 'build'
# The stage folder name becomes the top folder inside the portable zip.
$StageDir = Join-Path $BuildDir $ProductName
$DistDir = Join-Path $DesktopDir 'dist'

if ($WebInstaller) {
    $Flavor = 'client'
    $NoPortableZip = $true
    if (-not $ServerUrl) {
        # Default to the address this app is served from.
        $line = Select-String -Path (Join-Path $RepoDir '.env') -Pattern '^\s*APP_URL\s*=\s*"?([^"\s]+)' -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($line) { $ServerUrl = $line.Matches[0].Groups[1].Value }
    }
    if (-not $ServerUrl) { throw '-WebInstaller needs -ServerUrl (the address users open the system at).' }
    if (-not $PublishDir) { $PublishDir = Join-Path $RepoDir 'storage\app\desktop' }
}

$OutputBase = "BMPC-ProjectManagement-$Version" + $(if ($WebInstaller) { '-Web' } elseif ($Flavor -eq 'client') { '-Client' } else { '' })

if ($Version -notmatch '^\d+\.\d+\.\d+$') { throw "Version must look like 1.2.3 (got '$Version')." }

function Step($message) { Write-Host "`n==> $message" -ForegroundColor Cyan }
function Info($message) { Write-Host "    $message" -ForegroundColor DarkGray }

function Invoke-Native {
    param([string] $File, [string[]] $Arguments, [string] $WorkingDirectory = $RepoDir)
    Push-Location $WorkingDirectory
    try {
        & $File @Arguments
        if ($LASTEXITCODE -ne 0) { throw "$File $($Arguments -join ' ') failed with exit code $LASTEXITCODE" }
    } finally { Pop-Location }
}

function Invoke-Robocopy {
    param([string] $From, [string] $To, [string[]] $Extra = @())
    & robocopy $From $To /E /NFL /NDL /NJH /NJS /NP /R:1 /W:1 @Extra | Out-Null
    if ($LASTEXITCODE -ge 8) { throw "robocopy $From -> $To failed ($LASTEXITCODE)" }
    $global:LASTEXITCODE = 0
}

function Save-Download([string] $Url, [string] $Path) {
    New-Item -ItemType Directory -Force (Split-Path $Path) | Out-Null
    Info "Downloading $Url"
    Invoke-WebRequest $Url -OutFile $Path -UseBasicParsing
}

function Assert-Signed([string] $Path, [string] $Publisher) {
    $signature = Get-AuthenticodeSignature $Path
    if ($signature.Status -ne 'Valid' -or $signature.SignerCertificate.Subject -notmatch $Publisher) {
        throw "$Path is not validly signed by $Publisher ($($signature.Status))."
    }
}

# ---------------------------------------------------------------------------
# Tools
# ---------------------------------------------------------------------------

function Resolve-Php {
    if ($PhpDir) { return (Resolve-Path $PhpDir).Path }
    $command = Get-Command php -ErrorAction SilentlyContinue
    if ($command) { return Split-Path $command.Source }
    $laragon = Get-ChildItem 'C:\laragon\bin\php' -Directory -Filter 'php-8.*' -ErrorAction SilentlyContinue | Sort-Object Name -Descending | Select-Object -First 1
    if ($laragon) { return $laragon.FullName }
    throw 'PHP 8.3+ (Windows x64, thread safe) not found. Pass -PhpDir.'
}

function Resolve-MySql {
    if ($MySqlDir) { return (Resolve-Path $MySqlDir).Path }
    $laragon = Get-ChildItem 'C:\laragon\bin\mysql' -Directory -Filter 'mysql-8.*' -ErrorAction SilentlyContinue | Sort-Object Name -Descending | Select-Object -First 1
    if ($laragon) { return $laragon.FullName }
    throw 'MySQL 8 for Windows (the "ZIP Archive" download) not found. Pass -MySqlDir.'
}

function Resolve-Node {
    if ($NodeDir) { return (Resolve-Path $NodeDir).Path }
    $command = Get-Command npm.cmd -ErrorAction SilentlyContinue
    if ($command) { return Split-Path $command.Source }
    $laragon = Get-ChildItem 'C:\laragon\bin\nodejs' -Directory -ErrorAction SilentlyContinue | Sort-Object Name -Descending | Select-Object -First 1
    if ($laragon) { return $laragon.FullName }
    throw 'Node.js not found (needed to build the front-end). Pass -NodeDir or -SkipAssets.'
}

function Resolve-WebView2Sdk {
    $sdk = Join-Path $ToolsDir "webview2-$WebView2SdkVersion"
    if (-not (Test-Path "$sdk\lib\net462\Microsoft.Web.WebView2.WinForms.dll")) {
        $package = Join-Path $ToolsDir "downloads\webview2-$WebView2SdkVersion.nupkg"
        if (-not (Test-Path $package)) { Save-Download "https://www.nuget.org/api/v2/package/Microsoft.Web.WebView2/$WebView2SdkVersion" $package }
        Expand-Archive $package -DestinationPath $sdk -Force
    }
    return $sdk
}

function Resolve-WebView2Bootstrapper {
    $path = Join-Path $ToolsDir 'downloads\MicrosoftEdgeWebview2Setup.exe'
    if (-not (Test-Path $path)) { Save-Download 'https://go.microsoft.com/fwlink/p/?LinkId=2124703' $path }
    Assert-Signed $path 'Microsoft Corporation'
    return $path
}

function Resolve-InnoSetup {
    $candidates = @(
        (Join-Path $ToolsDir 'innosetup\ISCC.exe'),
        "${env:ProgramFiles(x86)}\Inno Setup 6\ISCC.exe",
        "$env:ProgramFiles\Inno Setup 6\ISCC.exe",
        "$env:LOCALAPPDATA\Programs\Inno Setup 6\ISCC.exe"
    )
    foreach ($candidate in $candidates) { if (Test-Path $candidate) { return $candidate } }

    # Install Inno Setup portably inside desktop\.tools (nothing system-wide).
    $tag = 'is-' + $InnoSetupVersion.Replace('.', '_')
    $installer = Join-Path $ToolsDir "downloads\innosetup-$InnoSetupVersion.exe"
    if (-not (Test-Path $installer)) { Save-Download "https://github.com/jrsoftware/issrc/releases/download/$tag/innosetup-$InnoSetupVersion.exe" $installer }
    Assert-Signed $installer 'Pyrsys'
    $target = Join-Path $ToolsDir 'innosetup'
    $process = Start-Process $installer -ArgumentList '/VERYSILENT', '/SUPPRESSMSGBOXES', '/CURRENTUSER', '/PORTABLE=1', "/DIR=`"$target`"", '/NOICONS', '/NORESTART' -Wait -PassThru
    if ($process.ExitCode -ne 0) { throw "Inno Setup installation failed ($($process.ExitCode))." }
    return (Join-Path $target 'ISCC.exe')
}

function Resolve-CaBundle([string] $php) {
    $ini = Join-Path $php 'php.ini'
    if (Test-Path $ini) {
        $line = Select-String -Path $ini -Pattern '^\s*curl\.cainfo\s*=\s*"?([^"]+)"?' | Select-Object -First 1
        if ($line -and (Test-Path $line.Matches[0].Groups[1].Value)) { return $line.Matches[0].Groups[1].Value }
    }
    if (Test-Path 'C:\laragon\etc\ssl\cacert.pem') { return 'C:\laragon\etc\ssl\cacert.pem' }
    $path = Join-Path $ToolsDir 'downloads\cacert.pem'
    if (-not (Test-Path $path)) { Save-Download 'https://curl.se/ca/cacert.pem' $path }
    return $path
}

# ---------------------------------------------------------------------------
# Icon
# ---------------------------------------------------------------------------

function New-AppIcon([string] $Source, [string] $Target) {
    Add-Type -AssemblyName System.Drawing
    $logo = [System.Drawing.Image]::FromFile($Source)
    $images = @()
    try {
        foreach ($size in 16, 20, 24, 32, 40, 48, 64, 128, 256) {
            $bitmap = New-Object System.Drawing.Bitmap $size, $size
            $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
            $graphics.InterpolationMode = 'HighQualityBicubic'
            $graphics.SmoothingMode = 'AntiAlias'
            $graphics.PixelOffsetMode = 'HighQuality'
            $graphics.Clear([System.Drawing.Color]::Transparent)
            $scale = [Math]::Min($size / $logo.Width, $size / $logo.Height)
            $w = [int]($logo.Width * $scale); $h = [int]($logo.Height * $scale)
            $graphics.DrawImage($logo, [int](($size - $w) / 2), [int](($size - $h) / 2), $w, $h)
            $graphics.Dispose()
            $stream = New-Object System.IO.MemoryStream
            $bitmap.Save($stream, [System.Drawing.Imaging.ImageFormat]::Png)
            $bitmap.Dispose()
            $images += , @($size, $stream.ToArray())
        }
    } finally { $logo.Dispose() }

    # ICO container with PNG-compressed entries.
    $out = New-Object System.IO.MemoryStream
    $writer = New-Object System.IO.BinaryWriter $out
    $writer.Write([UInt16]0); $writer.Write([UInt16]1); $writer.Write([UInt16]$images.Count)
    $offset = 6 + 16 * $images.Count
    foreach ($image in $images) {
        $size = $image[0]; $bytes = $image[1]
        $dimension = if ($size -ge 256) { 0 } else { $size }
        $writer.Write([byte]$dimension); $writer.Write([byte]$dimension)
        $writer.Write([byte]0); $writer.Write([byte]0)
        $writer.Write([UInt16]1); $writer.Write([UInt16]32)
        $writer.Write([UInt32]$bytes.Length); $writer.Write([UInt32]$offset)
        $offset += $bytes.Length
    }
    foreach ($image in $images) { $writer.Write([byte[]]$image[1]) }
    $writer.Flush()
    [System.IO.File]::WriteAllBytes($Target, $out.ToArray())
}

# ---------------------------------------------------------------------------
# Launcher
# ---------------------------------------------------------------------------

function Build-Launcher {
    Step 'Compiling the WebView2 launcher'
    $csc = Join-Path $env:WINDIR 'Microsoft.NET\Framework64\v4.0.30319\csc.exe'
    if (-not (Test-Path $csc)) { throw ".NET Framework 4.x compiler not found at $csc" }

    $sdk = Resolve-WebView2Sdk
    $launcherDir = Join-Path $DesktopDir 'launcher'
    $icon = Join-Path $launcherDir 'app.ico'
    if (-not (Test-Path $icon)) { New-AppIcon (Join-Path $RepoDir 'resources\logo\logo.png') $icon }

    New-Item -ItemType Directory -Force $BuildDir, $StageDir | Out-Null
    $assemblyInfo = Join-Path $BuildDir 'AssemblyInfo.cs'
    @"
using System.Reflection;
[assembly: AssemblyTitle("$ProductName")]
[assembly: AssemblyProduct("$ProductName")]
[assembly: AssemblyDescription("$ProductName desktop app")]
[assembly: AssemblyCompany("Barbaza Multi-Purpose Cooperative")]
[assembly: AssemblyCopyright("Barbaza Multi-Purpose Cooperative")]
[assembly: AssemblyVersion("$Version.0")]
[assembly: AssemblyFileVersion("$Version.0")]
"@ | Set-Content $assemblyInfo -Encoding utf8

    $references = @(
        'System.dll', 'System.Core.dll', 'System.Drawing.dll', 'System.Windows.Forms.dll',
        'System.Web.Extensions.dll', 'System.IO.Compression.dll', 'System.IO.Compression.FileSystem.dll',
        "$sdk\lib\net462\Microsoft.Web.WebView2.Core.dll", "$sdk\lib\net462\Microsoft.Web.WebView2.WinForms.dll"
    ) | ForEach-Object { "/reference:$_" }

    $sources = @((Get-ChildItem (Join-Path $launcherDir 'src') -Filter *.cs).FullName) + $assemblyInfo
    $arguments = @(
        '/nologo', '/target:winexe', '/platform:x64', '/optimize+', '/warnaserror-', '/utf8output',
        "/out:$(Join-Path $StageDir $ExeName)", "/win32icon:$icon", "/win32manifest:$(Join-Path $launcherDir 'app.manifest')"
    ) + $references + $sources

    & $csc @arguments
    if ($LASTEXITCODE -ne 0) { throw 'Launcher compilation failed.' }

    Copy-Item "$sdk\lib\net462\Microsoft.Web.WebView2.Core.dll", "$sdk\lib\net462\Microsoft.Web.WebView2.WinForms.dll", "$sdk\runtimes\win-x64\native\WebView2Loader.dll" $StageDir -Force
    Info "Built $ExeName"
}

# ---------------------------------------------------------------------------
# Offline server (full flavor)
# ---------------------------------------------------------------------------

function Build-FrontEnd {
    if ($SkipAssets) {
        if (-not (Test-Path (Join-Path $RepoDir 'public\build\manifest.json'))) { throw 'public\build is missing; run without -SkipAssets.' }
        return
    }
    Step 'Building front-end assets (Vite)'
    $node = Resolve-Node
    $env:PATH = "$node;$env:PATH"
    if (-not (Test-Path (Join-Path $RepoDir 'node_modules'))) { Invoke-Native 'npm.cmd' @('ci', '--no-audit', '--no-fund') }
    Invoke-Native 'npm.cmd' @('run', 'build')
}

function Stage-Laravel {
    Step 'Staging the Laravel app (production dependencies only)'
    $app = Join-Path $StageDir 'app'

    $excludeDirs = @('.git', '.github', '.claude', '.codex', '.cursor', '.idea', '.vscode', '.phpunit.cache',
        'node_modules', 'vendor', 'tests', 'desktop', 'docker', 'storage') |
        ForEach-Object { Join-Path $RepoDir $_ }
    $excludeDirs += (Join-Path $RepoDir 'public\storage'), (Join-Path $RepoDir 'bootstrap\cache')
    $excludeFiles = @('.env', '.env.*', '*.sql', '*.log', '.phpunit.result.cache', 'auth.json', '.mcp.json', 'boost.json',
        'AGENTS.md', 'CLAUDE.md', 'system-development-checklist.md', 'Dockerfile', '.dockerignore', 'phpunit.xml', 'hot', '*.sqlite')

    # /XJ: never follow junctions such as public\storage.
    Invoke-Robocopy $RepoDir $app (@('/XJ', '/XD') + $excludeDirs + @('/XF') + $excludeFiles)
    New-Item -ItemType Directory -Force (Join-Path $app 'bootstrap\cache') | Out-Null

    Invoke-Native 'composer' @('install', '--no-dev', '--optimize-autoloader', '--no-interaction', '--no-progress', '--no-scripts', '--prefer-dist') $app

    # standard-webhooks ships ports for other languages; only libraries\php is autoloaded.
    # Dropping the rest keeps the package well inside Windows' 260-character path limit.
    $webhooks = Join-Path $app 'vendor\standard-webhooks\standard-webhooks\libraries'
    if (Test-Path $webhooks) {
        Get-ChildItem $webhooks -Directory | Where-Object Name -ne 'php' | Remove-Item -Recurse -Force
    }
    $longest = Get-ChildItem $StageDir -Recurse -File | ForEach-Object { $_.FullName.Length - $StageDir.Length - 1 } | Measure-Object -Maximum
    Info "Longest path inside the package: $($longest.Maximum) characters"

    # Pre-build bootstrap\cache\packages.php and services.php. The app uses them as they are
    # when its data folder is on another drive and the caches cannot be relocated.
    $scratch = Join-Path $BuildDir 'discover-storage'
    New-Item -ItemType Directory -Force "$scratch\logs", "$scratch\framework\cache", "$scratch\framework\views" | Out-Null
    $env:LARAVEL_STORAGE_PATH = $scratch
    try { Invoke-Native (Join-Path (Resolve-Php) 'php.exe') @('artisan', 'package:discover', '--no-interaction') $app }
    finally { Remove-Item Env:\LARAVEL_STORAGE_PATH; Remove-Item $scratch -Recurse -Force }

    "$Version $(Get-Date -Format o)" | Set-Content (Join-Path $app '.desktop-build') -Encoding utf8
    Info "App staged ($('{0:N0}' -f ((Get-ChildItem $app -Recurse -File | Measure-Object Length -Sum).Sum / 1MB)) MB)"
}

function Stage-Php([string] $php) {
    Step "Bundling PHP from $php"
    $target = Join-Path $StageDir 'runtime\php'
    Invoke-Robocopy $php $target @('/XD', (Join-Path $php 'dev'), (Join-Path $php 'extras'),
        '/XF', 'php.ini', 'php.ini-*', 'php.ini.*', '*.pdb', 'phpdbg.exe', 'php8phpdbg.dll', 'php8embed.lib', 'php8apache2_4.dll', 'deplister.exe', 'news.txt', 'snapshot.txt')

    # Keep only the extensions php.ini loads.
    $needed = (Select-String -Path (Join-Path $DesktopDir 'runtime\php.ini') -Pattern '^\s*(zend_)?extension\s*=\s*(\w+)').Matches |
        ForEach-Object { "php_$($_.Groups[2].Value).dll" }
    Get-ChildItem (Join-Path $target 'ext') -Filter 'php_*.dll' | Where-Object { $needed -notcontains $_.Name } | Remove-Item -Force

    Copy-Item (Join-Path $php 'license.txt') (Join-Path $StageDir 'licenses\php.txt') -ErrorAction SilentlyContinue
}

function Stage-MySql([string] $mysql) {
    Step "Bundling MySQL from $mysql"
    $target = Join-Path $StageDir 'runtime\mysql'
    New-Item -ItemType Directory -Force "$target\bin", "$target\share" | Out-Null

    $binaries = 'mysqld.exe', 'mysqladmin.exe', 'mysqldump.exe', 'mysql.exe'
    foreach ($binary in $binaries) { Copy-Item (Join-Path $mysql "bin\$binary") "$target\bin" -Force }
    Get-ChildItem (Join-Path $mysql 'bin') -Filter *.dll | Where-Object { $_.Name -notmatch '-debug\.dll$' } | Copy-Item -Destination "$target\bin" -Force

    Invoke-Robocopy (Join-Path $mysql 'share\english') "$target\share\english"
    Invoke-Robocopy (Join-Path $mysql 'share\charsets') "$target\share\charsets"
    Get-ChildItem (Join-Path $mysql 'share') -File | Copy-Item -Destination "$target\share" -Force

    Copy-Item (Join-Path $mysql 'LICENSE') (Join-Path $StageDir 'licenses\mysql.txt') -ErrorAction SilentlyContinue
}

function Stage-RuntimeConfig([string] $php) {
    $config = Join-Path $StageDir 'runtime\config'
    New-Item -ItemType Directory -Force $config | Out-Null
    Copy-Item (Join-Path $DesktopDir 'runtime\php.ini'), (Join-Path $DesktopDir 'runtime\router.php') $config -Force
    Copy-Item (Resolve-CaBundle $php) (Join-Path $config 'cacert.pem') -Force
}

function Test-StagedPhp {
    Step 'Checking the bundled PHP'
    $php = Join-Path $StageDir 'runtime\php\php.exe'
    $modules = & $php -c (Join-Path $StageDir 'runtime\config\php.ini') "-dextension_dir=$(Join-Path $StageDir 'runtime\php\ext')" -m
    if ($LASTEXITCODE -ne 0) { throw 'The bundled PHP does not start.' }
    $required = 'curl', 'fileinfo', 'gd', 'intl', 'mbstring', 'openssl', 'pdo_mysql', 'zip', 'Zend OPcache'
    $missing = $required | Where-Object { $modules -notcontains $_ }
    if ($missing) { throw "The bundled PHP is missing: $($missing -join ', ')" }
    Info "PHP $((& $php -r 'echo PHP_VERSION;')) with all required extensions"
}

# ---------------------------------------------------------------------------
# Packages
# ---------------------------------------------------------------------------

function Build-Installer([string] $iscc) {
    Step 'Building the installer (Install or Portable)'
    New-Item -ItemType Directory -Force $DistDir | Out-Null
    $longest = (Get-ChildItem $StageDir -Recurse -File | ForEach-Object { $_.FullName.Length - $StageDir.Length - 1 } | Measure-Object -Maximum).Maximum
    $arguments = @(
        '/Q',
        "/DMaxDirLength=$(259 - 1 - $longest)",
        "/DAppVersion=$Version",
        "/DStageDir=$StageDir",
        "/DOutputDir=$DistDir",
        "/DOutputBaseFilename=$OutputBase-Setup",
        "/DIconFile=$(Join-Path $DesktopDir 'launcher\app.ico')"
    )
    if ($WebInstaller) { $arguments += '/DAutoInstall=1' }
    $arguments += (Join-Path $DesktopDir 'installer\bmpc.iss')
    & $iscc @arguments
    if ($LASTEXITCODE -ne 0) { throw 'Inno Setup failed.' }
    Info (Join-Path $DistDir "$OutputBase-Setup.exe")
}

function Build-PortableZip {
    Step 'Building the portable zip'
    New-Item -ItemType Directory -Force $DistDir | Out-Null
    $zip = Join-Path $DistDir "$OutputBase-Portable.zip"
    if (Test-Path $zip) { Remove-Item $zip -Force }

    Add-Type -AssemblyName System.IO.Compression, System.IO.Compression.FileSystem
    [System.IO.Compression.ZipFile]::CreateFromDirectory($StageDir, $zip, [System.IO.Compression.CompressionLevel]::Optimal, $true)

    # portable.flag tells the launcher to keep its data in a "data" folder beside it.
    $archive = [System.IO.Compression.ZipFile]::Open($zip, [System.IO.Compression.ZipArchiveMode]::Update)
    try {
        $entry = $archive.CreateEntry("$ProductName/portable.flag")
        $writer = New-Object System.IO.StreamWriter $entry.Open()
        $writer.WriteLine('This copy is portable: settings, database and documents are kept in the "data" folder next to it.')
        $writer.WriteLine('Delete this file to make the app use %LOCALAPPDATA%\BMPC Project Management instead.')
        $writer.Dispose()
    } finally { $archive.Dispose() }
    Info $zip
}

# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

$started = Get-Date

if ($LauncherOnly) {
    Build-Launcher
    return
}

if (Test-Path $StageDir) { Remove-Item $StageDir -Recurse -Force }
New-Item -ItemType Directory -Force $StageDir, (Join-Path $StageDir 'licenses'), (Join-Path $StageDir 'redist') | Out-Null

Build-Launcher

if ($Flavor -eq 'full') {
    $php = Resolve-Php
    $mysql = Resolve-MySql
    Build-FrontEnd
    Stage-Laravel
    Stage-Php $php
    Stage-MySql $mysql
    Stage-RuntimeConfig $php
    Test-StagedPhp
}

Copy-Item (Resolve-WebView2Bootstrapper) (Join-Path $StageDir 'redist') -Force

if ($ServerUrl) {
    @{ Mode = 'server'; ServerUrl = $ServerUrl.TrimEnd('/') } | ConvertTo-Json | Set-Content (Join-Path $StageDir 'desktop.defaults.json') -Encoding utf8
}

if (-not $NoInstaller) { Build-Installer (Resolve-InnoSetup) }
if (-not $NoPortableZip) { Build-PortableZip }

if ($WebInstaller -and -not $NoInstaller) {
    # config/desktop.php serves this file from the login page.
    New-Item -ItemType Directory -Force $PublishDir | Out-Null
    Copy-Item (Join-Path $DistDir "$OutputBase-Setup.exe") (Join-Path $PublishDir 'BMPC-Desktop-Setup.exe') -Force
    Step "Published the login-page installer (connects to $ServerUrl)"
    Info (Join-Path $PublishDir 'BMPC-Desktop-Setup.exe')
}

Step ("Done in {0:mm\:ss}" -f ((Get-Date) - $started))
Get-ChildItem $DistDir -File -ErrorAction SilentlyContinue | Where-Object Name -like "$OutputBase*" |
    ForEach-Object { Info ("{0}  ({1:N0} MB)" -f $_.Name, ($_.Length / 1MB)) }
