<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Transaction;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = DB::table('users')->count();
        $totalBooks = DB::table('books')->count();
        $totalTransactions = DB::table('transactions')->count();
        $totalRevenue = DB::table('transactions')->where('status', 'selesai')->sum('total_amount');

        $recentTransactions = DB::table('transactions')
            ->join('users', 'transactions.customer_id', '=', 'users.id')
            ->leftJoin('books', 'transactions.book_id', '=', 'books.id')
            ->select(
                'transactions.order_number as id',
                'users.name as user',
                'books.title as book',
                'transactions.created_at as date',
                'transactions.total_amount as amount',
                'transactions.status as status'
            )
            ->orderBy('transactions.created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'user' => $transaction->user,
                    'book' => $transaction->book ?? '-',
                    'date' => Carbon::parse($transaction->date)->format('d M Y'),
                    'amount' => 'Rp ' . number_format($transaction->amount, 0, ',', '.'),
                    'status' => ucfirst($transaction->status ?? 'pending'),
                ];
            });

        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite'
            ? "strftime('%m', created_at) as month"
            : "DATE_FORMAT(created_at, \"%b\") as month";

        $salesTrend = Transaction::selectRaw("{$monthExpr}, COUNT(*) as terbeli")
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->groupBy('month')
            ->orderByRaw('MIN(created_at)')
            ->get();

        return response()->json([
            'total_users' => $totalUsers,
            'total_books' => $totalBooks,
            'total_transactions' => $totalTransactions,
            'revenue' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
            'recent_transactions' => $recentTransactions,
            'sales_trend' => $salesTrend,
        ]);
    }
}