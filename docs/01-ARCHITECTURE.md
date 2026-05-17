# 01 — Architecture (Backend)

> Tujuan dokumen ini: memberi gambaran utuh **bagaimana backend BookSales disusun**. Setelah membaca dokumen ini Anda akan tahu di mana harus mencari/menambah kode untuk fitur baru.

---

## 1. Stack Teknologi

| Lapisan | Teknologi | Versi (`composer.json`) |
|---------|-----------|-------------------------|
| Runtime | PHP | `^8.3` |
| Framework | Laravel | `^13.0` |
| Auth | Laravel Sanctum (Bearer token) | `^4.3` |
| Payment SDK | midtrans/midtrans-php | `^2.6` |
| Database (default) | SQLite (dev) → MySQL (rekomendasi prod) | — |
| Testing | PHPUnit | `^12.5` |
| Dev tooling | Laravel Pail (log viewer), Pint (formatter), Tinker | — |

> **Catatan:** `.env.example` di-default `DB_CONNECTION=sqlite`. Untuk production gunakan MySQL dengan database `db_booksales` (lihat [02-SETUP.md](02-SETUP.md)).

---

## 2. Struktur Folder Esensial

Hanya folder/file yang relevan ditampilkan — folder Laravel standar (vendor, bootstrap/cache, dll) di-skip.

```
booksales-api-laravel/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── CancelExpiredTransactions.php   ← Auto-cancel pending > 1 jam
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php              ← Login, register, profile, password
│   │   │   ├── BookController.php              ← CRUD buku (admin) + index/show (public)
│   │   │   ├── GenreController.php             ← CRUD genre
│   │   │   ├── AuthorController.php            ← CRUD author
│   │   │   ├── PublicCatalogController.php     ← Catalog publik (search + filter)
│   │   │   ├── TransactionController.php       ← Checkout + Midtrans + status
│   │   │   ├── DashboardController.php         ← Stats homepage admin
│   │   │   ├── UserController.php              ← List user (admin)
│   │   │   ├── ContactController.php           ← Form kontak publik
│   │   │   ├── ConversationController.php      ← Chat dari sisi user
│   │   │   └── AdminConversationController.php ← Chat dari sisi admin
│   │   └── Middleware/
│   │       ├── Admin.php                       ← Cek role === 'admin'
│   │       └── Customer.php                    ← Cek role in ['user', 'customer']
│   ├── Models/
│   │   ├── User.php             ← #[Fillable] + #[Hidden] PHP attribute
│   │   ├── Book.php             ← belongsTo Genre, belongsTo Author
│   │   ├── Genre.php
│   │   ├── Author.php           ← hasMany Book
│   │   ├── Transaction.php      ← belongsTo User, hasMany TransactionItem
│   │   ├── TransactionItem.php  ← belongsTo Transaction, belongsTo Book
│   │   ├── Conversation.php     ← belongsTo User, hasMany Message
│   │   ├── Message.php          ← belongsTo Conversation, belongsTo Sender, belongsTo Transaction
│   │   └── Contact.php          ← (standalone, public form)
│   └── Providers/
│       └── AppServiceProvider.php
├── bootstrap/
│   └── app.php                  ← Daftar middleware alias + route loading
├── config/
│   ├── services.php             ← Konfigurasi Midtrans (server_key, client_key, is_production)
│   ├── auth.php
│   └── ... (file Laravel standar)
├── database/
│   ├── migrations/              ← Schema history (lihat 03-DATABASE.md)
│   └── seeders/
│       └── DatabaseSeeder.php   ← Seed 20 user + 50 buku + 20 transaksi
├── routes/
│   ├── api.php                  ← SEMUA endpoint API ada di sini
│   ├── web.php                  ← Tidak dipakai (proyek API only)
│   └── console.php              ← Scheduler (jalankan command cancel-expired tiap menit)
├── storage/
│   ├── app/
│   │   └── public/
│   │       └── chat-images/     ← Lampiran chat (foto)
│   └── logs/
│       └── laravel.log          ← Log default (lihat 10-TROUBLESHOOTING.md)
├── composer.json
└── .env.example                 ← Template environment variable
```

---

## 3. Request Lifecycle

Berikut alur sebuah request dari frontend sampai response — contoh: `POST /api/transactions` (checkout).

```
[Frontend React]
       │
       │  POST /api/transactions
       │  Headers: Authorization: Bearer <token>
       │  Body: { items: [{book_id, quantity}], shipping_address, ... }
       ▼
[Apache/PHP-FPM]
       │
       ▼
[public/index.php]
       │  Bootstrap Laravel Application
       ▼
[bootstrap/app.php]
       │  - Route file dimuat: routes/api.php
       │  - Middleware alias didaftarkan: 'admin' → Admin::class
       │                                  'customer' → Customer::class
       ▼
[Routing Engine]
       │  Match: POST /api/transactions →
       │    middleware: ['auth:sanctum', 'customer']
       │    → TransactionController@store
       ▼
[Middleware Stack — sequential]
       │  1) auth:sanctum
       │     - Cek header Authorization: Bearer <token>
       │     - Resolve user dari personal_access_tokens table
       │     - Tolak 401 jika token invalid/expired
       │  2) Customer
       │     - Cek $request->user()->role in ['user', 'customer']
       │     - Tolak 403 jika bukan
       ▼
[Controller: TransactionController::store]
       │  1) Validate request
       │  2) Hitung subtotal + tax 11% + shipping 10.000 dari DATA DB
       │  3) DB::transaction → simpan Transaction + TransactionItem
       │  4) Call Midtrans Snap::getSnapToken($params)
       │  5) Simpan snap_token ke transaksi
       │  6) Return JSON { transaction, snap_token, client_key }
       ▼
[Frontend menerima response]
       │  → Panggil window.snap.pay(snap_token)
       │  → Popup Midtrans muncul, user bayar
       ▼
[Webhook ke /api/midtrans/callback]
       │  → Verifikasi signature SHA512
       │  → Update transaction status
       │  → Decrement stok jika sukses
```

---

## 4. Routing

Semua endpoint ada di **satu file**: [`routes/api.php`](../routes/api.php). Strukturnya:

```php
// — Public (tanpa auth) —
Route::post('/login',     [AuthController::class, 'login']);
Route::post('/register',  [AuthController::class, 'register']);
Route::get('/catalog',    [PublicCatalogController::class, 'index']);
Route::apiResource('genres',  ...)->only(['index', 'show']);
Route::apiResource('authors', ...)->only(['index', 'show']);
Route::apiResource('books',   ...)->only(['index', 'show']);
Route::post('/contact',   [ContactController::class, 'store']);
Route::post('/midtrans/callback', [TransactionController::class, 'midtransCallback']);

// — Admin only (auth:sanctum + Admin middleware) —
Route::middleware(['auth:sanctum', Admin::class])->group(function () {
    Route::apiResource('genres',  ...)->except(['index', 'show']);   // store/update/destroy
    Route::apiResource('authors', ...)->except(['index', 'show']);
    Route::apiResource('books',   ...)->except(['index', 'show']);
    Route::apiResource('transactions', ...)->only(['index', 'destroy']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/users',     [UserController::class, 'index']);
    Route::get('/admin/conversations',                  ...);
    Route::get('/admin/conversations/{id}',             ...);
    Route::post('/admin/conversations/{id}/messages',   ...);
    Route::put('/admin/conversations/{id}/read',        ...);
});

// — Customer only —
Route::middleware(['auth:sanctum', Customer::class])->group(function () {
    Route::apiResource('transactions', ...)->only(['store']);   // checkout
});

// — Authenticated user (apapun role-nya) —
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user/transactions',  ...);
    Route::put('/user/profile',       ...);
    Route::put('/user/password',      ...);
    Route::apiResource('transactions', ...)->only(['update', 'show']);
    Route::get('/conversations',                ...);
    Route::post('/conversations/messages',      ...);
    Route::get('/conversations/messages',       ...);   // long-poll
    Route::put('/conversations/read',           ...);
});
```

> **Detail tiap endpoint** ada di [05-API-REFERENCE.md](05-API-REFERENCE.md).

### Middleware Alias Registration

Di [`bootstrap/app.php`](../bootstrap/app.php):

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'admin'    => \App\Http\Middleware\Admin::class,
        'customer' => \App\Http\Middleware\Customer::class,
    ]);
})
```

Sehingga di `routes/api.php` Anda bisa menulis `Admin::class` (preferred, type-safe) atau `'admin'` (string alias).

---

## 5. Konvensi Koding

### 5.1 Controller

- Setiap controller meng-handle **satu resource utama**. Jangan campur (mis. `BookController` jangan diisi logika contact form).
- Method standar Laravel: `index`, `show`, `store`, `update`, `destroy`. Tambahan custom dibolehkan (mis. `midtransCallback`, `userTransactions`).
- Validasi langsung di method via `$request->validate([...])`. Tidak perlu Form Request class kecuali validasi sangat kompleks/reusable.

### 5.2 Model

- **Fillable**: User pakai PHP 8 attribute `#[Fillable([...])]`. Model lain pakai property `protected $fillable = [...]`.
- **Timestamps**: Beberapa model (`Genre`, `Author`, `Book`, `TransactionItem`) sengaja set `public $timestamps = false` karena tidak butuh `created_at`/`updated_at`. Jangan ubah tanpa migrasi tambahan.
- **Relasi** didefinisikan di method (bukan property): `genre()`, `author()`, `customer()`, `items()`, dst.

### 5.3 Naming

- Endpoint: kebab-case + plural (`/genres`, `/authors`, `/books`).
- Status transaksi: Bahasa Indonesia lowercase (`pending`, `dibayar`, `dikirim`, `selesai`, `dibatalkan`). **Jangan ubah** — frontend & seeder bergantung pada nilai ini.
- Role: lowercase (`admin`, `user`). Middleware `Customer.php` juga menerima legacy value `'customer'`.

### 5.4 Pesan Response

- Untuk success: `{ message: 'XYZ berhasil', data: {...} }` atau direct payload.
- Untuk error: `{ message: 'Pesan dalam Bahasa Indonesia' }` + status code yang sesuai (`401`, `403`, `404`, `422`).

---

## 6. Keputusan Desain Penting

### 6.1 Perhitungan harga di **backend**, bukan frontend

Saat checkout, frontend hanya mengirim `[{ book_id, quantity }]`. Backend:
1. Ambil harga buku dari database (jangan trust harga dari frontend)
2. Validasi stok
3. Hitung: `subtotal = sum(price × qty)`, `tax = round(subtotal × 0.11)`, `shipping = 10000`
4. `gross_amount = subtotal + tax + shipping`

Implementasi: [`TransactionController::store`](../app/Http/Controllers/TransactionController.php) baris 59–169.

### 6.2 Stok di-decrement **hanya saat status `dibayar`**

- Saat checkout (status `pending`): stok TIDAK dikurangi.
- Saat callback Midtrans dengan `transaction_status = settlement` atau `capture + accept`: stok di-decrement secara atomik.
- Auto-cancel job mengubah status pending > 1 jam jadi `dibatalkan` (lihat [08-CONSOLE-COMMANDS.md](08-CONSOLE-COMMANDS.md)).

```php
// Atomic decrement, hanya jika stok cukup
Book::where('id', $item->book_id)
    ->where('stock', '>=', $item->quantity)
    ->decrement('stock', $item->quantity);
```

### 6.3 Backward compat `book_id` di `transactions`

Tabel `transactions` masih punya kolom `book_id` (single book). Dipertahankan untuk legacy frontend code; isinya = `book_id` item pertama. Sumber data **sebenarnya** adalah relasi `items` (`transaction_items` table). Lihat [03-DATABASE.md](03-DATABASE.md) untuk detail.

### 6.4 Chat pakai **polling**, bukan WebSocket

Chat user↔admin pakai polling HTTP setiap 5 detik (`GET /api/conversations/messages?after=<last_id>`). Pilihan ini sengaja untuk menghindari kompleksitas Reverb/Pusher di prototype. Detail di [07-CHAT-SYSTEM.md](07-CHAT-SYSTEM.md).

### 6.5 SQLite vs MySQL — dashboard agnostic

`DashboardController` mendeteksi driver DB dan memilih ekspresi SQL yang sesuai:

```php
$driver = DB::connection()->getDriverName();
$monthExpr = $driver === 'sqlite'
    ? "strftime('%m', created_at) as month"
    : "DATE_FORMAT(created_at, \"%b\") as month";
```

Jadi grafik tren penjualan tetap jalan baik di dev (SQLite) maupun prod (MySQL).

---

## 7. Diagram Komponen High-Level

```
                            ┌───────────────────────────────────┐
                            │      Frontend React (booksales/)  │
                            │   - Browser, Axios, Snap.js       │
                            └────────────┬──────────────────────┘
                                         │ HTTPS / Bearer Token
                                         ▼
┌────────────────────────────────────────────────────────────────┐
│                Laravel API (port 8000)                         │
│                                                                │
│  ┌──────────┐    ┌───────────────┐    ┌──────────────────┐   │
│  │ Routes   │───▶│ Middleware    │───▶│ Controllers      │   │
│  │ api.php  │    │ Sanctum/      │    │ (12 controller)  │   │
│  └──────────┘    │ Admin/Customer│    └────────┬─────────┘   │
│                  └───────────────┘             │              │
│                                                ▼              │
│                  ┌─────────────────────────────────────────┐  │
│                  │ Models (Eloquent ORM)                   │  │
│                  │  User, Book, Genre, Author,             │  │
│                  │  Transaction, TransactionItem,          │  │
│                  │  Conversation, Message, Contact         │  │
│                  └────────┬────────────────────────────────┘  │
│                           │                                   │
│  ┌────────────────────────▼─────────────────────────────────┐ │
│  │  Database (MySQL / SQLite)                               │ │
│  │  10 tabel: users, genres, authors, books, transactions,  │ │
│  │           transaction_items, contacts, conversations,    │ │
│  │           messages, personal_access_tokens               │ │
│  └──────────────────────────────────────────────────────────┘ │
│                                                                │
│  ┌──────────────────┐    ┌──────────────────────────────────┐ │
│  │ Scheduler        │    │ Storage                          │ │
│  │ (tiap menit)     │    │  storage/app/public/chat-images/ │ │
│  │ cancel-expired   │    │  storage/app/public/covers/      │ │
│  └──────────────────┘    └──────────────────────────────────┘ │
└────────────────────────────────────────────────────────────────┘
                                         │
                                         ▼
                              ┌──────────────────────┐
                              │  Midtrans Sandbox    │
                              │  (Snap + Webhook)    │
                              └──────────────────────┘
```

---

## 8. Berikutnya

- Mau setup lokal? → [02-SETUP.md](02-SETUP.md)
- Mau tahu skema DB? → [03-DATABASE.md](03-DATABASE.md)
- Mau cari endpoint? → [05-API-REFERENCE.md](05-API-REFERENCE.md)
