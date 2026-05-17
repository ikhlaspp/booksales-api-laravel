# 02 — Setup (Backend)

> Panduan lengkap untuk men-setup backend BookSales di mesin lokal Anda. Cocok untuk Windows (Laragon), Mac, dan Linux.

---

## 1. Prasyarat

Pastikan terinstall:

| Tool | Versi minimum | Cek dengan |
|------|---------------|-----------|
| **PHP** | 8.3 (dengan extension: `pdo_mysql`, `mbstring`, `openssl`, `gd`, `intl`, `bcmath`, `xml`) | `php -v` |
| **Composer** | 2.x | `composer --version` |
| **Node.js + npm** | Node 20+ | `node -v && npm -v` |
| **MySQL** (atau MariaDB) | 8.0+ | `mysql --version` |
| **Git** | apa pun | `git --version` |

### Windows: Pakai Laragon (Recommended)

Project ini di-develop di Laragon (`C:\laragon\www\booksales\`). Laragon sudah membundel PHP, MySQL, Apache/Nginx, dan Composer. Cukup taruh project di `C:\laragon\www\`.

URL otomatis di Laragon: `http://booksales-api-laravel.test` (pakai auto-virtual-host).

---

## 2. Clone & Install

```bash
# 1) Masuk ke folder backend
cd booksales-api-laravel

# 2) Install dependency PHP
composer install

# 3) Install dependency npm (untuk asset compilation jika perlu)
npm install
```

> Jika lambat, pakai mirror Indonesia: `composer config -g repo.packagist composer https://packagist.phpcomposer.com`

---

## 3. Konfigurasi `.env`

```bash
# Salin template
cp .env.example .env

# Generate APP_KEY
php artisan key:generate
```

Edit `.env` minimal seperti ini:

```dotenv
APP_NAME="BookSales API"
APP_ENV=local
APP_KEY=base64:...            # auto-generated
APP_DEBUG=true
APP_URL=http://localhost:8000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID

# ─── Database (PILIH SALAH SATU) ───

# Opsi A: MySQL (rekomendasi)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_booksales
DB_USERNAME=root
DB_PASSWORD=

# Opsi B: SQLite (default .env.example, paling cepat untuk dev)
# DB_CONNECTION=sqlite
# (tidak butuh setting lain, file disimpan di database/database.sqlite)

# ─── Session/Cache/Queue (default sudah OK) ───
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=local

# ─── Midtrans Sandbox (WAJIB diisi sebelum test checkout) ───
MIDTRANS_SERVER_KEY=<YOUR_MIDTRANS_SERVER_KEY>
MIDTRANS_CLIENT_KEY=<YOUR_MIDTRANS_CLIENT_KEY>
MIDTRANS_IS_PRODUCTION=false

# ─── Mail (opsional, default 'log' → email muncul di laravel.log) ───
MAIL_MAILER=log
```

### Cara dapat Midtrans Sandbox key:

1. Daftar gratis di https://dashboard.sandbox.midtrans.com/register
2. Login → **Settings** → **Access Keys**
3. Copy `Server Key` dan `Client Key`
4. Paste ke `.env`

Lebih lengkap: lihat [06-MIDTRANS-INTEGRATION.md](06-MIDTRANS-INTEGRATION.md).

---

## 4. Setup Database

### Opsi A — MySQL

1. Buat database kosong:
   ```sql
   CREATE DATABASE db_booksales CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
   Atau via Laragon: klik kanan **MySQL → Create database**.

2. Jalankan migration + seeder:
   ```bash
   php artisan migrate --seed
   ```

### Opsi B — SQLite (paling cepat)

```bash
# Buat file kosong
touch database/database.sqlite           # Linux/Mac
type nul > database\database.sqlite      # Windows CMD
New-Item database\database.sqlite        # Windows PowerShell

# Migrate + seed
php artisan migrate --seed
```

Output yang diharapkan:

```
   INFO  Preparing database.
  Creating migration table ......................... 25.32ms DONE

   INFO  Running migrations.
  0001_01_01_000000_create_users_table .............. 12.41ms DONE
  0001_01_01_000001_create_cache_table ..............  4.13ms DONE
  ... dst (12 migration)

   INFO  Seeding database.
  Database\Seeders\DatabaseSeeder .................. 1,234.56ms DONE
```

Jika berhasil, DB sudah berisi: 20 user, 20 genre, 20 author, 50 buku, 20 transaksi historis.

---

## 5. Symlink Storage (PENTING untuk upload chat)

```bash
php artisan storage:link
```

Ini membuat symlink dari `public/storage` → `storage/app/public/`, sehingga file di `storage/app/public/chat-images/` dapat diakses via URL `http://localhost:8000/storage/chat-images/xxx.jpg`.

> Tanpa langkah ini, lampiran chat tidak bisa di-preview di frontend.

---

## 6. Jalankan Server

Pilih salah satu:

### 6.1 Server biasa

```bash
php artisan serve
```

Server jalan di **http://localhost:8000**.

### 6.2 Server + Queue + Log Viewer + Vite (Concurrent)

```bash
composer run dev
```

Ini menjalankan 4 proses paralel pakai `npx concurrently`:

| Nama | Command | Fungsi |
|------|---------|--------|
| `server` | `php artisan serve` | HTTP server |
| `queue` | `php artisan queue:listen --tries=1 --timeout=0` | Worker queue |
| `logs` | `php artisan pail --timeout=0` | Live tail `laravel.log` di terminal |
| `vite` | `npm run dev` | Asset bundler (jika dipakai) |

Tekan `Ctrl+C` untuk stop semua proses.

### 6.3 Laragon Auto Virtual Host

Jika pakai Laragon, project otomatis bisa diakses di **`http://booksales-api-laravel.test`** tanpa perlu `php artisan serve`. Frontend tinggal set `VITE_API_BASE_URL=http://booksales-api-laravel.test/api`.

---

## 7. Setup Scheduler (untuk Auto-Cancel)

Job `transactions:cancel-expired` perlu di-trigger oleh scheduler tiap menit.

### Development (manual sesekali)

```bash
php artisan schedule:work    # Stay running, jalankan scheduler tiap menit
```

### Production (cron)

Tambah ke crontab:

```cron
* * * * * cd /path/to/booksales-api-laravel && php artisan schedule:run >> /dev/null 2>&1
```

Detail lebih lanjut: [08-CONSOLE-COMMANDS.md](08-CONSOLE-COMMANDS.md).

---

## 8. Verifikasi Setup

### 8.1 Cek API hidup

```bash
curl http://localhost:8000/api/catalog
# Harus return JSON dengan field "data" berisi array buku
```

### 8.2 Cek login bisa

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@toko.com","password":"adminpass"}'
# Harus return: { "access_token": "...", "user": {...}, "role": "admin" }
```

### 8.3 Cek endpoint admin pakai token

```bash
# Ambil token dari step 8.2
TOKEN="paste-token-disini"

curl http://localhost:8000/api/dashboard \
  -H "Authorization: Bearer $TOKEN"
# Harus return: { "total_users": 20, "total_books": 50, ... }
```

### 8.4 Run tests

```bash
composer run test
# Harus PASS semua (atau setidaknya tidak error config)
```

---

## 9. Composer Scripts

```bash
composer run setup     # First-time setup (composer install + key + migrate + npm)
composer run dev       # Concurrent dev mode
composer run test      # Jalankan PHPUnit
```

Lihat definisi lengkap di `composer.json` section `"scripts"`.

---

## 10. Troubleshooting Setup

| Gejala | Solusi |
|--------|--------|
| `Class "PDO" not found` | Install/enable extension `php-mysql` atau `php-sqlite3` |
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL belum jalan. Start MySQL service / Laragon |
| `Database (db_booksales) does not exist` | Buat database manual: `CREATE DATABASE db_booksales` |
| `Route [login] not defined` | Normal — middleware `auth` default mencoba redirect ke route web `login`. Untuk API, selalu kirim header `Accept: application/json` agar Laravel return 401 JSON |
| Permission denied storage/logs | `chmod -R 775 storage bootstrap/cache` |
| Snap.js error di frontend | Pastikan `MIDTRANS_CLIENT_KEY` di `.env` benar dan server di-restart |

Lebih banyak: [10-TROUBLESHOOTING.md](10-TROUBLESHOOTING.md).

---

## 11. Reset / Fresh Install

Kalau ingin reset DB dari nol:

```bash
php artisan migrate:fresh --seed
```

Atau hapus saja file `database/database.sqlite` (kalau pakai SQLite) lalu re-migrate.

> ⚠️ **WARNING**: `migrate:fresh` drops semua tabel. Jangan jalankan di production tanpa backup.

---

## Berikutnya

- Sudah jalan? Pelajari skema DB → [03-DATABASE.md](03-DATABASE.md)
- Mau test endpoint? → [05-API-REFERENCE.md](05-API-REFERENCE.md)
- Mau setup Midtrans? → [06-MIDTRANS-INTEGRATION.md](06-MIDTRANS-INTEGRATION.md)
