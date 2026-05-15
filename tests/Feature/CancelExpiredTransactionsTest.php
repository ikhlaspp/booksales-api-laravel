<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CancelExpiredTransactionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_transactions_older_than_one_hour_are_cancelled(): void
    {
        $user = User::factory()->create();

        $expired = Transaction::create([
            'order_number' => 'BS-EXPIRED-1',
            'customer_id'  => $user->id,
            'book_id'      => null,
            'subtotal'     => 100000,
            'tax_amount'   => 11000,
            'shipping_cost'=> 10000,
            'total_amount' => 121000,
            'status'       => 'pending',
            'created_at'   => Carbon::now()->subMinutes(61),
            'updated_at'   => Carbon::now()->subMinutes(61),
        ]);

        $fresh = Transaction::create([
            'order_number' => 'BS-FRESH-1',
            'customer_id'  => $user->id,
            'book_id'      => null,
            'subtotal'     => 100000,
            'tax_amount'   => 11000,
            'shipping_cost'=> 10000,
            'total_amount' => 121000,
            'status'       => 'pending',
            'created_at'   => Carbon::now()->subMinutes(10),
            'updated_at'   => Carbon::now()->subMinutes(10),
        ]);

        $paid = Transaction::create([
            'order_number' => 'BS-PAID-1',
            'customer_id'  => $user->id,
            'book_id'      => null,
            'subtotal'     => 100000,
            'tax_amount'   => 11000,
            'shipping_cost'=> 10000,
            'total_amount' => 121000,
            'status'       => 'dibayar',
            'created_at'   => Carbon::now()->subMinutes(120),
            'updated_at'   => Carbon::now()->subMinutes(120),
        ]);

        $this->artisan('transactions:cancel-expired')->assertSuccessful();

        $this->assertEquals('dibatalkan', $expired->fresh()->status);
        $this->assertEquals('pending',    $fresh->fresh()->status);
        $this->assertEquals('dibayar',    $paid->fresh()->status);
    }
}
