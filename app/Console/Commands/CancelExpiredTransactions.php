<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;

class CancelExpiredTransactions extends Command
{
    protected $signature = 'transactions:cancel-expired';
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
