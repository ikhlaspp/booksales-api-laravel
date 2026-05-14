<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['customer', 'book'])->latest();
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_number' => 'required|string',
            'book_id' => 'required|exists:books,id',
            'total_amount' => 'required|numeric',
        ]);

        $validated['customer_id'] = $request->user()->id;

        $transaction = Transaction::create($validated);

        return response()->json($transaction->load(['customer', 'book']), 201);
    }

    public function show(Request $request, Transaction $transaction)
    {
        if ($transaction->customer_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json($transaction->load(['customer', 'book']));
    }

    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->customer_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'order_number' => 'sometimes|string',
            'book_id' => 'sometimes|exists:books,id',
            'total_amount' => 'sometimes|numeric',
            'status' => 'sometimes|in:pending,dibayar,dikirim,selesai,dibatalkan',
        ]);

        $transaction->update($validated);

        return response()->json($transaction->load(['customer', 'book']));
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return response()->json(['message' => 'Transaction deleted successfully']);
    }
}
