<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminTransactionTest extends TestCase
{
    use RefreshDatabase;

    private function makeTransaction(User $user): Transaction
    {
        return Transaction::create([
            'order_number' => 'BS-ADM-' . uniqid(),
            'customer_id' => $user->id,
            'book_id' => null,
            'subtotal' => 100000,
            'tax_amount' => 11000,
            'shipping_cost' => 10000,
            'total_amount' => 121000,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_list_all_transactions(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($admin);

        $this->makeTransaction($user);
        $this->makeTransaction($user);

        $response = $this->getJson('/api/transactions');

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total'])
            ->assertJsonPath('total', 2);
    }

    public function test_admin_can_delete_transaction(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($admin);

        $tx = $this->makeTransaction($user);

        $response = $this->deleteJson("/api/transactions/{$tx->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('transactions', ['id' => $tx->id]);
    }

    public function test_regular_user_cannot_list_all_transactions(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/transactions');

        $response->assertForbidden();
    }

    public function test_regular_user_cannot_delete_transaction(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        Sanctum::actingAs($attacker);

        $tx = $this->makeTransaction($owner);

        $response = $this->deleteJson("/api/transactions/{$tx->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('transactions', ['id' => $tx->id]);
    }

    public function test_guest_cannot_list_transactions(): void
    {
        $response = $this->getJson('/api/transactions');

        $response->assertUnauthorized();
    }

    public function test_admin_can_search_transactions_by_order_number(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($admin);

        Transaction::create([
            'order_number' => 'BS-FIND-ME',
            'customer_id' => $user->id,
            'book_id' => null,
            'subtotal' => 50000,
            'tax_amount' => 5500,
            'shipping_cost' => 10000,
            'total_amount' => 65500,
            'status' => 'pending',
        ]);
        $this->makeTransaction($user);

        $response = $this->getJson('/api/transactions?search=FIND-ME');

        $response->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.order_number', 'BS-FIND-ME');
    }

    public function test_admin_can_filter_transactions_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($admin);

        $this->makeTransaction($user); // status: pending

        Transaction::create([
            'order_number' => 'BS-PAID-' . uniqid(),
            'customer_id' => $user->id,
            'book_id' => null,
            'subtotal' => 50000,
            'tax_amount' => 5500,
            'shipping_cost' => 10000,
            'total_amount' => 65500,
            'status' => 'dibayar',
        ]);

        $response = $this->getJson('/api/transactions?status=dibayar');

        $response->assertOk()
            ->assertJsonPath('total', 1);
    }
}
