# 03 — Database (Backend)

> Dokumen ini mencakup **semua tabel** di database BookSales, relasi antar tabel, migration timeline, dan ringkasan data yang di-seed.

---

## 1. ERD (Entity Relationship Diagram)

```
┌─────────────────┐                       ┌──────────────────┐
│     users       │                       │   conversations  │
├─────────────────┤  1                  1 ├──────────────────┤
│ id  PK          │◀──────────────────────│ user_id  FK      │
│ name            │                       │ last_message_at  │
│ email  UNIQUE   │                       │ timestamps       │
│ password        │                       └────────┬─────────┘
│ address         │                                │ 1
│ city            │                                │
│ postal_code     │                                ▼ *
│ role            │                       ┌──────────────────┐
│ last_access     │                       │     messages     │
│ timestamps      │                       ├──────────────────┤
└────────┬────────┘                       │ id  PK           │
         │ 1                              │ conversation_id  │
         │                                │ sender_id  FK    │
         ▼ *                              │ sender_type      │
┌─────────────────┐                       │ type             │
│  transactions   │ 1                   * │ body             │
├─────────────────┤◀──────────────┐       │ attachment_path  │
│ id  PK          │               │       │ transaction_id   │──┐
│ order_number U  │               │       │ is_read          │  │
│ customer_id  FK │               │       │ timestamps       │  │
│ book_id  FK     │──────┐        │       └──────────────────┘  │
│ subtotal        │      │        │                              │
│ tax_amount      │      │        │                              │
│ shipping_cost   │      │        │                              │
│ total_amount    │      │        │                              │
│ status          │      │        │                              │
│ snap_token      │      │        └─ * referenced as "tx"        │
│ payment_type    │      │           in chat messages           │
│ timestamps      │      │                                       │
└────────┬────────┘      │                                       │
         │ 1             │                                       │
         ▼ *             │                                       │
┌─────────────────┐      │                                       │
│transaction_items│      │                                       │
├─────────────────┤      │                                       │
│ id  PK          │      │                                       │
│ transaction_id  │      │                                       │
│ book_id  FK     │──────┤                                       │
│ quantity        │      │                                       │
│ price           │      │                                       │
└─────────────────┘      │                                       │
                         │                                       │
                ┌────────▼──────┐    ┌──────────────────┐        │
                │     books     │    │     genres       │        │
                ├───────────────┤    ├──────────────────┤        │
                │ id  PK        │ *  │ id  PK           │        │
                │ title         │───▶│ name             │        │
                │ description   │  1 │ description      │        │
                │ price         │    │ (no timestamps)  │        │
                │ stock         │    └──────────────────┘        │
                │ cover_photo   │                                │
                │ file_path     │    ┌──────────────────┐        │
                │ genre_id  FK  │    │     authors      │        │
                │ author_id  FK │ *  ├──────────────────┤        │
                │(no timestamps)│───▶│ id  PK           │        │
                └───────────────┘  1 │ name             │        │
                                     │ photo            │        │
                                     │ bio              │        │
                                     │ (no timestamps)  │        │
                                     └──────────────────┘        │
                                                                 │
       ┌──────────────────────┐    ┌──────────────────────┐      │
       │     contacts         │    │personal_access_tokens│◀─────┘
       ├──────────────────────┤    ├──────────────────────┤
       │ id  PK               │    │ id  PK               │
       │ name                 │    │ tokenable_id  FK     │ (morphs)
       │ email                │    │ tokenable_type       │
       │ subject              │    │ name                 │
       │ message              │    │ token UNIQUE         │
       │ timestamps           │    │ abilities            │
       └──────────────────────┘    │ last_used_at         │
                                   │ expires_at           │
                                   │ timestamps           │
                                   └──────────────────────┘
```

Legenda: `PK` = primary key, `FK` = foreign key, `UNIQUE` = unique constraint, `1` & `*` = cardinality (one / many).

---

## 2. Daftar Tabel

| # | Tabel | Tujuan | Migration |
|---|-------|--------|-----------|
| 1 | `users` | Akun (admin & customer) | [`0001_01_01_000000_create_users_table.php`](../database/migrations/0001_01_01_000000_create_users_table.php) |
| 2 | `password_reset_tokens` | (standard Laravel, tidak dipakai aktif) | sama dengan #1 |
| 3 | `sessions` | (standard Laravel, untuk web fallback) | sama dengan #1 |
| 4 | `cache`, `cache_locks` | Cache driver `database` | `0001_01_01_000001_create_cache_table.php` |
| 5 | `jobs`, `job_batches`, `failed_jobs` | Queue driver `database` | `0001_01_01_000002_create_jobs_table.php` |
| 6 | `authors` | Daftar penulis | `2026_04_20_115345_create_authors_table.php` |
| 7 | `genres` | Daftar kategori buku | `2026_04_20_115345_create_genres_table.php` |
| 8 | `books` | Katalog buku | `2026_04_20_115350_create_books_table.php` |
| 9 | `transactions` | Order/checkout | `2026_04_20_115360_create_transactions_table.php` |
| 10 | `personal_access_tokens` | Token Sanctum | `2026_04_27_145926_create_personal_access_tokens_table.php` |
| 11 | `transaction_items` | Detail line item per transaksi | `2026_05_14_180000_create_transaction_items_table.php` |
| 12 | `contacts` | Submission formulir kontak publik | `2026_05_15_000001_create_contacts_table.php` |
| 13 | `conversations` | Ruang chat per user | `2026_05_15_000002_create_conversations_table.php` |
| 14 | `messages` | Pesan di dalam conversation | `2026_05_15_000003_create_messages_table.php` |

---

## 3. Detail Schema

### 3.1 `users`

```sql
CREATE TABLE users (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    address         VARCHAR(255) NULL,
    city            VARCHAR(100) NULL,
    postal_code     VARCHAR(20)  NULL,
    role            ENUM('admin', 'user') DEFAULT 'user',
    last_access     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

**Catatan:**
- Password di-cast `'hashed'` di Model — disimpan dengan bcrypt otomatis.
- `address`, `city`, `postal_code` di-update via `PUT /api/user/profile`.
- `last_access` auto-update via DB `ON UPDATE CURRENT_TIMESTAMP`.
- Tidak pakai `email_verified_at` walaupun kolom-nya ada (default Laravel).

Lihat Model: [`app/Models/User.php`](../app/Models/User.php)

### 3.2 `genres`

```sql
CREATE TABLE genres (
    id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(255) NOT NULL,
    description TEXT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);
```

**Catatan:** Model `Genre` set `public $timestamps = false` (timestamps tetap ada di tabel tapi tidak di-handle Eloquent).

### 3.3 `authors`

```sql
CREATE TABLE authors (
    id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(255) NOT NULL,
    photo       VARCHAR(255) NULL,
    bio         TEXT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);
```

**Catatan:** `photo` bisa berisi URL eksternal (misal Wikipedia) atau path lokal.

### 3.4 `books`

```sql
CREATE TABLE books (
    id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title       VARCHAR(255) NOT NULL,
    description TEXT NULL,
    price       DECIMAL(10,2) NOT NULL,
    stock       INT DEFAULT 0,
    file_path   VARCHAR(255) NULL,      -- URL/path file PDF (untuk e-book)
    cover_photo VARCHAR(255) NULL,      -- URL/path cover image
    genre_id    BIGINT UNSIGNED NULL,   -- FK → genres.id ON DELETE SET NULL
    author_id   BIGINT UNSIGNED NULL,   -- FK → authors.id ON DELETE SET NULL
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);
```

**Catatan:**
- `cover_photo` & `file_path` bisa berisi URL absolut (mis. seeder pakai `https://upload.wikimedia.org/...`).
- Frontend mengecek prefix `http` — jika ya, langsung pakai; jika tidak, prepend `${API}/storage/covers/`.
- Model `Book` set `public $timestamps = false`.

### 3.5 `transactions`

```sql
CREATE TABLE transactions (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_number    VARCHAR(255) NOT NULL UNIQUE,    -- format: BS-YYYYMMDD-XXXXXX
    customer_id     BIGINT UNSIGNED NULL,            -- FK → users.id ON DELETE CASCADE
    book_id         BIGINT UNSIGNED NULL,            -- FK → books.id (LEGACY: item pertama)
    subtotal        DECIMAL(10,2) NULL,              -- sebelum pajak & shipping
    tax_amount      DECIMAL(10,2) NULL,              -- 11% × subtotal
    shipping_cost   DECIMAL(10,2) NULL,              -- selalu 10000 (Rp 10.000)
    total_amount    DECIMAL(10,2) NOT NULL,          -- subtotal + tax + shipping
    status          ENUM('pending','dibayar','dikirim','selesai','dibatalkan') DEFAULT 'pending',
    snap_token      VARCHAR(255) NULL,               -- Midtrans Snap token
    payment_type    VARCHAR(255) NULL,               -- credit_card / gopay / bank_transfer / dst
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

**Catatan tentang `book_id` di tabel ini:**
Dipertahankan untuk backward compatibility. Saat checkout, diisi dengan `book_id` item PERTAMA. Sumber kebenaran adalah relasi `items()` ke `transaction_items`. **Saat frontend menampilkan order detail, gunakan `tx.items` (array) bukan `tx.book` (single).**

**Status flow:**
```
pending ──(callback Midtrans: settlement)──▶ dibayar ──(admin manual)──▶ dikirim ──(admin manual)──▶ selesai
   │
   │──(>1 jam tanpa pembayaran)──▶ dibatalkan
   │──(callback Midtrans: cancel/deny/expire)──▶ dibatalkan
```

### 3.6 `transaction_items`

```sql
CREATE TABLE transaction_items (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    transaction_id  BIGINT UNSIGNED NOT NULL,    -- FK → transactions.id ON DELETE CASCADE
    book_id         BIGINT UNSIGNED NOT NULL,    -- FK → books.id ON DELETE CASCADE
    quantity        INT DEFAULT 1,
    price           DECIMAL(10,2) NOT NULL       -- snapshot harga saat checkout
);
```

**Catatan:** Tidak punya `timestamps`. `price` adalah snapshot — kalau admin nanti ubah harga buku di tabel `books`, data historis di sini tetap akurat.

### 3.7 `contacts`

```sql
CREATE TABLE contacts (
    id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(255) NOT NULL,
    email       VARCHAR(255) NOT NULL,
    subject     VARCHAR(255) NOT NULL,
    message     TEXT NOT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);
```

**Catatan:** Standalone — tidak terikat user. Submission via `POST /api/contact` (public, no auth). Admin baca via dashboard kontak (tampil di `/admin/contacts` di frontend).

### 3.8 `conversations`

```sql
CREATE TABLE conversations (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id         BIGINT UNSIGNED NOT NULL UNIQUE,  -- FK → users.id ON DELETE CASCADE
    last_message_at TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

**Catatan:**
- `user_id` UNIQUE — 1 user hanya punya 1 conversation (dengan admin).
- Dibuat otomatis saat user pertama kali buka chat via `firstOrCreate(['user_id' => ...])`.
- `last_message_at` di-touch tiap kali ada message baru, untuk sorting di inbox admin.

### 3.9 `messages`

```sql
CREATE TABLE messages (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    conversation_id BIGINT UNSIGNED NOT NULL,        -- FK → conversations.id ON DELETE CASCADE
    sender_id       BIGINT UNSIGNED NOT NULL,        -- FK → users.id ON DELETE CASCADE
    sender_type     ENUM('user', 'admin') NOT NULL,
    type            ENUM('text', 'image', 'transaction') DEFAULT 'text',
    body            TEXT NULL,                       -- isi pesan teks
    attachment_path VARCHAR(255) NULL,               -- relative path di storage (untuk type 'image')
    transaction_id  BIGINT UNSIGNED NULL,            -- FK → transactions.id ON DELETE SET NULL (untuk type 'transaction')
    is_read         BOOLEAN DEFAULT FALSE,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

**Aturan `is_read`:**
- `sender_type='user'` & `is_read=false` → unread bagi admin
- `sender_type='admin'` & `is_read=false` → unread bagi user
- User mark all admin messages as read via `PUT /api/conversations/read`
- Admin mark all user messages as read via `PUT /api/admin/conversations/{id}/read`

### 3.10 `personal_access_tokens`

Tabel standar Sanctum. Setiap kali login berhasil:

```php
$token = $user->createToken('auth_token')->plainTextToken;
// → INSERT INTO personal_access_tokens (tokenable_id, tokenable_type, name, token, ...) VALUES (...)
```

Kolom utama:
- `tokenable_id` + `tokenable_type` → polymorphic ke User (`App\Models\User`)
- `token` → SHA256 hash dari plain text token (yang dikirim ke client)
- `last_used_at` → auto-update setiap request authenticated
- `expires_at` → NULL (token tidak expire kecuali di-revoke manual)

---

## 4. Relasi di Eloquent

### User
```php
// app/Models/User.php
// (relasi tidak eksplisit di model — diakses via Sanctum's HasApiTokens)
```

### Book
```php
public function genre()  { return $this->belongsTo(Genre::class); }
public function author() { return $this->belongsTo(Author::class); }
```

### Author
```php
public function books() { return $this->hasMany(Book::class); }
```

### Transaction
```php
public function customer() { return $this->belongsTo(User::class, 'customer_id'); }
public function book()     { return $this->belongsTo(Book::class); }     // legacy
public function items()    { return $this->hasMany(TransactionItem::class); }
```

### TransactionItem
```php
public function transaction() { return $this->belongsTo(Transaction::class); }
public function book()        { return $this->belongsTo(Book::class); }
```

### Conversation
```php
public function user()     { return $this->belongsTo(User::class); }
public function messages() { return $this->hasMany(Message::class)->orderBy('created_at'); }
public function unreadCountForAdmin(): int {
    return $this->messages()->where('sender_type', 'user')->where('is_read', false)->count();
}
```

### Message
```php
public function sender()      { return $this->belongsTo(User::class, 'sender_id'); }
public function transaction() { return $this->belongsTo(Transaction::class); }
```

---

## 5. Foreign Key Behaviors

| Constraint | onDelete | Implikasi |
|------------|----------|-----------|
| `books.genre_id` | `set null` | Hapus genre → buku tetap ada, `genre_id` jadi NULL |
| `books.author_id` | `set null` | Hapus author → buku tetap ada, `author_id` jadi NULL |
| `transactions.customer_id` | `cascade` | Hapus user → semua transaksi user ikut terhapus |
| `transactions.book_id` | `cascade` | Hapus buku → transaksi legacy ter-link ikut hilang (jarang dipakai) |
| `transaction_items.transaction_id` | `cascade` | Hapus transaksi → item ikut hilang |
| `transaction_items.book_id` | `cascade` | Hapus buku → item historis ikut hilang (⚠️ HATI-HATI) |
| `conversations.user_id` | `cascade` | Hapus user → conversation ikut hilang |
| `messages.conversation_id` | `cascade` | Hapus conversation → pesan ikut hilang |
| `messages.sender_id` | `cascade` | Hapus user → pesan dari/ke user ikut hilang |
| `messages.transaction_id` | `set null` | Hapus transaksi → reference di chat jadi NULL |

> ⚠️ **PERHATIAN**: Cascade dari `transaction_items.book_id` berarti **jangan delete buku** kalau ada transaksi historis yang refer ke buku itu — riwayat ikut terhapus! Pertimbangkan soft delete jika perlu.

---

## 6. Data Hasil Seeder

`php artisan db:seed` akan menjalankan [`DatabaseSeeder.php`](../database/seeders/DatabaseSeeder.php) yang membuat:

| Tabel | Jumlah | Catatan |
|-------|--------|---------|
| `users` | 20 | 18 user + 2 admin (`admin@toko.com` / `adminpass`, `admin2@toko.com` / `adminpass2`) |
| `genres` | 20 | Fiksi, Fantasi, Komedi, Biografi, Sejarah, Horor, Romansa, Misteri, Thriller, Sains, Petualangan, dll |
| `authors` | 20 | Andrea Hirata, Tere Liye, J.K. Rowling, Pramoedya, dll (lengkap dengan foto Wikipedia) |
| `books` | 50 | 20 buku awal + 30 tambahan (mix lokal + internasional) |
| `transactions` | 20 | Jan–Apr 2026, status bervariasi (selesai, dikirim, dibayar, dibatalkan) |
| `transaction_items` | 0 | Seeder tidak buat items (legacy book_id only). Items dibuat saat checkout via API. |

Password default user:
- Pattern: `<nama_depan>123` atau `<nama>123` (mis. `siti@email.com` → `rahasia321`, `budi@email.com` → `password123`)

Lihat list lengkap di [`DatabaseSeeder.php`](../database/seeders/DatabaseSeeder.php) baris 22–42.

---

## 7. Migration Timeline (Urutan Eksekusi)

```
0001_01_01_000000_create_users_table              ← users + password_reset_tokens + sessions
0001_01_01_000001_create_cache_table              ← cache + cache_locks
0001_01_01_000002_create_jobs_table               ← jobs + job_batches + failed_jobs
2026_04_20_115345_create_authors_table            ← authors
2026_04_20_115345_create_genres_table             ← genres (same date, alphabetic order)
2026_04_20_115350_create_books_table              ← books (depends on genres + authors)
2026_04_20_115360_create_transactions_table       ← transactions
2026_04_27_145926_create_personal_access_tokens   ← Sanctum
2026_05_14_180000_create_transaction_items_table  ← transaction_items (depends on transactions + books)
2026_05_15_000001_create_contacts_table           ← contacts
2026_05_15_000002_create_conversations_table      ← conversations
2026_05_15_000003_create_messages_table           ← messages (depends on conversations)
```

---

## 8. Cara Menambah Migration Baru

```bash
# 1. Generate migration
php artisan make:migration add_phone_to_users_table --table=users

# 2. Edit file di database/migrations/
public function up(): void {
    Schema::table('users', function (Blueprint $table) {
        $table->string('phone', 20)->nullable()->after('postal_code');
    });
}

public function down(): void {
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('phone');
    });
}

# 3. Update Model: tambahkan 'phone' ke $fillable
# 4. Run migration
php artisan migrate
```

---

## 9. Backup & Restore (MySQL)

```bash
# Backup
mysqldump -u root db_booksales > backup-$(date +%Y%m%d).sql

# Restore
mysql -u root db_booksales < backup-20260517.sql
```

---

## Berikutnya

- Mau tahu cara auth → [04-AUTHENTICATION.md](04-AUTHENTICATION.md)
- Mau lihat semua endpoint → [05-API-REFERENCE.md](05-API-REFERENCE.md)
