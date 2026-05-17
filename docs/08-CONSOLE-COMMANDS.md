# 08 — Console Commands & Scheduler

> Dokumen ini menjelaskan **Artisan command custom** yang ada di project, **scheduler** yang menjalankannya, dan cara setup-nya di development & production.

---

## 1. Daftar Custom Command

| Command | Signature | Frekuensi | File |
|---------|-----------|-----------|------|
| Cancel Expired Transactions | `transactions:cancel-expired` | setiap menit | [`app/Console/Commands/CancelExpiredTransactions.php`](../app/Console/Commands/CancelExpiredTransactions.php) |
| Inspire (default Laravel) | `inspire` | manual | (built-in via `routes/console.php`) |

---

## 2. `transactions:cancel-expired`

### Tujuan

Auto-cancel transaksi yang status-nya `pending` lebih dari 1 jam. Ini penting untuk:

1. **Membersihkan order yang user batalkan** (mis. tutup tab browser tanpa bayar).
2. **Konsistensi data** — order pending yang menggantung tidak akan menumpuk.
3. **UX user**: frontend nampilkan countdown 1 jam — kalau expired, status pasti sudah `dibatalkan`.

### Implementasi

```php
// app/Console/Commands/CancelExpiredTransactions.php

class CancelExpiredTransactions extends Command
{
    protected $signature   = 'transactions:cancel-expired';
    protected $description = 'Cancel pending transactions older than 1 hour';

    public function handle(): int
    {
        $count = Transaction::where('status', 'pending')
            ->where('created_at', '<', now()->subHour())
            ->update(['status' => 'dibatalkan']);

        $this->info("Cancelled {$count} expired transaction(s).");

        return self::SUCCESS;
    }
}
```

**Behavior:**
- Cari transaksi `status=pending` dengan `created_at < (now - 1 hour)`
- Bulk update status jadi `dibatalkan`
- Print jumlah row yang ter-update

**Catatan:** Command ini TIDAK refund pembayaran (karena status `pending` artinya belum bayar). Juga TIDAK mengembalikan stok (karena stok hanya berkurang saat status `dibayar`).

### Jalankan manual

```bash
php artisan transactions:cancel-expired
```

Output:
```
Cancelled 3 expired transaction(s).
```

---

## 3. Scheduler

Laravel scheduler didefinisikan di [`routes/console.php`](../routes/console.php):

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('transactions:cancel-expired')->everyMinute();
```

Schedule API mendukung berbagai cadence:

| Method | Frekuensi |
|--------|-----------|
| `everyMinute()` | tiap menit (yang kita pakai) |
| `everyFiveMinutes()` | tiap 5 menit |
| `hourly()` | tiap jam |
| `daily()` | tiap hari jam 00:00 |
| `dailyAt('14:00')` | tiap hari jam 14:00 |
| `weekly()`, `monthly()` | tiap minggu/bulan |
| `cron('0 */6 * * *')` | custom cron expression |

---

## 4. Menjalankan Scheduler

### 4.1 Development — `schedule:work`

```bash
php artisan schedule:work
```

Stay-running process yang trigger scheduled jobs tiap menit. Tinggalkan jendela terminal terbuka.

Output sample:
```
[2026-05-17 10:30:00][1234] Running: php artisan transactions:cancel-expired
[2026-05-17 10:30:00][1234] Cancelled 2 expired transaction(s).
[2026-05-17 10:31:00][1235] Running: php artisan transactions:cancel-expired
[2026-05-17 10:31:00][1235] Cancelled 0 expired transaction(s).
...
```

Tekan `Ctrl+C` untuk stop.

### 4.2 Production — Cron Job

Tambah single cron entry di server:

```cron
* * * * * cd /path/to/booksales-api-laravel && php artisan schedule:run >> /dev/null 2>&1
```

**Penjelasan:**
- `* * * * *` → jalankan tiap menit
- `schedule:run` → command Laravel yang scan `routes/console.php` dan eksekusi job yang due saat ini
- `>> /dev/null 2>&1` → suppress output (biar tidak penuhi mail spool)

Cara install cron:
```bash
crontab -e
# Tambahkan baris di atas, save (Ctrl+O, Enter, Ctrl+X di nano)
crontab -l   # verifikasi
```

### 4.3 Windows — Task Scheduler

Untuk Windows server (tanpa cron native):

1. Buka **Task Scheduler**
2. Create Task → Name: "Laravel Schedule Run"
3. Trigger: Daily, recur every 1 minute, indefinitely
4. Action: Start a program
   - Program: `C:\path\to\php.exe`
   - Arguments: `artisan schedule:run`
   - Start in: `C:\laragon\www\booksales\booksales-api-laravel`

### 4.4 Hosting tanpa cron (mis. shared hosting)

Pakai service eksternal seperti:
- [cron-job.org](https://cron-job.org) — gratis untuk basic
- GitHub Actions cron workflow
- Vercel Cron Jobs (kalau deploy di Vercel)

Set URL ping ke endpoint custom (perlu tambah route + auth manual untuk security).

---

## 5. Composer Script `dev`

Saat menjalankan `composer run dev`, scheduler **TIDAK ikut dimulai otomatis**. Yang dimulai hanya:

```
- server (php artisan serve)
- queue  (php artisan queue:listen)
- logs   (php artisan pail)
- vite   (npm run dev)
```

Untuk scheduler, jalankan terpisah di terminal lain:
```bash
php artisan schedule:work
```

> **Improvement potensial:** Tambah `php artisan schedule:work` ke array `concurrently` di `composer.json` script `dev`. Saat ini sengaja dipisah karena scheduler kadang noisy di terminal dev.

---

## 6. Testing Command

### Manual

```bash
# 1. Buat transaksi pending dummy via Tinker
php artisan tinker
> \App\Models\Transaction::create([
    'order_number' => 'TEST-001',
    'customer_id' => 1,
    'book_id' => 1,
    'total_amount' => 100000,
    'status' => 'pending',
    'created_at' => now()->subHours(2),  // 2 jam yang lalu
  ]);

# 2. Run command
php artisan transactions:cancel-expired
# Output: Cancelled 1 expired transaction(s).

# 3. Verifikasi
> \App\Models\Transaction::where('order_number', 'TEST-001')->first()->status;
# = "dibatalkan"
```

### Unit test (rekomendasi, belum diimplementasi)

```php
// tests/Feature/CancelExpiredTransactionsTest.php
public function test_cancels_only_pending_older_than_one_hour()
{
    $oldPending = Transaction::factory()->create([
        'status' => 'pending',
        'created_at' => now()->subHours(2),
    ]);
    $recentPending = Transaction::factory()->create([
        'status' => 'pending',
        'created_at' => now()->subMinutes(30),
    ]);
    $oldPaid = Transaction::factory()->create([
        'status' => 'dibayar',
        'created_at' => now()->subHours(2),
    ]);

    Artisan::call('transactions:cancel-expired');

    $this->assertEquals('dibatalkan', $oldPending->fresh()->status);
    $this->assertEquals('pending',   $recentPending->fresh()->status);
    $this->assertEquals('dibayar',   $oldPaid->fresh()->status);
}
```

---

## 7. Membuat Command Baru

### Generate skeleton

```bash
php artisan make:command MyCustomCommand
```

Generated file: `app/Console/Commands/MyCustomCommand.php`

### Edit signature & handle

```php
class MyCustomCommand extends Command
{
    protected $signature = 'app:my-command {arg?} {--option=}';
    protected $description = 'Deskripsi command';

    public function handle(): int
    {
        $arg = $this->argument('arg');
        $opt = $this->option('option');

        // Bisnis logic di sini...

        return self::SUCCESS;   // atau self::FAILURE
    }
}
```

### Daftarkan di scheduler (opsional)

Tambah di `routes/console.php`:
```php
Schedule::command('app:my-command')->dailyAt('02:00');
```

### Run manual

```bash
php artisan app:my-command
php artisan app:my-command argvalue --option=foo
```

---

## 8. Inspire (Default Laravel)

```bash
php artisan inspire
# → Print quote inspirasi random
```

Cuma untuk demo. Defined di `routes/console.php`:
```php
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
```

Aman dihapus jika tidak dipakai.

---

## 9. Built-in Artisan Commands yang Sering Dipakai

| Command | Fungsi |
|---------|--------|
| `php artisan serve` | Run dev server |
| `php artisan migrate` | Run migrations |
| `php artisan migrate:fresh --seed` | Drop semua tabel, re-migrate, re-seed (⚠️ destruktif) |
| `php artisan db:seed` | Run seeder |
| `php artisan tinker` | REPL untuk explore models |
| `php artisan route:list` | List semua route + middleware |
| `php artisan config:clear` | Clear cached config (run setelah ubah .env) |
| `php artisan cache:clear` | Clear application cache |
| `php artisan storage:link` | Buat symlink storage/app/public → public/storage |
| `php artisan queue:work` | Process queue jobs |
| `php artisan pail` | Real-time log tail (lihat [10-TROUBLESHOOTING.md](10-TROUBLESHOOTING.md)) |

---

## 10. Job Queue

Project ini **mendaftarkan** queue driver `database` di `.env`:
```dotenv
QUEUE_CONNECTION=database
```

Tetapi **belum punya queued job aktif**. Jika nanti perlu (mis. kirim email notif setelah order paid):

```bash
# Generate job
php artisan make:job SendOrderConfirmation

# Dispatch (di controller)
SendOrderConfirmation::dispatch($transaction);

# Worker
php artisan queue:work
```

Lihat dokumentasi resmi Laravel Queues: https://laravel.com/docs/12.x/queues

---

## Berikutnya

- Mau lihat per-fitur deep dive? → [09-FEATURES.md](09-FEATURES.md)
- Ada masalah? → [10-TROUBLESHOOTING.md](10-TROUBLESHOOTING.md)
