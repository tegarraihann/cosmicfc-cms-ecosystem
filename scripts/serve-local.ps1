param(
    [int]$Port = 8000
)

$ErrorActionPreference = 'Stop'
$projectPath = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $projectPath

Write-Host "COSMIC Laravel berjalan di http://127.0.0.1:$Port" -ForegroundColor Cyan
Write-Host 'Biarkan terminal ini tetap terbuka.' -ForegroundColor DarkGray

php artisan serve --host=127.0.0.1 --port=$Port
