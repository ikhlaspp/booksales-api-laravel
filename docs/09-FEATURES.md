# 09 — Features (Backend)

> Penjelasan per-fitur dengan flow, controller, endpoint, dan side effect yang terlibat. Dokumen ini berguna kalau Anda ingin menambah/memodifikasi fitur tertentu.

---

## Index Fitur

1. [Autentikasi & Profile](#1-autentikasi--profile)
2. [Katalog & Pencarian Buku](#2-katalog--pencarian-buku)
3. [Manajemen Genre & Author](#3-manajemen-genre--author)
4. [Manajemen Buku](#4-manajemen-buku)
5. [Keranjang & Checkout](#5-keranjang--checkout)
6. [Manajemen Transaksi](#6-manajemen-transaksi)
7. [Dashboard Admin](#7-dashboard-admin)
8. [Manajemen User](#8-manajemen-user)
9. [Formulir Kontak](#9-formulir-kontak)
10. [Chat User ↔ Admin](#10-chat-user--admin)
11. [Auto-Cancel Expired Transactions](#11-auto-cancel-expired-transactions)

---

## 1. Autentikasi & Profile

### Endpoint
- `POST /api/login`, `POST /api/register`
- `GET /api/user`, `PUT /api/user/profile`, `PUT /api/user/password`

### Controller
[`AuthController`](../app/Http/Controllers/AuthController.php)

### Flow Inti
1. Login → return Bearer token
2. Frontend simpan token + user info di localStorage
3. Setiap request berikutnya: `Authorization: Bearer <token>`
4. Edit profile / password → PUT endpoint

### Field yang bisa di-edit di profile
- `name`, `email`, `address`, `city`, `postal_code`

> Field `address`, `city`, `postal_code` ditambahkan untuk mendukung shipping (alamat pengiriman). Kalau user belum isi, frontend Checkout akan munculkan modal address.

### Catatan keamanan
- Password hashed otomatis (cast `'hashed'` di Model + `Hash::make`)
- Token tidak ada expiry default
- Tidak ada email verification flow

Detail lengkap: [04-AUTHENTICATION.md](04-AUTHENTICATION.md).

---

## 2. Katalog & Pencarian Buku

### Endpoint
- `GET /api/catalog` — Untuk halaman publik utama (pencarian gabungan title + author + genre)
- `GET /api/books` — Untuk admin/dropdown (pencarian title only)
- `GET /api/books/{id}` — Detail buku

### Controllers
- [`PublicCatalogController`](../app/Http/Controllers/PublicCatalogController.php) untuk `/catalog`
- [`BookController`](../app/Http/Controllers/BookController.php) untuk `/books`

### Beda Antara `/catalog` dan `/books`

| Aspek | `/catalog` | `/books` |
|-------|-----------|----------|
| Auth | Public | Public (untuk show/index), Admin (untuk CRUD) |
| Search behavior | `title` OR `author.name` OR `genre.name` | `title` saja |
| Sort default | `latest()` (newest first) | tidak ada explicit order |
| Default `per_page` | 12 | 10 |

### Eager Loading

Keduanya eager-load `author` & `genre` untuk hindari N+1 query.

```php
Book::with(['author', 'genre'])->get();
```

### Pagination

Pakai Laravel paginator. Response format:
```json
{ "data": [...], "current_page": 1, "last_page": 5, "total": 60, ... }
```

---

## 3. Manajemen Genre & Author

### Endpoint (admin only untuk write)
- `GET/POST/PUT/DELETE /api/genres`
- `GET/POST/PUT/DELETE /api/authors`

### Controllers
- [`GenreController`](../app/Http/Controllers/GenreController.php)
- [`AuthorController`](../app/Http/Controllers/AuthorController.php)

### Pola Konsisten

Semua CRUD controller pakai pola yang sama:
- `index()` — paginated list dengan optional search
- `store()` — validate + create + return 201
- `show()` — return single object atau 404 dengan pesan Indonesia
- `update()` — find + validate (semua field opsional via `sometimes`) + update
- `destroy()` — delete + return success message

### Author punya `books_count`

`GET /api/authors` mengembalikan `books_count` (jumlah buku per author) via `withCount('books')`. Berguna untuk admin lihat author mana yang punya buku terbanyak.

### Author photo URL

Field `photo` bisa berisi URL absolut (mis. Wikipedia) atau path lokal. Frontend cek prefix `http` untuk decide.

---

## 4. Manajemen Buku

### Endpoint
- `GET /api/books`, `GET /api/books/{id}` — public
- `POST/PUT/DELETE /api/books` — admin

### Controller
[`BookController`](../app/Http/Controllers/BookController.php)

### Field

| Field | Validasi POST | Validasi PUT |
|-------|---------------|--------------|
| `title` | required, max 255 | sometimes, required, max 255 |
| `description` | nullable | nullable |
| `price` | required, numeric, min 0 | sometimes, required, numeric, min 0 |
| `stock` | required, integer, min 0 | sometimes, required, integer, min 0 |
| `cover_photo` | nullable, max 255 | nullable, max 255 |
| `file_path` | (tidak divalidasi di POST) | nullable |
| `genre_id` | nullable, exists | nullable, exists |
| `author_id` | nullable, exists | nullable, exists |

### Catatan implementasi
- Saat update via PUT, field yang tidak dikirim TIDAK di-set null (karena `sometimes`)
- `file_path` mendukung URL eksternal (dummy PDF di-seed pakai `https://www.w3.org/.../dummy.pdf`)
- `cover_photo` boleh URL absolut

### Side effect: stok ↔ checkout

Stok di-decrement otomatis saat status transaksi jadi `dibayar` (atomik). Jangan edit stok manual saat ada checkout in-flight — bisa race condition.

---

## 5. Keranjang & Checkout

### Endpoint
- `POST /api/transactions` (Customer only) — checkout

### Controller
[`TransactionController::store`](../app/Http/Controllers/TransactionController.php#L59)

### Flow

```
1. Frontend kirim items: [{book_id, quantity}, ...]
2. Backend ambil semua book dari DB (jangan trust harga dari frontend)
3. Validasi stok per item
4. Hitung:
   - subtotal     = Σ(book.price × quantity)
   - tax_amount   = round(subtotal × 0.11)         ← PPN 11%
   - shipping     = 10000                          ← Rp 10.000 flat
   - gross_amount = subtotal + tax + shipping
5. DB::transaction:
   - INSERT transactions (status='pending')
   - INSERT transaction_items (1 row per book)
6. Call Midtrans Snap::getSnapToken({transaction_details, customer_details, item_details})
7. UPDATE transactions SET snap_token = ...
8. Return { transaction, snap_token, client_key }
```

### Field yang DI-IGNORE (untuk historis)
- `shipping_address`, `city`, `postal_code` — frontend kirim, tapi backend abaikan
- Alamat dibaca dari profil user (yang sudah disimpan via `PUT /api/user/profile`)
- Future: bisa di-honor kalau ada use case "alamat berbeda per order"

### Pricing constant
- **Tax**: 11% (PPN Indonesia) — hardcoded di [`TransactionController.php:101`](../app/Http/Controllers/TransactionController.php#L101)
- **Shipping**: Rp 10.000 flat — hardcoded di [`TransactionController.php:102`](../app/Http/Controllers/TransactionController.php#L102)

Untuk ubah: ganti angka di controller (TIDAK di env, sengaja untuk konsistensi). Pertimbangkan migrasi ke config file kalau perlu sering ubah.

### Order number format

```
BS-YYYYMMDD-XXXXXX
   │  │        └── 6 char random dari uniqid()
   │  └── tanggal hari ini (Asia/Jakarta)
   └── prefix BookSales
```

Contoh: `BS-20260517-ABC123`

---

## 6. Manajemen Transaksi

### Endpoint
- `GET /api/transactions` (admin) — list semua dengan filter
- `GET /api/user/transactions` — list milik sendiri
- `GET /api/transactions/{id}` — detail (owner atau admin)
- `PUT /api/transactions/{id}` — update status
- `DELETE /api/transactions/{id}` (admin) — hapus
- `POST /api/midtrans/callback` — webhook Midtrans

### Controller
[`TransactionController`](../app/Http/Controllers/TransactionController.php)

### Status Lifecycle

```
                  ┌─────────────────────────────────────┐
                  │                                     │
                  ▼                                     │
              ┌─────────┐                               │
              │ pending │                               │
              └────┬────┘                               │
                   │                                    │
        ┌──────────┼──────────────────────┐             │
        │          │                      │             │
   (Midtrans:   (Midtrans:           (>1jam tanpa       │
   capture+    settlement)           bayar via cron)    │
   accept)        │                      │              │
        │         ▼                      │              │
        │     ┌─────────┐                │              │
        └────▶│ dibayar │                ▼              │
              └────┬────┘            ┌────────────┐     │
                   │                 │ dibatalkan │◀───┘
              (admin manual)         └────────────┘
                   │                       ▲
                   ▼                       │
              ┌─────────┐                  │
              │ dikirim │       (Midtrans: cancel/deny/expire)
              └────┬────┘
                   │
              (admin manual)
                   │
                   ▼
              ┌─────────┐
              │ selesai │
              └─────────┘
```

### Status update endpoints

| Triggered by | Endpoint | Frontend file |
|-------------|----------|---------------|
| User saat Snap onSuccess | `PUT /api/transactions/{id}` `{status: 'dibayar'}` | `Checkout.jsx` baris 290 |
| Midtrans webhook | `POST /api/midtrans/callback` | (server-to-server) |
| Admin manual ubah ke `dikirim`/`selesai` | `PUT /api/transactions/{id}` | `pages/admin/transactions/Form.jsx` |
| Auto-cancel job | (direct DB update) | scheduled command |
| Frontend countdown expired | `PUT /api/transactions/{id}` `{status: 'dibatalkan'}` | `Profile/index.jsx` (PendingCountdownBadge) |

### Owner check

`show()` dan `update()` di-protect: hanya owner atau admin yang boleh akses (line 256, 264 di TransactionController).

---

## 7. Dashboard Admin

### Endpoint
- `GET /api/dashboard` (admin only)

### Controller
[`DashboardController`](../app/Http/Controllers/DashboardController.php)

### Data yang dikembalikan

```json
{
  "total_users": 20,
  "total_books": 50,
  "total_transactions": 25,
  "revenue": "Rp 1.234.567",
  "recent_transactions": [ ... 5 latest ... ],
  "sales_trend": [ ... 6 months ... ]
}
```

### Cara hitung `revenue`
```sql
SELECT SUM(total_amount) FROM transactions WHERE status = 'selesai'
```
Hanya transaksi yang sudah selesai dihitung sebagai revenue final.

### Sales trend
```sql
-- MySQL
SELECT DATE_FORMAT(created_at, "%b") as month, COUNT(*) as terbeli
FROM transactions
WHERE created_at >= NOW() - INTERVAL 6 MONTH
GROUP BY month
ORDER BY MIN(created_at)

-- SQLite
SELECT strftime('%m', created_at) as month, COUNT(*) as terbeli
FROM transactions
WHERE created_at >= datetime('now', '-6 months')
GROUP BY month
ORDER BY MIN(created_at)
```

Controller auto-detect driver via `DB::connection()->getDriverName()`.

### Recent transactions

5 transaksi terbaru dengan format pre-formatted (tanggal, amount Rupiah, status capitalize) supaya frontend tinggal display.

---

## 8. Manajemen User

### Endpoint
- `GET /api/users` (admin only) — list user dengan search & filter role

### Controller
[`UserController`](../app/Http/Controllers/UserController.php)

### Query params
- `search` — cari di nama atau email
- `role` — filter exact `'admin'` atau `'user'`
- `page`, `per_page` (default 15)

### Implementasi minimalis

Saat ini controller hanya punya `index()`. Tidak ada endpoint untuk:
- Edit user oleh admin (mis. ban, promote ke admin)
- Hapus user
- Reset password user

Jika perlu, tambahkan method baru + route. Pastikan ada owner check (mis. admin tidak boleh delete dirinya sendiri).

---

## 9. Formulir Kontak

### Endpoint
- `POST /api/contact` (public)

### Controller
[`ContactController`](../app/Http/Controllers/ContactController.php)

### Validasi
- `name`: required, string, max 255
- `email`: required, email, max 255
- `subject`: required, string, max 255
- `message`: required, string, min 10

### Tidak ada notifikasi email

Saat ini submission hanya disimpan ke tabel `contacts`. Tidak ada email otomatis ke admin/user.

**Improvement**: tambah notification (mail, Slack) di controller setelah `Contact::create(...)`.

```php
Mail::to(config('mail.admin_address'))->send(new ContactReceived($contact));
```

### Akses admin

Saat ini frontend `/admin/contacts` lebih fokus ke chat inbox. Untuk lihat contact submissions, perlu endpoint baru misalnya:

```php
Route::middleware(['auth:sanctum', Admin::class])->get('/contacts', function () {
    return Contact::latest()->paginate(15);
});
```

(Belum diimplementasi — tabel `contacts` saat ini dipakai standalone, admin baca via DB tool.)

---

## 10. Chat User ↔ Admin

Detail lengkap di [07-CHAT-SYSTEM.md](07-CHAT-SYSTEM.md). Ringkasan:

- 1 user = 1 conversation dengan admin (UNIQUE constraint pada `user_id`)
- 3 tipe message: `text`, `image`, `transaction`
- Polling 5s untuk pesan baru (bukan WebSocket)
- Read tracking dipisah per direction
- File chat-image disimpan di `storage/app/public/chat-images/`

Endpoints:
- User: `/api/conversations` (GET/POST messages, GET poll, PUT read)
- Admin: `/api/admin/conversations` (GET list, GET detail, POST reply, PUT read)

---

## 11. Auto-Cancel Expired Transactions

Detail lengkap di [08-CONSOLE-COMMANDS.md](08-CONSOLE-COMMANDS.md). Ringkasan:

- Command: `php artisan transactions:cancel-expired`
- Scheduled tiap menit di `routes/console.php`
- Cari transaksi `status=pending` dan `created_at < (now - 1 hour)`
- Bulk update status jadi `dibatalkan`

Pastikan scheduler jalan di production (cron). Di dev: `php artisan schedule:work`.

---

## Berikutnya

- Ada masalah / mau debug → [10-TROUBLESHOOTING.md](10-TROUBLESHOOTING.md)
