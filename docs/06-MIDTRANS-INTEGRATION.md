# 06 — Midtrans Integration

> Panduan lengkap integrasi pembayaran **Midtrans Snap (Sandbox mode)** di backend BookSales. Berisi konfigurasi, alur checkout end-to-end, signature verification webhook, dan troubleshooting.

---

## 1. Tentang Midtrans Snap

[Midtrans Snap](https://docs.midtrans.com/docs/snap-overview) adalah payment solution dari Midtrans yang menyediakan pop-up checkout dengan multiple payment method (BCA Virtual Account, Mandiri Bill, GoPay, ShopeePay, QRIS, kartu kredit, dll) tanpa kita harus mengintegrasikan satu-satu.

**Cara kerja singkat:**
1. Backend kita request `snap_token` dari Midtrans API dengan detail order
2. Frontend pakai token tsb untuk render popup Snap (via `snap.js`)
3. User pilih metode pembayaran & bayar
4. Midtrans kirim notifikasi async via webhook ke backend kita
5. Backend update status order

---

## 2. Setup Akun & Credentials

### 2.1 Daftar Sandbox

1. Buka https://dashboard.sandbox.midtrans.com/register
2. Lengkapi data merchant (gratis untuk sandbox)
3. Login ke dashboard

### 2.2 Ambil Server Key & Client Key

1. **Settings** → **Access Keys**
2. Akan ada dua key:
   - **Server Key** (rahasia, hanya di backend) — contoh format: `SB-Mid-server-...` (sandbox) atau `Mid-server-...` (production)
   - **Client Key** (boleh tampil di frontend) — contoh format: `SB-Mid-client-...` (sandbox) atau `Mid-client-...` (production)

### 2.3 Set di `.env`

```dotenv
MIDTRANS_SERVER_KEY=<YOUR_MIDTRANS_SERVER_KEY>
MIDTRANS_CLIENT_KEY=<YOUR_MIDTRANS_CLIENT_KEY>
MIDTRANS_IS_PRODUCTION=false
```

Restart `php artisan serve` setelah ubah `.env` (atau jalankan `php artisan config:clear`).

### 2.4 Set Notification URL (untuk webhook)

Di sandbox dashboard:
1. **Settings** → **Configuration**
2. **Payment Notification URL** → isi: `http://your-domain.com/api/midtrans/callback`

> Untuk development di localhost, pakai **ngrok** atau **Cloudflare Tunnel** untuk expose `http://localhost:8000` ke publik agar Midtrans bisa kirim webhook. Contoh:
> ```bash
> ngrok http 8000
> # → forwarding https://abc123.ngrok.io → http://localhost:8000
> ```
> Set notification URL ke `https://abc123.ngrok.io/api/midtrans/callback`.

---

## 3. Mapping di Codebase

### File-file relevan:

| File | Tujuan |
|------|--------|
| [`config/services.php`](../config/services.php) baris 38–42 | Definisi konfigurasi Midtrans (membaca `.env`) |
| [`app/Http/Controllers/TransactionController.php`](../app/Http/Controllers/TransactionController.php) | Method `store` (checkout) + `midtransCallback` (webhook) |
| [`composer.json`](../composer.json) | Dependency `midtrans/midtrans-php: ^2.6` |
| Frontend: `booksales/src/pages/public/checkout/index.jsx` | Konsumsi `snap_token` & panggil `window.snap.pay()` |

---

## 4. Alur Checkout End-to-End

```
┌────────────────┐                                  ┌────────────────┐
│   Frontend     │                                  │   Backend      │
│   (Browser)    │                                  │   (Laravel)    │
└────────┬───────┘                                  └────────┬───────┘
         │                                                   │
         │ 1. User klik "Bayar Sekarang"                     │
         │ ───────────────────────────────────────────────▶  │
         │   POST /api/transactions                          │
         │   { items: [{book_id, quantity}], ... }           │
         │                                                   │
         │                                          2. Validasi
         │                                             stok & harga
         │                                                   │
         │                                          3. Hitung gross_amount
         │                                             = subtotal + tax + shipping
         │                                                   │
         │                                          4. DB::transaction:
         │                                             - INSERT transactions
         │                                             - INSERT transaction_items
         │                                                   │
         │                                          5. Call Midtrans API:
         │                                             Snap::getSnapToken({
         │                                               transaction_details,
         │                                               customer_details,
         │                                               item_details
         │                                             })
         │                                                   │
         │                                                   ▼
         │                                          ┌──────────────────┐
         │                                          │  Midtrans API    │
         │                                          │  /snap/v1/...    │
         │                                          └────────┬─────────┘
         │                                                   │
         │                                          6. Return snap_token
         │                                                   │
         │                                          7. UPDATE transactions
         │                                             SET snap_token = ...
         │                                                   │
         │ 8. Response 201                                   │
         │ ◀───────────────────────────────────────────────  │
         │   { transaction, snap_token, client_key }         │
         │                                                   │
         │ 9. window.snap.pay(snap_token, {                  │
         │      onSuccess: ..., onPending: ...,              │
         │      onError: ..., onClose: ...                   │
         │    })                                             │
         │                                                   │
         │ 10. Popup Midtrans muncul                         │
         │     User pilih metode & bayar                     │
         │                                                   │
         │ 11. onSuccess callback fires                      │
         │ ───────────────────────────────────────────────▶  │
         │   PUT /api/transactions/{id}                      │
         │   { status: "dibayar" }                           │
         │                                                   │
         │                                          12. Update status
         │                                              + decrement stok
         │                                                   │
         │ 13. Redirect ke /profile/orders/{id}              │
         │                                                   │
         │                                                   │
         │  ── Beberapa detik kemudian ──                    │
         │                                                   │
         │                                          14. Webhook async:
         │                                              POST /api/midtrans/callback
         │                                              ◀───────────── Midtrans
         │                                              { order_id, transaction_status,
         │                                                signature_key, ... }
         │                                                   │
         │                                          15. Verifikasi signature SHA512
         │                                                   │
         │                                          16. Update status DEFINITIF
         │                                              (settlement → dibayar, dst)
         │                                                   │
         │                                          17. Decrement stok jika sukses
         │                                              (idempotent jika sudah di step 12)
```

### Catatan penting:

- **Step 11 (`PUT /api/transactions`)** dan **Step 14 (webhook)** keduanya bisa update status jadi `dibayar`. Idempotency dijaga karena decrement stok hanya jalan kalau `previousStatus !== 'dibayar'` di endpoint `update` ([baris 276 TransactionController](../app/Http/Controllers/TransactionController.php#L276)).
- **Snap.js** dimuat di frontend dari CDN sandbox: `https://app.sandbox.midtrans.com/snap/snap.js`. Saat production ganti ke `https://app.midtrans.com/snap/snap.js`.
- **`client_key`** dikembalikan dari backend ke frontend lewat response checkout, agar frontend bisa attach `data-client-key` ke script tag jika perlu.

---

## 5. Konfigurasi Midtrans di Controller

Setiap kali ingin pakai Midtrans SDK, konfigurasi harus di-set dulu:

```php
use Midtrans\Config;
use Midtrans\Snap;

Config::$serverKey    = config('services.midtrans.server_key');
Config::$isProduction = (bool) config('services.midtrans.is_production');
Config::$isSanitized  = true;   // Auto-sanitize special chars
Config::$is3ds        = true;   // Aktifkan 3D Secure untuk kartu kredit
```

Implementasi: [`TransactionController::store`](../app/Http/Controllers/TransactionController.php#L134) baris 134–138 dan `midtransCallback` baris 176–178.

---

## 6. Payload Snap

Payload yang dikirim ke Midtrans saat request token:

```php
$params = [
    'transaction_details' => [
        'order_id'     => 'BS-20260517-ABC123',     // unique
        'gross_amount' => 287500,                    // total dalam IDR (integer)
    ],
    'customer_details' => [
        'first_name' => 'Budi Santoso',
        'email'      => 'budi@email.com',
    ],
    'item_details' => [
        [
            'id'       => '1',                       // book_id sebagai string
            'price'    => 80000,                     // integer IDR
            'quantity' => 2,
            'name'     => 'Laskar Pelangi',          // truncated 50 char
        ],
        // ... per item
    ],
];

$snapToken = Snap::getSnapToken($params);
```

> **Constraint:** Sum `(price × quantity)` di `item_details` harus **sama persis** dengan `gross_amount` di `transaction_details`. Kalau tidak, Midtrans tolak request. Saat ini kita TIDAK menambahkan tax & shipping sebagai line item (hanya pada `gross_amount`). Implementasi sekarang ini bisa mismatch — jika muncul error "amount mismatch", tambahkan tax & shipping sebagai item_details virtual:
> ```php
> $itemDetails[] = ['id' => 'TAX',      'price' => $taxAmount,    'quantity' => 1, 'name' => 'PPN 11%'];
> $itemDetails[] = ['id' => 'SHIPPING', 'price' => $shippingCost, 'quantity' => 1, 'name' => 'Ongkir'];
> ```

---

## 7. Webhook Callback Detail

### 7.1 Endpoint

`POST /api/midtrans/callback` — tidak pakai auth karena server-to-server. Pakai signature verification.

### 7.2 Format payload (sample dari Midtrans)

```json
{
  "transaction_time": "2026-05-17 10:30:45",
  "transaction_status": "settlement",
  "transaction_id": "abc-123-xyz",
  "status_message": "midtrans payment notification",
  "status_code": "200",
  "signature_key": "8d4f3f5b1e2a...",
  "payment_type": "bank_transfer",
  "order_id": "BS-20260517-ABC123",
  "merchant_id": "G123456789",
  "gross_amount": "287500.00",
  "fraud_status": "accept",
  "currency": "IDR"
}
```

### 7.3 Verifikasi signature

```php
$expectedSignature = hash('sha512',
    $orderId . $statusCode . $grossAmount . $serverKey
);

if ($signatureKey !== $expectedSignature) {
    return response()->json(['message' => 'Invalid signature'], 403);
}
```

> **Mengapa signature?** Karena endpoint tidak pakai auth, attacker bisa pretend jadi Midtrans dan kirim fake notification (mis. mark order as paid tanpa bayar). Signature membuktikan request datang dari Midtrans (karena hanya Midtrans + kita yang tahu `server_key`).

### 7.4 Mapping `transaction_status` → status BookSales

| Midtrans `transaction_status` | Midtrans `fraud_status` | BookSales `status` |
|-------------------------------|--------------------------|---------------------|
| `capture` | `accept` | `dibayar` |
| `capture` | `challenge` | `pending` (manual review) |
| `settlement` | — | `dibayar` |
| `pending` | — | `pending` |
| `cancel`, `deny`, `expire` | — | `dibatalkan` |

### 7.5 Side effect: decrement stok

Setelah update status ke `dibayar`, decrement stok atomik:

```php
foreach ($transaction->items as $item) {
    Book::where('id', $item->book_id)
        ->where('stock', '>=', $item->quantity)
        ->decrement('stock', $item->quantity);
}
```

> **Idempotent:** Where clause `stock >= quantity` mencegah stok jadi negatif. Jika webhook fire 2x (kasus duplikasi), decrement kedua akan no-op kalau stok sudah berkurang ke level di bawah quantity (bug potensial: kalau stok 5 dan beli 2, decrement pertama → stok 3, decrement kedua → stok 1). Untuk perfect idempotency, lacak via field `stock_decremented` di transaction:
> ```php
> if (!$transaction->stock_decremented) {
>     // decrement
>     $transaction->update(['stock_decremented' => true]);
> }
> ```
> (Belum diimplementasi — di-noted sebagai future improvement.)

---

## 8. Testing Pembayaran Sandbox

### 8.1 Kartu Kredit Test

Midtrans menyediakan kartu test khusus sandbox:

| Tipe | Card Number | CVV | Exp |
|------|-------------|-----|-----|
| Success | `4811 1111 1111 1114` | `123` | `01/25` |
| Denied | `4911 1111 1111 1113` | `123` | `01/25` |

OTP/3D Secure untuk semua test: `112233`.

### 8.2 Bank Transfer Test

- Pilih BCA / Mandiri / BNI Virtual Account di popup Snap
- Akan diberi nomor VA test
- Buka **Simulator** di sandbox dashboard: https://simulator.sandbox.midtrans.com/bca/va/index
- Masukkan VA number → bayar → webhook akan auto-fire

### 8.3 GoPay/E-Wallet Test

- Pilih GoPay di popup
- Akan diberi QR code dummy
- Buka simulator e-wallet → tap "Pay" → status auto-settle

### 8.4 Test webhook tanpa transaksi nyata

Pakai cURL untuk simulate webhook:

```bash
# Generate signature
ORDER_ID="BS-20260517-ABC123"
STATUS_CODE="200"
GROSS_AMOUNT="287500.00"
SERVER_KEY="<YOUR_MIDTRANS_SERVER_KEY>"

SIGNATURE=$(echo -n "${ORDER_ID}${STATUS_CODE}${GROSS_AMOUNT}${SERVER_KEY}" | sha512sum | cut -d' ' -f1)

# Send webhook
curl -X POST http://localhost:8000/api/midtrans/callback \
  -H "Content-Type: application/json" \
  -d "{
    \"order_id\": \"$ORDER_ID\",
    \"status_code\": \"$STATUS_CODE\",
    \"gross_amount\": \"$GROSS_AMOUNT\",
    \"signature_key\": \"$SIGNATURE\",
    \"transaction_status\": \"settlement\",
    \"fraud_status\": \"accept\",
    \"payment_type\": \"bank_transfer\"
  }"
```

---

## 9. Troubleshooting Midtrans

| Gejala | Penyebab & Solusi |
|--------|--------------------|
| `Midtrans Snap Error: ...` di `laravel.log` | Cek MIDTRANS_SERVER_KEY di .env, restart server |
| `snap_token` selalu null di response | Server key salah / koneksi internet bermasalah / Midtrans API down |
| Webhook 403 "Invalid signature" | Server key tidak match. Cek MIDTRANS_SERVER_KEY di .env sama dengan yang di Midtrans dashboard |
| Status order tidak ter-update walaupun bayar berhasil | Notification URL di Midtrans dashboard salah / firewall block / pakai ngrok URL yang sudah expired |
| Stok turun 2x | Race condition antara `PUT /api/transactions/{id}` (frontend) dan webhook. Belum ada perfect idempotency — pertimbangkan migrasi `stock_decremented` boolean column |
| `window.snap is not defined` di frontend | Snap.js belum di-load. Cek `<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="...">` di Checkout.jsx |

### Lihat log

```bash
# Real-time
php artisan pail

# Atau file log
tail -f storage/logs/laravel.log
```

Cari line `Midtrans Snap Error: ...` untuk error detail.

---

## 10. Migrasi ke Production

Saat siap go-live:

1. **Daftar production account** di https://dashboard.midtrans.com/register (bukan sandbox)
2. **Verifikasi merchant** (KTP, NPWP, dll — wajib untuk produksi)
3. **Ambil production keys** dari dashboard production
4. **Update `.env`:**
   ```dotenv
   MIDTRANS_SERVER_KEY=<YOUR_PRODUCTION_SERVER_KEY>
   MIDTRANS_CLIENT_KEY=<YOUR_PRODUCTION_CLIENT_KEY>
   MIDTRANS_IS_PRODUCTION=true
   ```
5. **Update Snap.js URL di frontend** dari sandbox CDN ke production CDN:
   ```js
   const SNAP_SCRIPT_URL = 'https://app.midtrans.com/snap/snap.js';
   ```
6. **Update notification URL** ke domain production
7. **Test pembayaran dengan kartu/VA asli** (nominal kecil untuk sanity check)
8. **Monitor `laravel.log` ketat** di hari-hari pertama

---

## Berikutnya

- Mau memahami fitur chat? → [07-CHAT-SYSTEM.md](07-CHAT-SYSTEM.md)
- Mau setup scheduled job auto-cancel? → [08-CONSOLE-COMMANDS.md](08-CONSOLE-COMMANDS.md)
- Per-fitur deep dive → [09-FEATURES.md](09-FEATURES.md)
