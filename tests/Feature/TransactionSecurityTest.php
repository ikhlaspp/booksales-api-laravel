<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function makeTransaction(User $user, float $total = 100000): Transaction
    {
        return Transaction::create([
            'order_number' => 'BS-TEST-' . uniqid(),
            'customer_id' => $user->id,
            'book_id' => null,
            'subtotal' => $total,
            'tax_amount' => (int) round($total * 0.11),
            'shipping_cost' => 10000,
            'total_amount' => $total + (int) round($total * 0.11) + 10000,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_view_own_transaction(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $tx = $this->makeTransaction($user);

        $response = $this->getJson("/api/transactions/{$tx->id}");

        $response->assertOk()
            ->assertJsonPath('id', $tx->id);
    }

    public function test_user_cannot_view_other_users_transaction(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        Sanctum::actingAs($userA);

        $txB = $this->makeTransaction($userB);

        $response = $this->getJson("/api/transactions/{$txB->id}");

        $response->assertForbidden();
    }

    public function test_user_cannot_update_other_users_transaction(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        Sanctum::actingAs($userA);

        $txB = $this->makeTransaction($userB);

        $response = $this->putJson("/api/transactions/{$txB->id}", [
            'status' => 'dibayar',
        ]);

        $response->assertForbidden();
    }

    public function test_user_can_update_own_transaction_status(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $tx = $this->makeTransaction($user);

        $response = $this->putJson("/api/transactions/{$tx->id}", [
            'status' => 'dibatalkan',
        ]);

        $response->assertOk();
        $this->assertEquals('dibatalkan', $tx->fresh()->status);
    }

    /**
     * Bug B2: update() currently allows user to change total_amount.
     * This test must PASS after the bugfix — total_amount must not change.
     */
    public function test_user_cannot_manipulate_total_amount(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $tx = $this->makeTransaction($user, 100000);

        $originalTotal = $tx->total_amount;

        $this->putJson("/api/transactions/{$tx->id}", [
            'total_amount' => 1,
        ]);

        $this->assertEquals($originalTotal, $tx->fresh()->total_amount);
    }

    public function test_guest_cannot_view_any_transaction(): void
    {
        $user = User::factory()->create();
        $tx = $this->makeTransaction($user);

        $response = $this->getJson("/api/transactions/{$tx->id}");

        $response->assertUnauthorized();
    }

    public function test_admin_can_view_any_transaction(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($admin);

        $tx = $this->makeTransaction($user);

        $response = $this->getJson("/api/transactions/{$tx->id}");

        $response->assertOk();
    }

    public function test_stock_decrements_when_user_updates_status_to_dibayar(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $genre = Genre::create(['name' => 'Fiksi']);
        $author = Author::create(['name' => 'Penulis']);
        $book = Book::create([
            'title' => 'Buku Stock',
            'price' => 100000,
            'stock' => 5,
            'genre_id' => $genre->id,
            'author_id' => $author->id,
        ]);

        $tx = Transaction::create([
            'order_number' => 'BS-STOCK-' . uniqid(),
            'customer_id' => $user->id,
            'book_id' => $book->id,
            'subtotal' => 100000,
            'tax_amount' => 11000,
            'shipping_cost' => 10000,
            'total_amount' => 121000,
            'status' => 'pending',
        ]);

        TransactionItem::create([
            'transaction_id' => $tx->id,
            'book_id' => $book->id,
            'quantity' => 2,
            'price' => 100000,
        ]);

        $this->putJson("/api/transactions/{$tx->id}", ['status' => 'dibayar']);

        $this->assertEquals(3, $book->fresh()->stock); // 5 - 2 = 3
    }
}
