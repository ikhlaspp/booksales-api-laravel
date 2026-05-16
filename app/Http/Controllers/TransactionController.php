<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Snap;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['customer', 'book', 'items.book'])->latest();
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', '%' . $search . '%')
                  ->orWhereHas('customer', function($sub) use ($search) {
                      $sub->where('name', 'like', '%' . $search . '%');
                  });
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function userTransactions(Request $request)
    {
        $query = Transaction::with(['book', 'items.book'])
            ->where('customer_id', $request->user()->id)
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('items.book', fn($b) => $b->where('title', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(10));
    }

    /**
     * Checkout: menerima array book_id dari keranjang, menghitung total dari database,
     * membuat transaksi + snap_token Midtrans.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items'            => 'required|array|min:1',
            'items.*.book_id'  => 'required|exists:books,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // Ambil semua buku dari database untuk menghitung total secara aman
        $bookIds = collect($validated['items'])->pluck('book_id')->unique()->toArray();
        $books = Book::whereIn('id', $bookIds)->get()->keyBy('id');

        // Validasi stok sebelum proses
        $itemDetails = [];
        $subtotalAmount = 0;

        foreach ($validated['items'] as $item) {
            $book = $books->get($item['book_id']);

            if (!$book) {
                return response()->json([
                    'message' => "Buku dengan ID {$item['book_id']} tidak ditemukan."
                ], 404);
            }

            if ($book->stock < $item['quantity']) {
                return response()->json([
                    'message' => "Stok buku \"{$book->title}\" tidak mencukupi. Tersedia: {$book->stock}."
                ], 422);
            }

            $subtotal = $book->price * $item['quantity'];
            $subtotalAmount += $subtotal;

            $itemDetails[] = [
                'id' => (string) $book->id,
                'price' => (int) $book->price,
                'quantity' => (int) $item['quantity'],
                'name' => substr($book->title, 0, 50),
            ];
        }

        $taxAmount = (int) round($subtotalAmount * 0.11);
        $shippingCost = 10000;
        $grossAmount = $subtotalAmount + $taxAmount + $shippingCost;

        // Atomic transaction: simpan record + generate snap_token
        $transaction = DB::transaction(function () use ($request, $validated, $books, $grossAmount, $subtotalAmount, $taxAmount, $shippingCost, $itemDetails) {
            $orderNumber = 'BS-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $user = $request->user();

            // Simpan transaksi utama (book_id diisi dengan item pertama untuk backward compatibility)
            $firstBookId = $validated['items'][0]['book_id'];
            $tx = Transaction::create([
                'order_number'  => $orderNumber,
                'customer_id'   => $user->id,
                'book_id'       => $firstBookId,
                'subtotal'      => $subtotalAmount,
                'tax_amount'    => $taxAmount,
                'shipping_cost' => $shippingCost,
                'total_amount'  => $grossAmount,
                'status'        => 'pending',
            ]);

            // Simpan item detail
            foreach ($validated['items'] as $item) {
                $book = $books->get($item['book_id']);
                TransactionItem::create([
                    'transaction_id' => $tx->id,
                    'book_id' => $item['book_id'],
                    'quantity' => $item['quantity'],
                    'price' => $book->price,
                ]);
            }

            // Konfigurasi Midtrans
            Config::$serverKey = config('services.midtrans.server_key');
            Config::$isProduction = (bool) config('services.midtrans.is_production');
            Config::$isSanitized = true;
            Config::$is3ds = true;

            $params = [
                'transaction_details' => [
                    'order_id' => $orderNumber,
                    'gross_amount' => (int) $grossAmount,
                ],
                'customer_details' => [
                    'first_name' => $user->name,
                    'email' => $user->email,
                ],
                'item_details' => $itemDetails,
            ];

            try {
                $snapToken = Snap::getSnapToken($params);
                $tx->update(['snap_token' => $snapToken]);
            } catch (\Exception $e) {
                // Jika Midtrans gagal, tetap simpan transaksi tanpa snap_token
                // Frontend akan menangani error ini
                \Log::error('Midtrans Snap Error: ' . $e->getMessage());
            }

            return $tx;
        });

        return response()->json([
            'transaction' => $transaction->load(['customer', 'items.book']),
            'snap_token' => $transaction->snap_token,
            'client_key' => config('services.midtrans.client_key'),
        ], 201);
    }

    /**
     * Callback dari Midtrans - update status transaksi
     */
    public function midtransCallback(Request $request)
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production');
        Config::$isSanitized = true;

        $notification = $request->all();
        $orderId = $notification['order_id'] ?? null;
        $transactionStatus = $notification['transaction_status'] ?? null;
        $fraudStatus = $notification['fraud_status'] ?? null;
        $paymentType = $notification['payment_type'] ?? null;

        // Verifikasi signature
        $serverKey = config('services.midtrans.server_key');
        $signatureKey = $notification['signature_key'] ?? '';
        $statusCode = $notification['status_code'] ?? '';
        $grossAmount = $notification['gross_amount'] ?? '';

        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if ($signatureKey !== $expectedSignature) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $transaction = Transaction::where('order_number', $orderId)->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // Update status berdasarkan notifikasi Midtrans
        $newStatus = 'pending';

        if ($transactionStatus === 'capture') {
            $newStatus = ($fraudStatus === 'accept') ? 'dibayar' : 'pending';
        } elseif ($transactionStatus === 'settlement') {
            $newStatus = 'dibayar';
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            $newStatus = 'dibatalkan';
        } elseif ($transactionStatus === 'pending') {
            $newStatus = 'pending';
        }

        $transaction->update([
            'status' => $newStatus,
            'payment_type' => $paymentType,
        ]);

        // Kurangi stok hanya jika pembayaran berhasil (atomic stock decrement)
        if ($newStatus === 'dibayar') {
            $items = $transaction->items;
            foreach ($items as $item) {
                Book::where('id', $item->book_id)
                    ->where('stock', '>=', $item->quantity)
                    ->decrement('stock', $item->quantity);
            }
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Frontend memanggil ini untuk update status setelah Snap popup
     */
    public function updateStatus(Request $request, Transaction $transaction)
    {
        // Hanya pemilik transaksi yang bisa update
        if ($transaction->customer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,dibayar,dikirim,selesai,dibatalkan',
        ]);

        $transaction->update($validated);

        return response()->json($transaction->load(['customer', 'items.book']));
    }

    public function show(Request $request, Transaction $transaction)
    {
        if ($transaction->customer_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json($transaction->load(['customer', 'book', 'items.book']));
    }

    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->customer_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,dibayar,dikirim,selesai,dibatalkan',
        ]);

        $previousStatus = $transaction->status;

        $transaction->update($validated);

        if (isset($validated['status']) && $validated['status'] === 'dibayar' && $previousStatus !== 'dibayar') {
            foreach ($transaction->items as $item) {
                Book::where('id', $item->book_id)
                    ->where('stock', '>=', $item->quantity)
                    ->decrement('stock', $item->quantity);
            }
        }

        return response()->json($transaction->load(['customer', 'book', 'items.book']));
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return response()->json(['message' => 'Transaction deleted successfully']);
    }
}
