# เรียก Laravel ด้วย PHP 8.3 ของโปรเจกต์
[CmdletBinding()]
param([Parameter(ValueFromRemainingArguments = $true)][string[]]$Arguments)

$projectRoot = Split-Path -Parent $PSScriptRoot
$php = Join-Path $projectRoot 'tools\php83\php.exe'
if (-not (Test-Path $php)) { throw 'ยังไม่พบ PHP 8.3 กรุณารัน .\scripts\install-php83.ps1 ก่อน' }
& $php (Join-Path $projectRoot 'artisan') @Arguments
exit $LASTEXITCODE
