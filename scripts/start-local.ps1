# Starts the local Laravel server in the background and prints the browser URL.
[CmdletBinding()]
param([int]$Port = 8002)

$projectRoot = Split-Path -Parent $PSScriptRoot
$php = Join-Path $projectRoot 'tools\php83\php.exe'
$artisan = Join-Path $projectRoot 'artisan'
$log = Join-Path $projectRoot 'storage\logs\local-server.log'
$errorLog = Join-Path $projectRoot 'storage\logs\local-server-error.log'

if (-not (Test-Path $php)) { throw 'PHP 8.3 was not found. Run scripts\install-php83.ps1 first.' }
if (Get-NetTCPConnection -LocalPort $Port -ErrorAction SilentlyContinue) { throw "Port $Port is already in use. Choose another port, for example: .\scripts\start-local.ps1 -Port 8003" }

$process = Start-Process -FilePath $php -ArgumentList @($artisan, 'serve', '--host=127.0.0.1', "--port=$Port", '--no-reload') -WorkingDirectory $projectRoot -WindowStyle Hidden -RedirectStandardOutput $log -RedirectStandardError $errorLog -PassThru
Start-Sleep -Seconds 2

if ($process.HasExited) { throw "The server stopped unexpectedly. See $log" }

Write-Host "SM HAIR DESIGN is running at http://127.0.0.1:$Port" -ForegroundColor Green
Write-Host "Stop it later with: Stop-Process -Id $($process.Id)"
