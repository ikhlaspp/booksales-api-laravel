# 05 — API Reference (Backend)

> Referensi lengkap semua endpoint REST di BookSales API. Setiap endpoint dilengkapi: method, path, middleware, parameter, contoh request, contoh response, dan kemungkinan error.

**Base URL (development):** `http://localhost:8000/api`

**Format response:** JSON

**Auth header:** `Authorization: Bearer <token>` (untuk endpoint protected)

---

## Daftar Endpoint per Kategori

### 🔓 Public (tanpa auth)
- [`POST /login`](#post-login) — Autentikasi
- [`POST /register`](#post-register) — Daftar user baru
- [`GET /catalog`](#get-catalog) — Katalog publik
- [`GET /books`](#get-books) — List buku
- [`GET /books/{id}`](#get-booksid) — Detail buku
- [`GET /genres`](#get-genres) — List genre
- [`GET /genres/{id}`](#get-genresid) — Detail genre
- [`GET /authors`](#get-authors) — List penulis
- [`GET /authors/{id}`](#get-authorsid) — Detail penulis
- [`POST /contact`](#post-contact) — Submit form kontak
- [`POST /midtrans/callback`](#post-midtranscallback) — Webhook Midtrans

### 🔐 Authenticated (sembarang role)
- [`GET /user`](#get-user) — User saat ini
- [`PUT /user/profile`](#put-userprofile) — Update profil
- [`PUT /user/password`](#put-userpassword) — Ubah password
- [`GET /user/transactions`](#get-usertransactions) — Transaksi sendiri
- [`GET /transactions/{id}`](#get-transactionsid) — Detail transaksi
- [`PUT /transactions/{id}`](#put-transactionsid) — Update status transaksi
- [`GET /conversations`](#get-conversations) — Chat sendiri (user)
- [`POST /conversations/messages`](#post-conversationsmessages) — Kirim chat
- [`GET /conversations/messages`](#get-conversationsmessages) — Polling pesan baru
- [`PUT /conversations/read`](#put-conversationsread) — Mark all admin msgs as read

### 🛒 Customer only (`role: user`)
- [`POST /transactions`](#post-transactions) — Checkout

### 👑 Admin only (`role: admin`)
- [`GET /dashboard`](#get-dashboard) — Stats overview
- [`GET /users`](#get-users) — List user
- [`POST /genres`](#post-genres) — Tambah genre
- [`PUT /genres/{id}`](#put-genresid) — Update genre
- [`DELETE /genres/{id}`](#delete-genresid) — Hapus genre
- [`POST /authors`](#post-authors) — Tambah author
- [`PUT /authors/{id}`](#put-authorsid) — Update author
- [`DELETE /authors/{id}`](#delete-authorsid) — Hapus author
- [`POST /books`](#post-books) — Tambah buku
- [`PUT /books/{id}`](#put-booksid) — Update buku
- [`DELETE /books/{id}`](#delete-booksid) — Hapus buku
- [`GET /transactions`](#get-transactions) — List semua transaksi
- [`DELETE /transactions/{id}`](#delete-transactionsid) — Hapus transaksi
- [`GET /admin/conversations`](#get-adminconversations) — Inbox chat admin
- [`GET /admin/conversations/{id}`](#get-adminconversationsid) — Detail percakapan
- [`POST /admin/conversations/{id}/messages`](#post-adminconversationsidmessages) — Balas chat
- [`PUT /admin/conversations/{id}/read`](#put-adminconversationsidread) — Mark user msgs as read

---

## Authentication

### `POST /login`

Autentikasi dengan email & password, return Bearer token.

**Request body:**
```json
{
  "email": "admin@toko.com",
  "password": "adminpass"
}
```

**Response 200:**
```json
{
  "message": "Login berhasil",
  "user": { "id": 3, "name": "Admin Utama", "email": "...", "role": "admin", ... },
  "role": "admin",
  "access_token": "1|abcdef...xyz",
  "token_type": "Bearer"
}
```

**Errors:** `401` (kredensial invalid), `422` (validasi gagal).

Detail lengkap: [04-AUTHENTICATION.md](04-AUTHENTICATION.md#51-post-apilogin).

---

### `POST /register`

Daftar user baru. Role auto-set ke `user`.

**Request body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "rahasia123"
}
```

**Response 201:** Sama dengan login, plus `"role": "user"`.

**Errors:** `422` (email sudah terdaftar / password < 8 / dst).

---

## User Profile

### `GET /user`

Return data user yang sedang login.

**Auth:** Required.

**Response 200:**
```json
{
  "id": 3, "name": "Admin Utama", "email": "...", "role": "admin",
  "address": "Jl. ...", "city": "Jakarta", "postal_code": "12190",
  "created_at": "...", "updated_at": "...", "last_access": "..."
}
```

---

### `PUT /user/profile`

Update profil sendiri (nama, email, alamat, kota, kode pos).

**Auth:** Required.

**Request body (semua opsional):**
```json
{
  "name": "Nama Baru",
  "email": "new@email.com",
  "address": "Jl. Baru No. 1",
  "city": "Bandung",
  "postal_code": "40115"
}
```

**Response 200:** User object terbaru.
**Response 422:** Jika body kosong (`"Tidak ada field yang diubah."`) atau email duplikat.

---

### `PUT /user/password`

Ubah password sendiri (perlu password lama untuk verifikasi).

**Auth:** Required.

**Request body:**
```json
{
  "current_password": "lama123",
  "new_password": "baru1234",
  "new_password_confirmation": "baru1234"
}
```

**Response 200:** `{ "message": "Password berhasil diubah." }`
**Response 422:** Jika current password salah atau new_password tidak match confirmation.

---

## Katalog Publik

### `GET /catalog`

Endpoint utama untuk halaman katalog publik. Mendukung pencarian gabungan (title + author name + genre name).

**Auth:** Tidak perlu.

**Query params:**
- `search` (string, optional) — Cari di title, nama author, atau nama genre
- `genre_id` (integer, optional) — Filter by genre
- `page` (integer, optional, default 1)
- `per_page` (integer, optional, default 12)

**Request:**
```http
GET /api/catalog?search=harry&genre_id=2&per_page=12&page=1
```

**Response 200 (Laravel paginator):**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 3,
      "title": "Harry Potter dan Batu Bertuah",
      "description": "Awal kisah penyihir cilik.",
      "price": "150000.00",
      "stock": 20,
      "cover_photo": "https://covers.openlibrary.org/b/id/10580458-L.jpg",
      "file_path": "https://...",
      "genre_id": 2,
      "author_id": 3,
      "author": { "id": 3, "name": "J.K. Rowling", "photo": "...", "bio": "..." },
      "genre": { "id": 2, "name": "Fantasi", "description": "..." }
    }
  ],
  "first_page_url": "...",
  "from": 1,
  "last_page": 3,
  "last_page_url": "...",
  "links": [...],
  "next_page_url": "...",
  "path": "...",
  "per_page": 12,
  "prev_page_url": null,
  "to": 12,
  "total": 32
}
```

---

### `GET /books`

Sama seperti `/catalog` tapi minus pencarian author/genre name (hanya title).

**Auth:** Tidak perlu (admin pakai endpoint yang sama untuk dropdown).

**Query params:** `search` (title saja), `genre_id`, `page`, `per_page` (default 10).

**Response:** Paginator buku dengan relasi `author` + `genre`.

---

### `GET /books/{id}`

Detail buku tunggal.

**Response 200:** Single book object dengan `author` + `genre` ter-load.
**Response 404:** Jika buku tidak ditemukan.

---

### `GET /genres`

List genre dengan optional search.

**Query params:** `search` (cari di nama), `per_page` (default 10).

**Response 200:** Paginator object genre.

---

### `GET /genres/{id}`

Detail satu genre.

**Response 404:** `{ "message": "Data tidak ditemukan" }`

---

### `GET /authors`

List author dengan jumlah buku-nya (`books_count`).

**Query params:** `search`, `per_page`.

**Response 200:**
```json
{
  "data": [
    { "id": 1, "name": "Andrea Hirata", "photo": "...", "bio": "...", "books_count": 5 },
    ...
  ],
  ...
}
```

---

### `GET /authors/{id}`

Detail satu author. Response 404 dengan pesan Bahasa Indonesia jika tidak ada.

---

## Contact Form

### `POST /contact`

Submit formulir kontak (public, no auth).

**Request body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "subject": "Pertanyaan tentang pengiriman",
  "message": "Halo, saya ingin tanya bagaimana cara pengiriman ke luar Jawa?"
}
```

**Aturan validasi:**
- `name`: required, string, max 255
- `email`: required, valid email, max 255
- `subject`: required, string, max 255
- `message`: required, string, min 10 karakter

**Response 201:**
```json
{
  "message": "Pesan berhasil dikirim",
  "data": { "id": 1, "name": "...", "email": "...", "subject": "...", "message": "...", "created_at": "...", "updated_at": "..." }
}
```

**Note:** Admin baca message ini via dashboard kontak (frontend `/admin/contacts`). Saat ini ditampilkan bersamaan dengan inbox chat — lihat [07-CHAT-SYSTEM.md](07-CHAT-SYSTEM.md).

---

## Transactions

### `POST /transactions` (Checkout)

Buat transaksi baru + dapat Snap token Midtrans. **HANYA untuk role `user`/`customer`.**

**Auth:** Required + Customer middleware.

**Request body:**
```json
{
  "items": [
    { "book_id": 1, "quantity": 2 },
    { "book_id": 3, "quantity": 1 }
  ]
}
```

> Note: Field `shipping_address`, `city`, `postal_code` saat ini **tidak digunakan** di controller — alamat dibaca dari profil user yang sudah disimpan via `PUT /user/profile`. Frontend tetap mengirimkannya tapi diabaikan.

**Proses backend:**
1. Validasi: items tidak kosong, semua `book_id` valid, quantity ≥ 1
2. Cek stok semua buku (error 422 kalau ada yang kurang)
3. Hitung `subtotal = Σ(price × qty)` dari DB
4. Hitung `tax = round(subtotal × 0.11)`, `shipping = 10000`
5. `gross_amount = subtotal + tax + shipping`
6. Generate `order_number = BS-YYYYMMDD-XXXXXX`
7. `DB::transaction(...)` → INSERT transaction + transaction_items
8. Call `Midtrans Snap::getSnapToken($params)`
9. Update `snap_token` di transaction row
10. Return JSON

**Response 201:**
```json
{
  "transaction": {
    "id": 25, "order_number": "BS-20260517-ABC123",
    "customer_id": 1, "book_id": 1,
    "subtotal": "250000.00", "tax_amount": "27500.00",
    "shipping_cost": "10000.00", "total_amount": "287500.00",
    "status": "pending", "snap_token": "abc-xyz-123",
    "payment_type": null, "customer": {...},
    "items": [
      { "id": 50, "transaction_id": 25, "book_id": 1, "quantity": 2, "price": "80000.00", "book": {...} },
      { "id": 51, "transaction_id": 25, "book_id": 3, "quantity": 1, "price": "150000.00", "book": {...} }
    ],
    "created_at": "...", "updated_at": "..."
  },
  "snap_token": "abc-xyz-123",
  "client_key": "<YOUR_MIDTRANS_CLIENT_KEY>"
}
```

**Errors:**
- `422` — Validasi gagal / stok kurang (`"Stok buku \"...\" tidak mencukupi. Tersedia: N."`)
- `404` — Buku tidak ditemukan
- `403` — User adalah admin (tidak boleh checkout)

Detail flow Midtrans: [06-MIDTRANS-INTEGRATION.md](06-MIDTRANS-INTEGRATION.md).

---

### `GET /transactions` (Admin only)

List semua transaksi dengan filter & search.

**Auth:** Required + Admin middleware.

**Query params:**
- `search` — Cari di `order_number` atau nama customer
- `status` — Filter exact: `pending` | `dibayar` | `dikirim` | `selesai` | `dibatalkan`
- `page`, `per_page` (default 15)

**Response 200:** Paginator transaction dengan `customer` + `book` + `items.book` ter-load.

---

### `GET /user/transactions`

List transaksi user yang sedang login (untuk halaman profil).

**Auth:** Required.

**Query params:** `search` (di order_number atau title buku), `status`, `page`.

**Response 200:** Paginator (`per_page` = 10, hardcoded).

---

### `GET /transactions/{id}`

Detail satu transaksi. User biasa hanya boleh akses transaksinya sendiri, admin boleh akses semua.

**Auth:** Required.

**Response 200:** Single transaction object dengan relasi.
**Response 403:** Jika bukan owner & bukan admin.

---

### `PUT /transactions/{id}`

Update status transaksi (umumnya untuk konfirmasi pembayaran dari frontend).

**Auth:** Required (owner atau admin).

**Request body:**
```json
{ "status": "dibayar" }
```

**Aturan validasi:** `status` opsional, harus salah satu dari 5 nilai enum.

**Side effect penting:** Kalau status berubah jadi `dibayar` (dari status sebelumnya yang BUKAN `dibayar`), stok semua buku di items akan di-decrement secara atomik.

**Response 200:** Transaction object terbaru.

---

### `DELETE /transactions/{id}` (Admin only)

Hapus transaksi permanently (cascade ke transaction_items).

**Response 200:** `{ "message": "Transaction deleted successfully" }`

---

## Midtrans Webhook

### `POST /midtrans/callback`

Endpoint webhook server-to-server dari Midtrans. **TIDAK PERLU AUTH** (signature verification dipakai sebagai gantinya).

**Request body (dari Midtrans):**
```json
{
  "order_id": "BS-20260517-ABC123",
  "status_code": "200",
  "gross_amount": "287500.00",
  "signature_key": "<sha512_hash>",
  "transaction_status": "settlement",
  "fraud_status": "accept",
  "payment_type": "bank_transfer",
  ...
}
```

**Verifikasi signature:**
```
expected = sha512(order_id + status_code + gross_amount + server_key)
if (signature_key !== expected) → 403 Invalid signature
```

**Mapping `transaction_status` → status BookSales:**

| Midtrans transaction_status | fraud_status | BookSales status |
|----------------------------|--------------|-------------------|
| `capture` | `accept` | `dibayar` |
| `capture` | `challenge` | `pending` |
| `settlement` | — | `dibayar` |
| `pending` | — | `pending` |
| `cancel`, `deny`, `expire` | — | `dibatalkan` |

**Side effect:** Jika status jadi `dibayar`, stok di-decrement atomik.

**Response:** `{ "message": "OK" }` (200)

Detail integrasi: [06-MIDTRANS-INTEGRATION.md](06-MIDTRANS-INTEGRATION.md).

---

## Admin — Resource Management

### `POST /genres`

```json
{ "name": "Slice of Life", "description": "Cerita keseharian." }
```

Response 201: `{ "message": "Genre created successfully", "data": {...} }`

### `PUT /genres/{id}`

Sama, semua field optional. Response 200 atau 404.

### `DELETE /genres/{id}`

Response 200: `{ "message": "Genre deleted successfully" }` atau 404.

---

### `POST /authors`

```json
{ "name": "Nama Author", "photo": "https://...", "bio": "Bio singkat." }
```

### `PUT /authors/{id}` & `DELETE /authors/{id}`

Pola sama dengan genre.

---

### `POST /books`

```json
{
  "title": "Judul Buku",
  "description": "Deskripsi",
  "price": 100000,
  "stock": 25,
  "cover_photo": "https://...",
  "genre_id": 1,
  "author_id": 2
}
```

**Validasi:**
- `title`: required, max 255
- `price`: required, numeric ≥ 0
- `stock`: required, integer ≥ 0
- `cover_photo`: string max 255 (URL atau path)
- `genre_id`, `author_id`: nullable, harus exist

### `PUT /books/{id}` & `DELETE /books/{id}`

Pola sama. `PUT` mendukung `file_path` (untuk e-book PDF) di samping field di atas.

---

## Admin — Dashboard

### `GET /dashboard`

Stats untuk halaman utama admin.

**Auth:** Required + Admin.

**Response 200:**
```json
{
  "total_users": 20,
  "total_books": 50,
  "total_transactions": 25,
  "revenue": "Rp 1.234.567",
  "recent_transactions": [
    {
      "id": "BS-20260517-ABC123",
      "user": "Budi Santoso",
      "book": "Laskar Pelangi",
      "date": "17 Mei 2026",
      "amount": "Rp 75.000",
      "status": "Selesai"
    },
    ...  // 5 transaksi terakhir
  ],
  "sales_trend": [
    { "month": "Jan", "terbeli": 4 },
    { "month": "Feb", "terbeli": 4 },
    { "month": "Mar", "terbeli": 6 },
    { "month": "Apr", "terbeli": 6 },
    { "month": "Mei", "terbeli": 1 }
  ]
}
```

**Note:**
- `revenue` dihitung dari `SUM(total_amount) WHERE status = 'selesai'`
- `sales_trend` dihitung 6 bulan terakhir
- Dashboard agnostic SQLite/MySQL (lihat [01-ARCHITECTURE.md](01-ARCHITECTURE.md#65))

---

### `GET /users`

List semua user (untuk admin user management).

**Auth:** Required + Admin.

**Query params:**
- `search` — cari di nama atau email
- `role` — filter `'admin'` atau `'user'`
- `page`, `per_page` (default 15)

**Response 200:** Paginator user object (password TIDAK ter-include karena `#[Hidden]`).

---

## Chat — User Side

### `GET /conversations`

Get atau create conversation untuk user yang sedang login + load semua message.

**Auth:** Required.

**Response 200:**
```json
{
  "conversation_id": 5,
  "messages": [
    {
      "id": 12, "sender_type": "user", "type": "text",
      "body": "Halo admin, pesanan saya kapan dikirim?",
      "is_read": true, "created_at": "2026-05-17T10:30:00Z",
      "attachment_url": null, "transaction": null
    },
    {
      "id": 13, "sender_type": "admin", "type": "transaction",
      "body": null, "is_read": false, "created_at": "...",
      "attachment_url": null,
      "transaction": { "id": 25, "order_number": "BS-20260517-...", "total_amount": "287500.00", "status": "dikirim" }
    }
  ]
}
```

---

### `POST /conversations/messages`

Kirim pesan baru. 3 tipe: `text`, `image`, `transaction`.

**Auth:** Required.

#### Text
**Content-Type:** `application/json`
```json
{ "type": "text", "body": "Pesan apa saja" }
```

#### Image
**Content-Type:** `multipart/form-data`
- `type`: `image`
- `attachment`: file (max 2MB, harus image)

File disimpan di `storage/app/public/chat-images/<random>.jpg`. URL dikembalikan sebagai `attachment_url`.

#### Transaction (reference)
```json
{ "type": "transaction", "transaction_id": 25 }
```

User hanya boleh refer transaksinya sendiri (403 kalau bukan).

**Response 201:** Single message object dengan format sama seperti `/conversations`.

---

### `GET /conversations/messages?after={id}`

Polling untuk pesan baru. Return pesan dengan `id > after`.

**Auth:** Required.

**Response 200:**
```json
{ "messages": [...] }   // Array, mungkin kosong
```

Frontend ChatWidget polling endpoint ini setiap 5 detik.

---

### `PUT /conversations/read`

Tandai semua pesan dari admin sebagai sudah dibaca.

**Response 200:** `{ "message": "Dibaca" }`

---

## Chat — Admin Side

### `GET /admin/conversations`

Inbox semua percakapan (terurut by `last_message_at` desc).

**Auth:** Admin.

**Response 200:**
```json
[
  {
    "id": 5,
    "user": { "id": 1, "name": "Budi", "email": "budi@email.com" },
    "last_message": "Halo admin, pesanan saya...",
    "last_at": "2026-05-17T10:30:00Z",
    "unread_count": 2
  },
  ...
]
```

`unread_count` = jumlah pesan dari user yang `is_read = false`.

---

### `GET /admin/conversations/{id}`

Detail percakapan + semua message.

**Response 200:**
```json
{
  "id": 5,
  "user": { "id": 1, "name": "Budi" },
  "messages": [...]
}
```

---

### `POST /admin/conversations/{id}/messages`

Admin balas chat. Body & content-type sama dengan endpoint user (`text`/`image`/`transaction`).

**Response 201:** Single message object dengan `sender_type: 'admin'`.

---

### `PUT /admin/conversations/{id}/read`

Mark semua pesan dari user sebagai dibaca.

**Response 200:** `{ "message": "Dibaca" }`

---

## Format Error Standar

Laravel default response untuk validation:

```json
{
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

Custom error (controller manual):

```json
{ "message": "Pesan dalam Bahasa Indonesia" }
```

**Status code yang dipakai:**
- `200` OK
- `201` Created (untuk POST yang sukses bikin resource)
- `401` Unauthenticated (token tidak ada/invalid)
- `403` Forbidden (role tidak punya hak)
- `404` Not Found
- `422` Unprocessable Entity (validasi gagal)
- `500` Server Error (uncaught exception)

---

## Pagination Format

Semua endpoint list pakai Laravel paginator. Field standar:

| Field | Tipe | Catatan |
|-------|------|---------|
| `data` | array | Item halaman ini |
| `current_page` | int | Halaman aktif |
| `last_page` | int | Total halaman |
| `per_page` | int | Item per halaman |
| `total` | int | Total semua item |
| `from`, `to` | int | Range item (mis. "1–10" → from:1, to:10) |
| `first_page_url`, `last_page_url` | string | URL ke halaman pertama/terakhir |
| `prev_page_url`, `next_page_url` | string\|null | URL navigasi |
| `links` | array | Untuk render pagination buttons |
| `path` | string | Base URL request |

---

## Berikutnya

- Mau memahami flow checkout end-to-end? → [06-MIDTRANS-INTEGRATION.md](06-MIDTRANS-INTEGRATION.md)
- Mau memahami chat? → [07-CHAT-SYSTEM.md](07-CHAT-SYSTEM.md)
- Per-fitur deep dive → [09-FEATURES.md](09-FEATURES.md)
