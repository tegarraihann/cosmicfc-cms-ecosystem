# Menjalankan COSMIC melalui ngrok

Project telah dilengkapi dua skrip PowerShell. Jalankan perintah dari folder `cosmic-app` menggunakan dua terminal terpisah.

## Terminal 1 — Laravel

```powershell
.\scripts\serve-local.ps1
```

Secara default Laravel menggunakan `http://127.0.0.1:8000`.

## Terminal 2 — ngrok

```powershell
.\scripts\start-ngrok.ps1
```

Salin alamat HTTPS pada baris `Forwarding`, misalnya `https://nama-random.ngrok-free.app`.

`AppServiceProvider` dan trusted proxy Laravel sudah dikonfigurasi untuk membaca `X-Forwarded-Proto` milik ngrok. Karena itu asset, redirect login, cookie, dan signed URL akan tetap menggunakan HTTPS.

## Menggunakan port lain

Nilai port pada kedua terminal harus sama:

```powershell
.\scripts\serve-local.ps1 -Port 8080
```

```powershell
.\scripts\start-ngrok.ps1 -Port 8080
```

## Menggunakan static domain ngrok

Jika akun memiliki assigned dev domain:

```powershell
.\scripts\start-ngrok.ps1 -Domain "domain-anda.ngrok-free.app"
```

Jangan menuliskan `https://` pada parameter `Domain`.

Untuk assigned/static domain, konfigurasi `.env` opsional berikut dapat dipakai agar URL yang dibuat dari command/queue juga memakai domain publik:

```dotenv
FORCE_HTTPS=true
PUBLIC_ORIGIN=https://domain-anda.ngrok-free.app
```

Setelah mengubah `.env`, jalankan:

```powershell
php artisan optimize:clear
```

Untuk domain acak, biarkan `PUBLIC_ORIGIN` kosong. Laravel akan mengikuti host dan scheme request ngrok secara otomatis.

Media Library menggunakan `PUBLIC_STORAGE_URL=/storage`, sehingga URL gambar bersifat relatif dan otomatis mengikuti domain localhost atau ngrok. Hindari mengisinya dengan URL localhost.

## Browser warning ngrok

Free plan ngrok menampilkan halaman peringatan pada kunjungan pertama. Ini berasal dari edge ngrok, bukan Laravel, sehingga tidak dapat dihapus oleh kode website. Pilihan resminya adalah:

- Pengunjung menekan `Visit Site` satu kali untuk endpoint tersebut.
- Klien API mengirim request header `ngrok-skip-browser-warning`.
- Menggunakan paket ngrok yang tidak memakai interstitial page.

Contoh akses API tanpa warning:

```powershell
Invoke-WebRequest -Uri "https://DOMAIN-NGROK" -Headers @{ "ngrok-skip-browser-warning" = "true" }
```

## Menghentikan server

Tekan `Ctrl+C` pada terminal Laravel dan terminal ngrok.

Jika port tetap tertahan:

```powershell
Get-NetTCPConnection -LocalPort 8000 -State Listen | Select-Object LocalAddress,LocalPort,OwningProcess
```

Periksa prosesnya terlebih dahulu:

```powershell
Get-Process -Id ID_PROSES
```

Lalu hentikan hanya proses yang sudah dipastikan benar:

```powershell
Stop-Process -Id ID_PROSES
```
