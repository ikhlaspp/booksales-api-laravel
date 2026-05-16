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

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function makeBook(array $overrides = []): Book
    {
        $genre = Genre::create(['name' => 'Fiksi']);
        $author = Author::create(['name' => 'Penulis']);

        return Book::create(array_merge([
            'title' => 'Buku Test',
            'price' => 100000,
            'stock' => 10,
            'genre_id' => $genre->id,
            'author_id' => $author->id,
        ], $overrides));
    }

    public function test_authenticated_user_can_checkout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $book = $this->makeBook(['price' => 100000, 'stock' => 5]);

        $response = $this->postJson('/api/transactions', [
            'items' => [
                ['book_id' => $book->id, 'quantity' => 2],
            ],
            'shipping_address' => 'Jl. Test No. 1',
            'city' => 'Jakarta',
            'postal_code' => '12345',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['transaction', 'snap_token', 'client_key']);

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_checkout_calculates_correct_totals(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $book = $this->makeBook(['price' => 100000, 'stock' => 5]);

        $this->postJson('/api/transactions', [
            'items' => [
                ['book_id' => $book->id, 'quantity' => 2],
            ],
            'shipping_address' => 'Jl. Test',
            'city' => 'Kota',
            'postal_code' => '00000',
        ]);

        $transaction = Transaction::where('customer_id', $user->id)->first();

        $this->assertNotNull($transaction);
        $this->assertEquals(200000, (int)$transaction->subtotal);            // 100000 × 2
        $this->assertEquals(22000, (int)$transaction->tax_amount);           // 200000 × 11%
        $this->assertEquals(10000, (int)$transaction->shipping_cost);        // flat
        $this->assertEquals(232000, (int)$transaction->total_amount);        // subtotal + tax + shipping
    }

    public function test_checkout_saves_transaction_items(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $book = $this->makeBook(['price' => 75000, 'stock' => 10]);

        $this->postJson('/api/transactions', [
            'items' => [
                ['book_id' => $book->id, 'quantity' => 3],
            ],
            'shipping_address' => 'Jl. Test',
            'city' => 'Kota',
            'postal_code' => '00000',
        ]);

        $transaction = Transaction::where('customer_id', $user->id)->first();

        $this->assertDatabaseHas('transaction_items', [
            'transaction_id' => $transaction->id,
            'book_id' => $book->id,
            'quantity' => 3,
            'price' => 75000,
        ]);
    }

    public function test_checkout_fails_when_stock_insufficient(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $book = $this->makeBook(['stock' => 2]);

        $response = $this->postJson('/api/transactions', [
            'items' => [
                ['book_id' => $book->id, 'quantity' => 5],
            ],
            'shipping_address' => 'Jl. Test',
            'city' => 'Kota',
            'postal_code' => '00000',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', fn($v) => str_contains($v, 'tidak mencukupi'));
    }

    public function test_checkout_fails_with_nonexistent_book(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/transactions', [
            'items' => [
                ['book_id' => 99999, 'quantity' => 1],
            ],
            'shipping_address' => 'Jl. Test',
            'city' => 'Kota',
            'postal_code' => '00000',
        ]);

        $response->assertUnprocessable();
    }

    public function test_checkout_requires_auth(): void
    {
        $book = $this->makeBook();

        $response = $this->postJson('/api/transactions', [
            'items' => [['book_id' => $book->id, 'quantity' => 1]],
            'shipping_address' => 'Jl. Test',
            'city' => 'Kota',
            'postal_code' => '00000',
        ]);

        $response->assertUnauthorized();
    }

    public function test_checkout_requires_at_least_one_item(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/transactions', [
            'items' => [],
            'shipping_address' => 'Jl. Test',
            'city' => 'Kota',
            'postal_code' => '00000',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_admin_cannot_checkout(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $book = $this->makeBook();

        $response = $this->postJson('/api/transactions', [
            'items' => [['book_id' => $book->id, 'quantity' => 1]],
            'shipping_address' => 'Jl. Test',
            'city' => 'Kota',
            'postal_code' => '00000',
        ]);

        $response->assertForbidden();
    }
}
