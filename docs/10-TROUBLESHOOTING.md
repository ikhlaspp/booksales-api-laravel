# 10 — Troubleshooting (Backend)

> Catatan masalah umum yang sering ditemui di BookSales backend, gejala, dan cara mengatasinya. Dilengkapi panduan **debugging tools** (logs, Pail, Tinker).

---

## 1. Cara Debug — Tools

### 1.1 Real-time log dengan `pail`

```bash
php artisan pail
```

Output akan tampil tiap kali ada log baru di `storage/logs/laravel.log`. Sangat berguna saat develop sambil ngecek webhook/error.

Filter:
```bash
php artisan pail --filter="Midtrans"
php artisan pail --level=error
```

### 1.2 Baca log file

```bash
tail -f storage/logs/laravel.log     # Linux/Mac
Get-Content storage/logs/laravel.log -Wait    # PowerShell
```

Atau buka di editor — log default 1 file (`stack` channel di `.env`).

### 1.3 Tinker (REPL)

```bash
php artisan tinker
```

Bisa eksekusi PHP/Eloquent secara interaktif:

```php
> User::count()
> User::where('role', 'admin')->get()
> Transaction::with('items.book')->find(1)
> Hash::check('adminpass', User::find(3)->password)
```

### 1.4 List semua route

```bash
php artisan route:list
php artisan route:list --path=api
```

Berguna untuk pastiin endpoint terdaftar + cek middleware yang attached.

### 1.5 Cek konfigurasi terbaca

```bash
php artisan tinker
> config('services.midtrans')
> config('database.default')
```

Jika nilai tidak sesuai `.env`, run `php artisan config:clear`.

---

## 2. Masalah Setup

### `Class "PDO" not found` saat migrate
**Penyebab:** Extension PHP untuk database belum ter-install.
**Solusi:**
- MySQL: `sudo apt-get install php8.3-mysql` (Linux) atau enable `extension=pdo_mysql` di `php.ini` (Windows)
- SQLite: enable `extension=pdo_sqlite` dan `extension=sqlite3`

Setelah install, restart server: `php artisan serve`.

### `SQLSTATE[HY000] [2002] Connection refused`
**Penyebab:** MySQL/MariaDB service belum jalan.
**Solusi:**
- Laragon: klik **Start All**
- Linux: `sudo service mysql start`
- Windows: Start MySQL service via `services.msc`

### `Database (db_booksales) does not exist`
**Penyebab:** Database belum dibuat.
**Solusi:**
```sql
CREATE DATABASE db_booksales CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
Atau via Laragon: kanan klik MySQL → Create database → nama `db_booksales`.

### `APP_KEY is not set`
**Solusi:**
```bash
php artisan key:generate
```

### Permission denied di `storage/` atau `bootstrap/cache/`
**Linux/Mac:**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache    # jika dipakai di prod
```

### Vendor / autoload error setelah `git pull`
```bash
composer install
composer dump-autoload
```

---

## 3. Masalah Authentication

### `Route [login] not defined` saat akses endpoint protected

**Penyebab:** Header `Accept: application/json` tidak dikirim. Laravel default mencoba redirect ke route `login` (web), padahal kita API-only.

**Solusi (sisi client):** Selalu kirim:
```
Accept: application/json
```

Atau di Axios:
```js
axios.defaults.headers.common['Accept'] = 'application/json';
```

### Login berhasil tapi `GET /api/user` return 401
**Cek:**
1. Header dikirim dengan format `Authorization: Bearer <token>` (spasi tunggal, kapitalisasi tepat)
2. Token belum di-revoke di tabel `personal_access_tokens`
3. Cek di Tinker:
   ```php
   > DB::table('personal_access_tokens')->latest()->first()
   ```

### "Unauthorized or Forbidden" (403) padahal sudah login
**Penyebab:** Role user tidak match middleware. Misal user biasa coba akses endpoint admin.

**Solusi:**
- Cek role: `php artisan tinker` → `User::find($id)->role`
- Promote user jadi admin: `User::find($id)->update(['role' => 'admin'])`

---

## 4. Masalah Checkout / Midtrans

### `snap_token` null di response checkout

**Cek `storage/logs/laravel.log`** untuk line `Midtrans Snap Error: ...`.

Penyebab umum:
1. **`MIDTRANS_SERVER_KEY` salah/kosong**
   - Solusi: cek `.env`, run `php artisan config:clear`
2. **Tidak ada koneksi internet ke api.midtrans.com**
   - Solusi: cek firewall/proxy
3. **`gross_amount` tidak match `sum(item_details.price × qty)`**
   - Solusi: tambah tax & shipping sebagai virtual item (lihat [06-MIDTRANS-INTEGRATION.md#6](06-MIDTRANS-INTEGRATION.md#6-payload-snap))

### Webhook 403 "Invalid signature"

**Penyebab:** Signature SHA512 tidak match.

**Solusi:**
1. Pastikan `MIDTRANS_SERVER_KEY` di `.env` sama persis dengan di dashboard Midtrans
2. Jika baru pindah dari sandbox ke production: server key BEDA, harus update
3. Test signature manual:
   ```bash
   echo -n "BS-20260517-ABC123200287500.00<YOUR_MIDTRANS_SERVER_KEY>" | sha512sum
   # Bandingkan dengan signature_key yang dikirim Midtrans
   ```

### Webhook tidak ter-trigger dari Midtrans

**Penyebab umum:**
1. Notification URL di dashboard Midtrans salah / kosong
2. Server di-host di localhost (Midtrans tidak bisa reach)
3. Firewall block incoming request

**Solusi:**
1. Set notification URL di dashboard Sandbox → Settings → Configuration
2. Pakai **ngrok** untuk dev:
   ```bash
   ngrok http 8000
   # Copy URL https://abc123.ngrok.io
   # Set di Midtrans dashboard: https://abc123.ngrok.io/api/midtrans/callback
   ```
3. Buka port 80/443 di firewall production

### Stok berkurang 2x setelah pembayaran

**Penyebab:** Race condition antara `PUT /api/transactions/{id}` (frontend onSuccess) dan webhook async.

**Current state:** Idempotency BELUM perfect — kedua handler bisa fire dengan timing yang dekat.

**Mitigasi sementara:**
- Cek `previousStatus !== 'dibayar'` sebelum decrement (sudah diimplementasi di `update()`)
- Webhook punya `decrement` yang menjaga `stock >= quantity` (TIDAK negatif, tapi BISA double-decrement)

**Improvement (belum diimplementasi):**
Tambah column `stock_decremented` di `transactions`:

```php
// Migration
$table->boolean('stock_decremented')->default(false);

// Logic
if (!$transaction->stock_decremented) {
    DB::transaction(function () use ($transaction) {
        foreach ($transaction->items as $item) {
            Book::where('id', $item->book_id)
                ->where('stock', '>=', $item->quantity)
                ->decrement('stock', $item->quantity);
        }
        $transaction->update(['stock_decremented' => true]);
    });
}
```

---

## 5. Masalah Chat

### Lampiran image error 404

**Penyebab:** Symlink storage belum dibuat.

**Solusi:**
```bash
php artisan storage:link
```

Verifikasi symlink:
```bash
ls -la public/storage    # Linux/Mac
dir public\storage       # Windows
```

Harus berupa symlink ke `../storage/app/public/`.

### Upload image gagal: "The attachment must be an image"

**Penyebab:** File tidak ter-detect sebagai image. Cek:
1. MIME type — kalau aneh, ganti dengan `.jpg`/`.png`
2. Size > 2MB — exceeds `max:2048` rule. Resize file dulu.

### Polling pesan baru tidak update di frontend

**Cek:**
1. Network tab browser — apakah `GET /api/conversations/messages?after=X` fire setiap 5 detik?
2. Apakah response berisi message baru?
3. Cek `lastId` di state — sudah update?

Di backend, log query:
```php
DB::enableQueryLog();
$messages = $conversation->messages()->where('id', '>', $after)->get();
\Log::info('Polling result', ['lastId' => $after, 'count' => $messages->count()]);
```

---

## 6. Masalah Database

### Migration error: "Foreign key constraint fails"

**Penyebab:** Migration jalan urutan salah. Misal `books` migrate sebelum `genres` (FK dependency).

**Solusi:**
1. Cek prefix tanggal di nama file migration — pastikan dependencies di-create dulu
2. Run fresh: `php artisan migrate:fresh --seed`

### Seeder error: "Duplicate entry"

**Penyebab:** Tabel sudah berisi data + seeder coba INSERT row dengan PK eksplisit yang sudah ada.

**Solusi:**
1. Fresh DB: `php artisan migrate:fresh --seed`
2. Atau truncate tabel manual sebelum seed

### Decimal sum tidak akurat (mis. tax)

**Penyebab:** PHP float precision.

**Solusi:** Pakai integer arithmetic (kalikan 100 untuk paise/sen). Saat ini kita pakai `round(... * 0.11)` yang sudah cukup untuk IDR (no fractional rupiah).

---

## 7. Masalah Scheduler / Console Command

### `php artisan transactions:cancel-expired` tidak meng-cancel apa-apa

**Cek:**
1. Apakah ada transaksi dengan `status='pending'` dan `created_at < now - 1 hour`?
   ```bash
   php artisan tinker
   > Transaction::where('status', 'pending')->where('created_at', '<', now()->subHour())->count()
   ```
2. Kalau 0, tidak ada yang harus di-cancel — command jalan tapi 0 rows affected. NORMAL.

### Scheduler tidak jalan di production

**Cek crontab:**
```bash
crontab -l
# Harus ada baris: * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

**Cek schedule list:**
```bash
php artisan schedule:list
# Harus tampil: * * * * *  php artisan transactions:cancel-expired ........................... Next Due: 1 minute
```

**Test eksekusi manual:**
```bash
php artisan schedule:run
# Harus log "Running scheduled command: transactions:cancel-expired"
```

---

## 8. Masalah Performance

### API lambat respond (> 1 detik)

**Diagnosa:**
1. Enable query log di tinker:
   ```php
   > DB::enableQueryLog();
   > User::with('transactions.items.book')->get();
   > dd(DB::getQueryLog());
   ```
2. Cari query yang berulang / lambat
3. Tambah `with()` untuk eager load

### N+1 di list endpoint

Pastikan semua list endpoint pakai eager loading:
```php
Book::with(['author', 'genre'])->paginate();          // ✅
Transaction::with(['customer', 'items.book'])->get(); // ✅
```

JANGAN:
```php
Book::all()->each(fn($b) => $b->author->name);   // ❌ N+1!
```

### Dashboard lambat

Query `total_revenue` butuh full scan `transactions`. Untuk DB > 10K rows, tambah index:
```sql
CREATE INDEX idx_transactions_status ON transactions(status);
```

---

## 9. Masalah Frontend Komunikasi dengan API

### CORS error: "blocked by CORS policy"

**Penyebab:** Frontend di port berbeda (5173) call API di port 8000.

**Solusi:** Laravel sudah set CORS default open via `config/cors.php`. Cek file ini:
```php
'paths' => ['api/*'],
'allowed_origins' => ['*'],  // atau spesifik http://localhost:5173
```

Restart `php artisan serve` setelah ubah.

### `Network Error` di axios

**Cek:**
1. Backend memang jalan? `curl http://localhost:8000/api/catalog`
2. URL di frontend benar? `.env` punya `VITE_API_BASE_URL=http://localhost:8000/api`?
3. Cek dev tools Network tab — request terkirim ke URL yang benar?

### Response field `data` ada / tidak ada inconsistency

**Penjelasan:**
- Laravel Paginator: response = `{ data: [...], current_page: ..., total: ..., ... }`
- Single resource: response = object langsung (tanpa `data` wrapper)
- Custom controller bisa wrap manual (`return response()->json(['data' => $item])`)

**Solusi konsumsi di frontend:**
```js
const items = response.data?.data ?? response.data ?? [];
```

---

## 10. Mengatur Log Lebih Detail

### Tingkatkan verbosity log di dev

`.env`:
```dotenv
LOG_LEVEL=debug
APP_DEBUG=true
```

Restart server.

### Custom log channel

`config/logging.php` tambah:
```php
'midtrans' => [
    'driver' => 'single',
    'path' => storage_path('logs/midtrans.log'),
    'level' => 'debug',
],
```

Di controller:
```php
\Log::channel('midtrans')->info('Webhook received', $request->all());
```

Tinggal `tail -f storage/logs/midtrans.log` saat test webhook.

---

## 11. Reset Semua

Jika benar-benar stuck dan ingin start fresh:

```bash
# ⚠️ DESTRUKTIF: drop semua data
php artisan migrate:fresh --seed

# Clear semua cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Re-install dependency
rm -rf vendor node_modules
composer install
npm install

# Re-link storage
php artisan storage:link

# Restart server
php artisan serve
```

---

## 12. Help, Stuck!

1. **Cek `storage/logs/laravel.log`** untuk error detail
2. **Cek frontend Network tab** untuk lihat response actual
3. **Try cURL** untuk isolasi apakah masalah backend atau frontend
4. **Bandingkan dengan `.env.example`** — ada field yang kelupaan?
5. **Tanya tim** atau buka issue dengan log + reproduction steps

---

## Berikutnya

- Kembali ke [README.md](../README.md)
- Pelajari frontend di [`../booksales/docs/`](../../booksales/docs/)
