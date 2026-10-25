# COSMIC CMS

Aplikasi utama COSMIC berbasis Laravel, Inertia.js, dan Vue 3. File HTML prototipe tetap disimpan di direktori induk sebagai acuan UI/UX.

## Menjalankan aplikasi

```bash
composer install
npm install
php artisan migrate --seed
php artisan storage:link
composer run dev
```

Buka `http://localhost:8000`. Halaman awal akan diarahkan ke portal login.

## Akun admin lokal

- Email: `admin@cosmic.test`
- Password: `CosmicAdmin2026!`

Ubah `ADMIN_EMAIL` dan `ADMIN_PASSWORD` di `.env`, kemudian jalankan `php artisan db:seed` untuk mengganti akun awal.

## Fondasi yang tersedia

- Autentikasi portal tertutup (registrasi publik dinonaktifkan)
- Middleware akses khusus admin
- Dashboard Inertia + Vue
- Model dan tabel `players`, `match_games`, dan `sponsors`
- Endpoint unggah foto `POST /admin/media`
- Validasi JPG, JPEG, PNG, atau WebP maksimal 5 MB
- Penyimpanan media pada disk publik Laravel

UI dashboard dan halaman CRUD akan dikembangkan bertahap dengan mengacu pada prototipe HTML di direktori induk.
