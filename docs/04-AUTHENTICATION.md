# 04 — Authentication (Backend)

> Dokumen ini menjelaskan **bagaimana autentikasi & otorisasi bekerja** di BookSales API: Sanctum, Bearer token, middleware Admin/Customer, dan alur lengkap dari login sampai akses endpoint protected.

---

## 1. Stack Auth

- **Laravel Sanctum 4.3** — untuk API token authentication
- **Bearer token** dikirim via header `Authorization: Bearer <token>`
- **Tidak pakai session-based auth** untuk endpoint API
- **Bcrypt** untuk hashing password (cost factor default 12)

---

## 2. Alur Login Lengkap

```
1) Frontend: POST /api/login { email, password }
                  │
                  ▼
2) AuthController::login
   - Validate email + password tidak kosong
   - User::where('email', $email)->first()
   - Hash::check($plainPassword, $user->password)
   - Jika gagal: return 401 { "message": "Kredensial tidak valid" }
                  │
                  ▼
3) $token = $user->createToken('auth_token')->plainTextToken
   - Sanctum generate random 40-char string
   - Disimpan SHA256-hashed di personal_access_tokens
   - Plain text token DIKEMBALIKAN ke client (sekali ini saja)
                  │
                  ▼
4) Return JSON:
   {
     "message": "Login berhasil",
     "user": { id, name, email, role, ... },
     "role": "admin" | "user",
     "access_token": "1|abcdefgh...XYZ",
     "token_type": "Bearer"
   }
                  │
                  ▼
5) Frontend simpan token + user info di localStorage:
   - token
   - user_id
   - user_name
   - user_role
   - Dispatch event 'authChange' (untuk CartContext refresh)
                  │
                  ▼
6) Frontend redirect berdasarkan role:
   - admin → /admin
   - user/customer → /profile
```

Implementasi: [`AuthController::login`](../app/Http/Controllers/AuthController.php) baris 12–36.

---

## 3. Alur Request Authenticated

```
Frontend kirim:
  GET /api/dashboard
  Headers: Authorization: Bearer 1|abcdefgh...XYZ
                  │
                  ▼
Middleware: auth:sanctum
  1) Parse header Authorization
  2) Cari token di personal_access_tokens (cek SHA256 hash)
  3) Resolve user dari tokenable_id/type
  4) Update last_used_at
  5) Set $request->user() = User instance
  6) Jika token tidak ada/invalid → 401 Unauthenticated
                  │
                  ▼
Middleware tambahan (mis. Admin)
  - Cek $request->user()->role === 'admin'
  - Jika bukan: 403 Forbidden
                  │
                  ▼
Controller method
  - Akses user via $request->user() atau auth()->user()
```

---

## 4. Custom Middleware

### 4.1 `Admin` middleware

[`app/Http/Middleware/Admin.php`](../app/Http/Middleware/Admin.php):

```php
public function handle(Request $request, Closure $next): Response
{
    if ($request->user() && $request->user()->role === 'admin') {
        return $next($request);
    }
    return response()->json(['message' => 'Unauthorized or Forbidden'], 403);
}
```

**Dipakai di:** semua route admin (CRUD genres/authors/books, list transactions, dashboard, list users, admin conversations).

### 4.2 `Customer` middleware

[`app/Http/Middleware/Customer.php`](../app/Http/Middleware/Customer.php):

```php
public function handle(Request $request, Closure $next): Response
{
    if ($request->user() && in_array($request->user()->role, ['user', 'customer'])) {
        return $next($request);
    }
    return response()->json(['message' => 'Unauthorized or Forbidden'], 403);
}
```

**Dipakai di:** `POST /api/transactions` (checkout). Hanya user biasa yang boleh checkout — admin diblock.

**Catatan:** Menerima legacy value `'customer'` selain `'user'` untuk backward compat.

### 4.3 Registrasi Alias

Di [`bootstrap/app.php`](../bootstrap/app.php):

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'admin'    => \App\Http\Middleware\Admin::class,
        'customer' => \App\Http\Middleware\Customer::class,
    ]);
})
```

Bisa dipakai dalam 2 cara di routes:

```php
// Cara 1: pakai class (type-safe, recommended)
Route::middleware(['auth:sanctum', Admin::class])->group(...);

// Cara 2: pakai string alias
Route::middleware(['auth:sanctum', 'admin'])->group(...);
```

---

## 5. Endpoint Authentication

### 5.1 `POST /api/login`

**Request:**
```http
POST /api/login HTTP/1.1
Content-Type: application/json

{
  "email": "admin@toko.com",
  "password": "adminpass"
}
```

**Response 200 — Sukses:**
```json
{
  "message": "Login berhasil",
  "user": {
    "id": 3,
    "name": "Admin Utama",
    "email": "admin@toko.com",
    "role": "admin",
    "address": "Gedung Perkantoran Sudirman",
    "city": "Jakarta",
    "postal_code": "12190",
    "last_access": "2026-05-17T10:30:00.000000Z",
    "created_at": "...",
    "updated_at": "..."
  },
  "role": "admin",
  "access_token": "1|abcdef...xyz",
  "token_type": "Bearer"
}
```

**Response 401 — Gagal:**
```json
{ "message": "Kredensial tidak valid" }
```

**Response 422 — Validasi gagal:**
```json
{
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### 5.2 `POST /api/register`

**Request:**
```http
POST /api/register HTTP/1.1
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "rahasia123"
}
```

**Aturan validasi:**
- `name`: required, string, max 255
- `email`: required, email, unique di tabel users
- `password`: required, string, min 8 karakter

**Response 201 — Sukses:**
```json
{
  "message": "Registrasi berhasil",
  "user": { ... },
  "role": "user",
  "access_token": "...",
  "token_type": "Bearer"
}
```

User baru selalu di-default `role: 'user'` — tidak ada cara user mendaftar sebagai admin via API.

### 5.3 `GET /api/user`

**Request:**
```http
GET /api/user HTTP/1.1
Authorization: Bearer <token>
```

**Response 200:**
```json
{
  "id": 3,
  "name": "Admin Utama",
  "email": "admin@toko.com",
  "role": "admin",
  "address": "...",
  "city": "...",
  "postal_code": "...",
  ...
}
```

Implementasi: closure di [`routes/api.php`](../routes/api.php) baris 22–24.

### 5.4 `PUT /api/user/profile`

**Request:**
```http
PUT /api/user/profile HTTP/1.1
Authorization: Bearer <token>
Content-Type: application/json

{
  "name": "Nama Baru",
  "address": "Jl. Baru No. 1",
  "city": "Jakarta",
  "postal_code": "12345"
}
```

**Aturan validasi:**
- Semua field optional (`sometimes`)
- `email` unik (exclude current user)
- `name` max 255
- `address` max 255
- `city` max 100
- `postal_code` max 20

**Response 200:** User object (fresh, sudah ter-update).

**Response 422:** Jika tidak ada field yang diubah → `"Tidak ada field yang diubah."`

### 5.5 `PUT /api/user/password`

**Request:**
```http
PUT /api/user/password HTTP/1.1
Authorization: Bearer <token>

{
  "current_password": "rahasia123",
  "new_password": "rahasiaBaru456",
  "new_password_confirmation": "rahasiaBaru456"
}
```

**Aturan validasi:**
- `current_password`: required
- `new_password`: required, min 8, harus match dengan `new_password_confirmation`

**Response 200:**
```json
{ "message": "Password berhasil diubah." }
```

**Response 422 — Password lama salah:**
```json
{ "message": "Password saat ini tidak sesuai." }
```

---

## 6. Logout (Tidak Ada Endpoint Khusus)

API ini **tidak menyediakan endpoint `/api/logout`**. Logout dilakukan dengan:

### Sisi frontend (cukup):
```js
localStorage.removeItem('token');
localStorage.removeItem('user_id');
localStorage.removeItem('user_name');
localStorage.removeItem('user_role');
window.dispatchEvent(new Event('authChange'));
navigate('/login');
```

### Sisi backend (opsional, untuk revoke token):
Jika ingin token benar-benar invalid di server-side, tambahkan endpoint:

```php
Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();
    return response()->json(['message' => 'Logged out']);
});
```

Lalu di frontend: `await axios.post('/api/logout', null, { headers: {...} })` sebelum hapus localStorage.

> **Status saat ini:** Belum diimplementasi. Token tetap valid di DB sampai user login ulang (yang akan create token baru, lama tetap valid sampai dihapus manual).

---

## 7. Role-Based Access Control (RBAC)

Saat ini hanya ada 2 role:

| Role | Akses |
|------|-------|
| `admin` | Semua endpoint admin + semua endpoint auth biasa |
| `user` | Endpoint katalog, checkout, transaksi sendiri, profile, chat |

| Capability | `user` | `admin` |
|------------|:------:|:-------:|
| Browse katalog (`GET /api/catalog`) | ✅ | ✅ |
| Lihat detail buku | ✅ | ✅ |
| Login & register | ✅ | ✅ |
| Update profile sendiri | ✅ | ✅ |
| Checkout (`POST /api/transactions`) | ✅ | ❌ (blocked by Customer middleware) |
| Lihat transaksi sendiri | ✅ | ✅ |
| Update transaksi sendiri | ✅ | ✅ |
| Lihat **semua** transaksi | ❌ | ✅ |
| Hapus transaksi | ❌ | ✅ |
| CRUD buku/genre/author | ❌ | ✅ |
| List semua user | ❌ | ✅ |
| Akses dashboard | ❌ | ✅ |
| Chat dengan admin (sebagai user) | ✅ | ❌ (admin pakai inbox) |
| Admin chat inbox | ❌ | ✅ |

### Catatan implementasi authorization di controller

Beberapa controller juga melakukan **owner check** di samping middleware. Contoh `TransactionController::show`:

```php
if ($transaction->customer_id !== $request->user()->id && $request->user()->role !== 'admin') {
    return response()->json(['message' => 'Unauthorized'], 403);
}
```

Artinya: user biasa hanya bisa lihat transaksinya sendiri, sementara admin bisa lihat semua.

---

## 8. Token Management

### Lifecycle

| Action | Efek di `personal_access_tokens` |
|--------|----------------------------------|
| Login | INSERT row baru, plain token dikirim ke client |
| Setiap request authenticated | UPDATE `last_used_at = now()` |
| Login lagi | INSERT row BARU (token lama tetap ada) |
| Manual revoke (belum ada endpoint) | DELETE row |

### Tidak ada expiry

`expires_at` di-NULL by default — token tetap valid selama row ada di tabel.

### Hapus token expired (manual maintenance)

```php
// Misalnya, hapus token yang tidak dipakai > 30 hari
DB::table('personal_access_tokens')
    ->where('last_used_at', '<', now()->subDays(30))
    ->delete();
```

Bisa dijadikan scheduled command jika perlu (saat ini belum ada).

---

## 9. Security Best Practices yang Diterapkan

| Praktik | Implementasi |
|---------|--------------|
| Password hashing | Bcrypt via Laravel `Hash::make()` (cost factor 12) |
| Password tidak ter-expose | Di Model User pakai `#[Hidden(['password', 'remember_token'])]` |
| Token tidak disimpan plain di DB | Sanctum simpan SHA256 hash |
| Email unique constraint | DB-level UNIQUE pada `email` |
| Validasi input | Tiap controller pakai `$request->validate(...)` |
| SQL injection protection | Eloquent ORM + parameterized queries |
| CSRF tidak relevan | API-only, tidak pakai web session |

### Yang belum / opsional:

- ❌ Rate limiting login (bisa pakai `throttle:login` middleware Laravel)
- ❌ Email verification
- ❌ Password reset via email
- ❌ Two-factor auth
- ❌ Account lockout setelah X failed login

---

## 10. Testing Auth Manual

```bash
# Login
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@toko.com","password":"adminpass"}' \
  | grep -o '"access_token":"[^"]*' | cut -d'"' -f4)

echo "Token: $TOKEN"

# Pakai token
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/user
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/dashboard

# Test akses admin sebagai user biasa (harus 403)
# (login dulu sebagai user, dapat token, lalu coba akses /api/dashboard)
```

---

## Berikutnya

- Detail tiap endpoint → [05-API-REFERENCE.md](05-API-REFERENCE.md)
- Memahami fitur per-modul → [09-FEATURES.md](09-FEATURES.md)
