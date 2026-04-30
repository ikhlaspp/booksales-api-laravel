<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Menghitung data statistik
        $totalRevenue = DB::table('transactions')->sum('total_amount');
        $totalCustomers = DB::table('users')->where('role', 'customer')->count();
        $totalBooks = DB::table('books')->sum('stock'); 
        $totalTransactions = DB::table('transactions')->count();

        $recentTransactions = DB::table('transactions')
            ->join('users', 'transactions.customer_id', '=', 'users.id')
            ->select(
                'transactions.order_number as id', 
                'users.name as user', 
                'transactions.created_at as date', 
                'transactions.total_amount as amount'
            )
            ->orderBy('transactions.created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'user' => $transaction->user,
                    'date' => Carbon::parse($transaction->date)->format('d M Y'),
                    'amount' => 'Rp ' . number_format($transaction->amount, 0, ',', '.'),
                    'status' => 'Completed', // Status dummy untuk saat ini
                ];
            });

        // Mengembalikan response JSON yang formatnya sesuai dengan di React
        return response()->json([
            'data' => [
                'stats' => [
                    ['name' => 'Total Pendapatan', 'stat' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'), 'icon' => 'cash'],
                    ['name' => 'Total Pelanggan', 'stat' => $totalCustomers, 'icon' => 'users'],
                    ['name' => 'Stok Buku Tersedia', 'stat' => $totalBooks, 'icon' => 'book'],
                    ['name' => 'Total Transaksi', 'stat' => $totalTransactions, 'icon' => 'clock'],
                ],
                'recentTransactions' => $recentTransactions
            ]
        ]);
    }
}