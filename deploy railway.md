# Deployment Laravel 12 ke Railway

Dokumen ini mencatat deployment project `pencatatan_uang_web` ke Railway, termasuk konfigurasi MySQL, PHP, build Vite, session, dan masalah yang pernah terjadi.

## Prinsip Railway

- Railway dapat membuat ulang container kapan saja.
- Gunakan MySQL Railway untuk data production.
- Build frontend harus dilakukan saat build image, bukan Start Command.
- Gunakan session database agar session tidak bergantung pada filesystem container.
- Untuk satu instance, cache file cukup. Redis belum diperlukan.
- Seeder tidak boleh dijalankan otomatis pada setiap deploy.

## 1. Push repository

Dari komputer lokal:

```powershell
cd D:\\COBA\\pencatatan_uang_web
git status
git add .
git commit -m "Prepare Laravel production deployment"
git push origin main
```

Buat project Railway dan hubungkan repository GitHub private. Jika repository tidak terlihat, berikan izin GitHub App Railway ke repository tersebut.

## 2. Tambahkan MySQL

Pada project Railway:

1. Klik **New**.
2. Pilih **Database â†’ MySQL**.
3. Tunggu service berstatus running.
4. Buka Variables pada service MySQL.
5. Catat nama service dan nama variabel yang diberikan.

Biasanya tersedia `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`, dan `MYSQL_URL`. Gunakan nama aktual yang terlihat pada dashboard, karena dapat berbeda menurut template.

## 3. Environment aplikasi

Pada service aplikasi isi:

```env
APP_NAME=Pencatatan Uang
APP_ENV=production
APP_DEBUG=false
APP_URL=https://DOMAIN_RAILWAY_ATAU_CUSTOM_DOMAIN
APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=Asia/Jakarta

APP_KEY=base64:KUNCI_YANG_TETAP

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
DB_URL=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

CACHE_STORE=file
QUEUE_CONNECTION=sync
LOG_CHANNEL=stack
LOG_LEVEL=error
```

Jika nama service bukan `MySQL`, sesuaikan bagian referensi `${{NamaService.VARIABEL}}`.

Jika masih memakai URL HTTP sementara, gunakan `SESSION_SECURE_COOKIE=false`. Setelah HTTPS aktif, ubah menjadi `true`.

APP_KEY wajib tetap sama. Jangan menjalankan `php artisan key:generate` pada setiap deploy.

## 4. Versi PHP dan Composer

Project meminta PHP `^8.2`. Gunakan PHP 8.3 atau 8.4 sesuai kebutuhan `composer.lock`.

Pernah terjadi build gagal karena Railway memakai PHP 8.2.33 sedangkan package Symfony pada lock file meminta PHP 8.4.1. Jika error ini muncul:

- gunakan PHP 8.4, atau
- regenerate lock file dengan target PHP 8.2/8.3 dan jalankan test sebelum push.

Untuk Nixpacks, variable yang umum digunakan:

```env
NIXPACKS_PHP_VERSION=8.3
```

Setelah package berubah, periksa kompatibilitas:

```bash
composer check-platform-reqs --no-dev
```

Jangan menjalankan `composer update` secara acak pada server production.

## 5. Build dan Start Command

Build Command:

```bash
composer install --no-dev --optimize-autoloader --no-interaction && npm ci && npm run build
```

Jika Railway sudah otomatis menjalankan Composer:

```bash
npm ci && npm run build
```

Start Command:

```bash
php artisan serve --host=0.0.0.0 --port=$PORT
```

Jangan menaruh `npm ci` atau `npm run build` di Start Command. Start Command hanya menjalankan aplikasi.

## 6. Pre-deploy Command

Gunakan:

```bash
php artisan migrate --force
```

Jangan gunakan:

```bash
php artisan migrate --force && php artisan db:seed --force
```

Seeder hanya dijalankan sekali melalui Railway Shell setelah database baru dibuat:

```bash
php artisan db:seed --force
```

Seeder project membuat dua bengkel, owner, customer umum, katalog, dan produk demo. Seeder juga mengatur ulang password owner demo, sehingga tidak boleh dijalankan pada setiap deploy.

## 7. Healthcheck

Isi Healthcheck Path:

```text
/up
```

Pastikan Start Command mendengarkan `0.0.0.0` dan memakai `$PORT`.

## 8. Error host MySQL

Error:

```text
Could not resolve host: mysql.railway.internal
```

berarti service MySQL belum ada, belum terhubung ke environment aplikasi, atau DB_HOST ditulis manual dengan hostname yang salah.

Perbaikan:

1. Buat service MySQL pada project yang sama.
2. Pastikan aplikasi dan database berada pada environment yang sama.
3. Gunakan reference variables dari service MySQL.
4. Periksa DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, dan DB_PASSWORD.
5. Deploy ulang.

## 9. CSS/Tailwind tidak muncul

Penyebab utama adalah `npm run build` tidak dijalankan atau dijalankan setelah container start.

Gunakan:

```bash
npm ci
npm run build
```

Pastikan output memiliki:

```text
public/build/manifest.json
public/build/assets/
```

Jangan menggunakan `npm run dev` pada production.

## 10. Error npm EBUSY

Error yang pernah muncul:

```text
EBUSY: resource busy or locked, rmdir '/app/node_modules/.cache'
```

Ini adalah konflik cache `node_modules` build container, bukan error Laravel.

Solusi:

1. Pastikan npm berada di Build Command.
2. Bersihkan build cache Railway jika tersedia.
3. Deploy commit baru.
4. Jika masih gagal, gunakan `npm install` sementara sebagai workaround, lalu kembali ke `npm ci` setelah cache bersih.

Jangan menjalankan npm install setiap kali aplikasi start.

## 11. Error 419 Page Expired

Periksa:

```env
APP_KEY=base64:KUNCI_YANG_TETAP
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

Kemudian pastikan:

- APP_URL sama dengan domain yang dibuka.
- HTTPS benar-benar aktif jika secure cookie true.
- Migration tabel sessions sudah berhasil.
- DB_URL kosong jika memakai DB_*.
- Cookie lama dihapus atau buka private window.
- Jalankan melalui Railway Shell:

```bash
php artisan optimize:clear
```

## 12. Deployment otomatis berikutnya

Setelah perubahan:

```bash
git add .
git commit -m "Update application"
git push origin main
```

Railway akan menjalankan build dan deploy. Urutan yang benar:

```text
composer install
npm ci dan npm run build
php artisan migrate --force
Start Command
Healthcheck /up
```

Jangan menjalankan `db:seed --force` atau `key:generate` pada deployment rutin.

## 13. Checklist akhir

- Repository private sudah terhubung.
- MySQL ada pada project/environment yang sama.
- APP_KEY tetap.
- APP_DEBUG=false.
- DB_URL kosong.
- Build Command membangun Vite.
- Start Command memakai `0.0.0.0` dan `$PORT`.
- Pre-deploy hanya migrasi.
- Healthcheck `/up`.
- HTTPS aktif sebelum secure cookie true.
- Seeder hanya sekali pada database baru.
- `.env`, password, dan private key tidak di-commit.


