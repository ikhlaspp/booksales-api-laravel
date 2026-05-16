<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MidtransCallbackTest extends TestCase
{
    use RefreshDatabase;

    private string $serverKey = 'test-server-key';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.midtrans.server_key' => $this->serverKey]);
    }

    private function makeSignature(string $orderId, string $statusCode, string $grossAmount): string
    {
        return hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
    }

    private function makeTransactionWithBook(): array
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'Fiksi']);
        $author = Author::create(['name' => 'Penulis']);
        $book = Book::create(['title' => 'Buku', 'price' => 100000, 'stock' => 5, 'genre_id' => $genre->id, 'author_id' => $author->id]);

        $tx = Transaction::create([
            'order_number' => 'BS-CB-' . uniqid(),
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
            'quantity' => 1,
            'price' => 100000,
        ]);

        return [$tx, $book];
    }

    public function test_settlement_status_marks_transaction_as_dibayar(): void
    {
        [$tx] = $this->makeTransactionWithBook();
        $grossAmount = number_format($tx->total_amount, 2, '.', '');
        $signature = $this->makeSignature($tx->order_number, '200', $grossAmount);

        $response = $this->postJson('/api/midtrans/callback', [
            'order_id' => $tx->order_number,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'bank_transfer',
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
        ]);

        $response->assertOk();
        $this->assertEquals('dibayar', $tx->fresh()->status);
    }

    public function test_settlement_decrements_stock(): void
    {
        [$tx, $book] = $this->makeTransactionWithBook();
        $originalStock = $book->stock; // 5
        $grossAmount = number_format($tx->total_amount, 2, '.', '');
        $signature = $this->makeSignature($tx->order_number, '200', $grossAmount);

        $this->postJson('/api/midtrans/callback', [
            'order_id' => $tx->order_number,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'bank_transfer',
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
        ]);

        $this->assertEquals($originalStock - 1, $book->fresh()->stock); // 5 - 1 = 4
    }

    public function test_expire_status_marks_transaction_as_dibatalkan(): void
    {
        [$tx] = $this->makeTransactionWithBook();
        $grossAmount = number_format($tx->total_amount, 2, '.', '');
        $signature = $this->makeSignature($tx->order_number, '202', $grossAmount);

        $response = $this->postJson('/api/midtrans/callback', [
            'order_id' => $tx->order_number,
            'transaction_status' => 'expire',
            'fraud_status' => null,
            'payment_type' => 'bank_transfer',
            'status_code' => '202',
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
        ]);

        $response->assertOk();
        $this->assertEquals('dibatalkan', $tx->fresh()->status);
    }

    public function test_capture_with_fraud_accept_marks_as_dibayar(): void
    {
        [$tx] = $this->makeTransactionWithBook();
        $grossAmount = number_format($tx->total_amount, 2, '.', '');
        $signature = $this->makeSignature($tx->order_number, '200', $grossAmount);

        $response = $this->postJson('/api/midtrans/callback', [
            'order_id' => $tx->order_number,
            'transaction_status' => 'capture',
            'fraud_status' => 'accept',
            'payment_type' => 'credit_card',
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
        ]);

        $response->assertOk();
        $this->assertEquals('dibayar', $tx->fresh()->status);
    }

    public function test_invalid_signature_returns_403(): void
    {
        [$tx] = $this->makeTransactionWithBook();
        $grossAmount = number_format($tx->total_amount, 2, '.', '');

        $response = $this->postJson('/api/midtrans/callback', [
            'order_id' => $tx->order_number,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'bank_transfer',
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'signature_key' => 'invalid-signature-completely-wrong',
        ]);

        $response->assertForbidden();
    }

    public function test_nonexistent_order_id_returns_404(): void
    {
        $grossAmount = '100000.00';
        $signature = $this->makeSignature('BS-FAKE-ORDER', '200', $grossAmount);

        $response = $this->postJson('/api/midtrans/callback', [
            'order_id' => 'BS-FAKE-ORDER',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'bank_transfer',
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
        ]);

        $response->assertNotFound();
    }
}
