# 07 — Chat System

> BookSales punya fitur **chat real-time-ish antara user dan admin** untuk customer support. Dokumen ini menjelaskan arsitektur, model data, alur message, dan polling strategy.

---

## 1. Konsep Inti

- **1 user = 1 conversation** dengan admin. User tidak bisa chat sesama user.
- **Polling-based** (bukan WebSocket): user/admin polling endpoint setiap 5 detik untuk message baru.
- **3 tipe message**: `text`, `image`, `transaction` (reference ke order).
- **Read tracking** terpisah per arah (user → admin, admin → user).
- **Attachment image** disimpan di local filesystem (`storage/app/public/chat-images/`).

---

## 2. Data Model

### Conversation (1 per user)

```
conversations
├── id
├── user_id (UNIQUE, FK users)        ← satu user satu conversation
├── last_message_at                    ← untuk sorting di inbox admin
└── created_at, updated_at
```

### Message (banyak per conversation)

```
messages
├── id
├── conversation_id (FK)
├── sender_id (FK users)               ← siapa yang ngirim
├── sender_type ('user' | 'admin')
├── type ('text' | 'image' | 'transaction')
├── body (TEXT, nullable)              ← isi text
├── attachment_path (nullable)         ← path image di storage
├── transaction_id (nullable, FK)      ← refer ke order (untuk type 'transaction')
├── is_read (boolean, default false)
└── created_at, updated_at
```

### Eloquent Relations

```php
// Conversation
public function user()     { return $this->belongsTo(User::class); }
public function messages() { return $this->hasMany(Message::class)->orderBy('created_at'); }
public function unreadCountForAdmin(): int {
    return $this->messages()->where('sender_type', 'user')->where('is_read', false)->count();
}

// Message
public function sender()      { return $this->belongsTo(User::class, 'sender_id'); }
public function transaction() { return $this->belongsTo(Transaction::class); }
```

---

## 3. Controllers

| Controller | Endpoint | Untuk siapa |
|------------|----------|-------------|
| [`ConversationController`](../app/Http/Controllers/ConversationController.php) | `/api/conversations/*` | User biasa |
| [`AdminConversationController`](../app/Http/Controllers/AdminConversationController.php) | `/api/admin/conversations/*` | Admin |

Pemisahan controller membuat logic per-sisi lebih clear (mis. user hanya bisa load conversationnya sendiri, admin bisa lihat semua).

---

## 4. Alur User Mengirim Pesan

```
User buka chat widget di frontend
       │
       ▼
[Frontend] GET /api/conversations
       │  → return: { conversation_id, messages: [...] }
       ▼
[Backend] ConversationController::show
   - getOrCreateConversation(user_id)  ← firstOrCreate
   - Load semua messages
   - Format setiap message:
     {
       id, sender_type, type, body, is_read, created_at,
       attachment_url: Storage::url($attachment_path) | null,
       transaction: { ... } | null
     }
       │
       ▼
User ketik pesan, klik kirim
       │
       ▼
[Frontend] POST /api/conversations/messages
   Body: { type: "text", body: "Halo admin" }
       │
       ▼
[Backend] ConversationController::sendMessage
   - Validate (type wajib, body wajib jika type=text, dst)
   - Cari/create conversation
   - INSERT message dengan sender_type='user', is_read=false
   - UPDATE conversation.last_message_at = now()
   - Return formatted message
       │
       ▼
[Frontend] Append message ke UI
```

### Tipe text
```json
POST /api/conversations/messages
Content-Type: application/json

{ "type": "text", "body": "Halo admin, pesanan saya kapan dikirim?" }
```

### Tipe image (multipart)
```http
POST /api/conversations/messages
Content-Type: multipart/form-data

type=image
attachment=<file>   (max 2MB, format image/*)
```

**Backend:**
```php
$attachmentPath = $request->file('attachment')->store('chat-images', 'public');
// → stored at storage/app/public/chat-images/<random>.jpg
// → accessible via URL: /storage/chat-images/<random>.jpg (setelah artisan storage:link)
```

### Tipe transaction (refer ke order)
```json
{ "type": "transaction", "transaction_id": 25 }
```

**Validasi tambahan di backend:**
```php
if ($request->type === 'transaction') {
    $tx = Transaction::find($request->transaction_id);
    if ($tx->customer_id !== $request->user()->id) {
        return response()->json(['message' => 'Transaksi tidak ditemukan'], 403);
    }
}
```

User hanya boleh refer transaksinya sendiri (admin di-block kirim transaction message via endpoint user, tapi boleh kirim via endpoint admin — bisa refer transaksi siapa saja).

---

## 5. Alur Polling (Real-time-ish)

Setelah load chat awal, frontend polling endpoint berikut setiap 5 detik:

```http
GET /api/conversations/messages?after=42
Authorization: Bearer <token>
```

Backend ([`ConversationController::poll`](../app/Http/Controllers/ConversationController.php#L92)):

```php
$after = (int) $request->query('after', 0);
$messages = $conversation->messages()
    ->when($after > 0, fn($q) => $q->where('id', '>', $after))
    ->get()
    ->map(fn($m) => $this->formatMessage($m));

return response()->json(['messages' => $messages]);
```

Frontend update `lastId = max(message.id)` dan polling lagi dengan id terbaru. Sehingga setiap polling hanya download message baru.

**Frontend implementation:** `booksales/src/components/ChatWidget.jsx` baris 102–123.

---

## 6. Sisi Admin: Inbox

Admin akses chat via `/admin/contacts` di frontend (walaupun nama "contacts", isinya adalah inbox chat — kebetulan halaman ini juga punya akses ke contact form submissions di backend, tapi UI utama-nya inbox chat).

### Inbox list

```http
GET /api/admin/conversations
```

Return semua conversation, sorted by `last_message_at DESC`:

```json
[
  {
    "id": 5,
    "user": { "id": 1, "name": "Budi Santoso", "email": "..." },
    "last_message": "Halo admin, pesanan saya...",
    "last_at": "2026-05-17T10:30:00Z",
    "unread_count": 2
  },
  ...
]
```

`unread_count` dihitung via [`Conversation::unreadCountForAdmin()`](../app/Models/Conversation.php#L23):
```php
return $this->messages()
    ->where('sender_type', 'user')
    ->where('is_read', false)
    ->count();
```

### Buka percakapan

```http
GET /api/admin/conversations/{id}
```

Return single conversation + semua messages.

### Balas chat

```http
POST /api/admin/conversations/{id}/messages
```

Body sama dengan user endpoint (text/image/transaction). Bedanya `sender_type` di-set `'admin'`.

### Mark as read

Saat admin buka percakapan, frontend juga panggil:
```http
PUT /api/admin/conversations/{id}/read
```

Backend update semua message dengan `sender_type='user'` jadi `is_read=true`.

---

## 7. Read Tracking Detail

Dua aliran read tracking:

| Direction | Mark as read endpoint | Logic |
|-----------|----------------------|-------|
| User → Admin | `PUT /api/admin/conversations/{id}/read` | `UPDATE messages SET is_read=1 WHERE sender_type='user' AND is_read=0` |
| Admin → User | `PUT /api/conversations/read` | `UPDATE messages SET is_read=1 WHERE sender_type='admin' AND is_read=0` |

Frontend convention: panggil endpoint mark-as-read **saat chat panel dibuka** (bukan saat per-message dilihat — terlalu chatty).

Implementasi frontend:
- User: `ChatWidget.jsx` baris 131–139 — saat `open === true`, panggil PUT
- Admin: `pages/admin/contacts/index.jsx` baris 138 — saat klik user di sidebar

---

## 8. Format Response Message (Konsisten)

Method private [`formatMessage`](../app/Http/Controllers/ConversationController.php#L19) dan [`formatMessage`](../app/Http/Controllers/AdminConversationController.php#L14) di kedua controller mengembalikan struktur sama:

```json
{
  "id": 12,
  "sender_type": "user" | "admin",
  "type": "text" | "image" | "transaction",
  "body": "...",                  // null untuk image/transaction
  "is_read": true | false,
  "created_at": "2026-05-17T10:30:00.000000Z",
  "attachment_url": "http://localhost:8000/storage/chat-images/abc.jpg" | null,
  "transaction": {                // hanya jika type === 'transaction'
    "id": 25,
    "order_number": "BS-20260517-ABC123",
    "total_amount": "287500.00",
    "status": "dibayar"
  } | null
}
```

Format sengaja dibuat konsisten supaya frontend (baik widget user maupun admin inbox) bisa pakai komponen render yang sama (`MessageBubble`).

---

## 9. Attachment Storage

### Lokasi fisik
```
storage/app/public/chat-images/
└── <uuid-or-random>.jpg
```

### URL public
```
http://localhost:8000/storage/chat-images/<filename>
```

URL ini hanya jalan setelah symlink dibuat:
```bash
php artisan storage:link
```

### Validasi upload
- Max size: 2MB (`max:2048` di validation rule)
- Tipe: harus image (`image` rule, accept image/jpeg, png, gif, webp, dll)

### Pertimbangan production
- Pakai S3/Cloud Storage untuk scalability (configure di `config/filesystems.php`)
- Tambah image optimization (mis. via Intervention Image package) untuk resize sebelum store
- Pertimbangkan virus scan untuk public upload

---

## 10. Performance Considerations

### Current state (polling 5s)

| Komponen | Beban per user aktif |
|----------|----------------------|
| Network | 1 request HTTP per 5 detik |
| DB query | `SELECT FROM messages WHERE conversation_id=X AND id>Y` (indexed scan) |
| Memory backend | Minimal — no persistent connection |

Asumsi 100 user concurrent online: 100 req/5s = 20 req/s. Untuk Laravel + MySQL ini ringan, bahkan tanpa optimization.

### Kalau scale > 1000 concurrent

Pertimbangkan:
- **WebSocket** via Laravel Reverb / Pusher → reduces network overhead drastis
- **Long polling** (server hold request sampai message baru atau timeout 30s) → midway solution
- **Redis pub/sub** untuk delivery cross-server

Tapi untuk MVP / lower-mid scale, polling cukup.

### Index yang penting

```sql
-- Sudah ada via foreign key
CREATE INDEX idx_messages_conv ON messages(conversation_id);

-- Tambah jika polling lambat:
CREATE INDEX idx_messages_conv_id ON messages(conversation_id, id);
```

---

## 11. Frontend Hooks (Quick Reference)

| Frontend file | Tujuan |
|---------------|--------|
| `booksales/src/components/ChatWidget.jsx` | Widget chat floating bagi user |
| `booksales/src/pages/admin/contacts/index.jsx` | Inbox admin (chat) |

Detail strategy polling, transaction picker, notification: lihat dokumentasi frontend [`10-CHAT-WIDGET.md`](../../booksales/docs/10-CHAT-WIDGET.md).

---

## 12. Future Improvements (Belum diimplementasi)

- Typing indicator
- Edit/delete pesan
- Reply ke pesan spesifik (threaded)
- Search dalam chat history
- Export chat ke PDF/CSV
- Push notification (browser/mobile) selain in-app
- Read receipt per-pesan (saat ini hanya per-direction batch)
- Multi-admin assignment (saat ini semua admin lihat inbox yang sama)

---

## Berikutnya

- Mau setup scheduled job (cancel expired transactions)? → [08-CONSOLE-COMMANDS.md](08-CONSOLE-COMMANDS.md)
- Per-fitur deep dive → [09-FEATURES.md](09-FEATURES.md)
