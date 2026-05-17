# BookSales API — Backend Laravel

> REST API resmi untuk aplikasi **BookSales / PustakaIkhlas** — toko buku online pasar Indonesia. Dibangun dengan **Laravel 13**, **PHP 8.3+**, **Laravel Sanctum**, **MySQL**, dan terintegrasi dengan **Midtrans Snap (Sandbox)** untuk pembayaran dalam **Rupiah (IDR)**.

API ini melayani frontend React (`booksales/`) dan menangani: autentikasi, katalog buku, manajemen genre & penulis, keranjang & checkout, pembayaran Midtrans, manajemen transaksi, dashboard admin, fitur chat user↔admin, dan formulir kontak.

---

## ⚡ Quick Start

```bash
# 1. Install dependency
composer install
npm install

# 2. Setup environment
cp .env.example .env
php artisan key:generate

# 3. Konfigurasi DB di .env (default: SQLite — ganti ke MySQL jika perlu)
#    DB_CONNECTION=mysql
#    DB_DATABASE=db_booksales

# 4. Jalankan migration + seeder
php artisan migrate --seed

# 5. Buat symlink storage (untuk akses gambar cover & lampiran chat)
php artisan storage:link

# 6. Konfigurasi Midtrans Sandbox di .env
#    MIDTRANS_SERVER_KEY=<YOUR_MIDTRANS_SERVER_KEY>
#    MIDTRANS_CLIENT_KEY=<YOUR_MIDTRANS_CLIENT_KEY>
#    MIDTRANS_IS_PRODUCTION=false

# 7. Jalankan server (pilih salah satu)
php artisan serve                # Server biasa @ http://localhost:8000
composer run dev                 # Server + queue + log viewer + vite (parallel)
```

Server siap di **`http://localhost:8000`**. Endpoint root API: **`http://localhost:8000/api`**.

### Akun default seeder

| Email | Password | Role |
|-------|----------|------|
| `admin@toko.com` | `adminpass` | admin |
| `admin2@toko.com` | `adminpass2` | admin |
| `budi@email.com` | `password123` | user |
| `siti@email.com` | `rahasia321` | user |

(Total 20 user dummy + 50 buku + 20 transaksi historis terseed di [`DatabaseSeeder.php`](database/seeders/DatabaseSeeder.php).)

---

## 📚 Dokumentasi Lengkap

Semua dokumentasi mendetail ada di folder [`docs/`](docs/). Mulai dari sini sesuai kebutuhan Anda:

| # | Dokumen | Untuk Anda yang ingin... |
|---|---------|--------------------------|
| 01 | [Architecture](docs/01-ARCHITECTURE.md) | Memahami stack, struktur folder, lifecycle request, dan keputusan desain |
| 02 | [Setup](docs/02-SETUP.md) | Setup lingkungan dev di Windows/Mac/Linux + konfigurasi `.env` lengkap |
| 03 | [Database](docs/03-DATABASE.md) | Skema 10 tabel, relasi, ERD, migration timeline, seeder breakdown |
| 04 | [Authentication](docs/04-AUTHENTICATION.md) | Cara kerja Sanctum, middleware `admin`/`customer`, alur login & token |
| 05 | [API Reference](docs/05-API-REFERENCE.md) | Daftar **semua** endpoint + request/response sample + error format |
| 06 | [Midtrans Integration](docs/06-MIDTRANS-INTEGRATION.md) | Setup sandbox, alur Snap, callback signature verification, decode status |
| 07 | [Chat System](docs/07-CHAT-SYSTEM.md) | Arsitektur chat (Conversation, Message), polling, attachment & tx reference |
| 08 | [Console Commands](docs/08-CONSOLE-COMMANDS.md) | Scheduled job `transactions:cancel-expired` & cara menjalankan scheduler |
| 09 | [Features](docs/09-FEATURES.md) | Penjelasan per-fitur (katalog, keranjang, checkout, dashboard, contact, dll) |
| 10 | [Troubleshooting](docs/10-TROUBLESHOOTING.md) | Masalah umum + cara debug pakai logs, pail, dan tinker |

---

## 🛠️ Composer Scripts

```bash
composer run setup       # First-time setup (install + key + migrate + npm)
composer run dev         # Concurrent: server + queue + log viewer + vite
composer run test        # Jalankan PHPUnit test suite
```

---

## 🗺️ Cheat-sheet Endpoint Cepat

| Method | Endpoint | Auth | Tujuan |
|--------|----------|------|--------|
| `POST` | `/api/login` | — | Dapatkan Bearer token |
| `POST` | `/api/register` | — | Daftar user baru |
| `GET` | `/api/catalog` | — | Katalog publik (search, genre filter, paginated) |
| `GET` | `/api/books/{id}` | — | Detail buku |
| `POST` | `/api/transactions` | Customer | Checkout + dapat `snap_token` |
| `POST` | `/api/midtrans/callback` | — (webhook) | Notifikasi pembayaran dari Midtrans |
| `GET` | `/api/dashboard` | Admin | Stats homepage admin |
| `POST` | `/api/contact` | — | Submit formulir kontak |
| `GET` | `/api/conversations` | Auth | Chat user dengan admin |

Daftar lengkap: lihat [docs/05-API-REFERENCE.md](docs/05-API-REFERENCE.md).

---

## 🚦 Aturan Project (untuk Developer)

1. **Currency** selalu IDR (`Rp` + format `id-ID`)
2. **Pajak** fixed 11%, **shipping** fixed Rp 10.000 (logic di backend, jangan dipindah ke frontend)
3. **Roles** hanya `admin` dan `user`
4. **Status transaksi** hanya: `pending`, `dibayar`, `dikirim`, `selesai`, `dibatalkan`
5. **Midtrans** WAJIB pakai mode sandbox di development
6. Untuk menambah fitur baru, ikuti pola yang ada di `app/Http/Controllers/` dan tambahkan route di `routes/api.php`
7. Jangan menggunakan session-based auth — semua endpoint protected pakai Bearer token via Sanctum

Detail aturan: lihat [docs/01-ARCHITECTURE.md](docs/01-ARCHITECTURE.md) dan [`CLAUDE.md`](../CLAUDE.md) di root project.

---

## 🔗 Repo Frontend

Frontend React-nya ada di folder kakak: **`../booksales/`**.
Lihat [`../booksales/README.md`](../booksales/README.md) untuk setup-nya.

---

## 📜 Lisensi

MIT
