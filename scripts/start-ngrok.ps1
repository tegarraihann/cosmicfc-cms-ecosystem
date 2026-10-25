param(
    [int]$Port = 8000,
    [string]$Domain = ''
)

$ErrorActionPreference = 'Stop'

if (-not (Get-Command ngrok -ErrorAction SilentlyContinue)) {
    throw 'ngrok tidak ditemukan. Instal dengan: choco install ngrok'
}

ngrok config check | Out-Host

try {
    $null = Invoke-WebRequest -Uri "http://127.0.0.1:$Port" -UseBasicParsing -TimeoutSec 3
} catch {
    throw "Laravel belum merespons pada port $Port. Jalankan .\scripts\serve-local.ps1 -Port $Port di terminal pertama."
}

Write-Host "Membuka tunnel menuju http://127.0.0.1:$Port" -ForegroundColor Cyan
Write-Host 'Alamat HTTPS publik akan tampil pada bagian Forwarding.' -ForegroundColor DarkGray

if ($Domain) {
    ngrok http "http://127.0.0.1:$Port" --url "https://$Domain"
} else {
    ngrok http "http://127.0.0.1:$Port"
}
