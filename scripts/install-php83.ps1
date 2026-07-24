# Install PHP 8.3 and Composer locally for this project; XAMPP remains unchanged.
[CmdletBinding()]
param(
    [string]$PhpVersion = '8.3.29'
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$toolsDir = Join-Path $projectRoot 'tools'
$phpDir = Join-Path $toolsDir 'php83'
$phpArchive = Join-Path $toolsDir "php-$PhpVersion-Win32-vs16-x64.zip"
$composerPhar = Join-Path $toolsDir 'composer.phar'
# PHP 8.3 releases move to the official archive once newer branches are published.
$phpUrl = "https://downloads.php.net/~windows/releases/archives/php-$PhpVersion-Win32-vs16-x64.zip"

New-Item -ItemType Directory -Force -Path $toolsDir | Out-Null

Write-Host "Downloading PHP $PhpVersion..." -ForegroundColor Cyan
Invoke-WebRequest -Uri $phpUrl -OutFile $phpArchive

if (Test-Path $phpDir) {
    Remove-Item -LiteralPath $phpDir -Force -Recurse
}
Expand-Archive -LiteralPath $phpArchive -DestinationPath $phpDir -Force
Remove-Item -LiteralPath $phpArchive -Force

@(
    'extension_dir = "ext"',
    'extension=curl',
    'extension=fileinfo',
    'extension=mbstring',
    'extension=openssl',
    'extension=pdo_mysql',
    'extension=mysqli',
    'extension=intl',
    'extension=zip',
    'date.timezone = "Asia/Bangkok"',
    'memory_limit = 256M',
    'upload_max_filesize = 20M',
    'post_max_size = 20M'
) | Set-Content -LiteralPath (Join-Path $phpDir 'php.ini') -Encoding ascii

Write-Host 'Downloading Composer...' -ForegroundColor Cyan
Invoke-WebRequest -Uri 'https://getcomposer.org/composer-stable.phar' -OutFile $composerPhar

@('@echo off', '"' + $phpDir + '\php.exe" "' + $composerPhar + '" %*') |
    Set-Content -LiteralPath (Join-Path $toolsDir 'composer.bat') -Encoding ascii

& (Join-Path $phpDir 'php.exe') -v
& (Join-Path $phpDir 'php.exe') -m | Select-String 'curl|mbstring|openssl|pdo_mysql|mysqli|intl'
& (Join-Path $phpDir 'php.exe') $composerPhar --version

Write-Host "`nInstallation complete." -ForegroundColor Green
Write-Host "PHP: $phpDir\php.exe"
Write-Host "Composer: $toolsDir\composer.bat"
