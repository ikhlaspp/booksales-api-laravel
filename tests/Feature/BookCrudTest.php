<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookCrudTest extends TestCase
{
    use RefreshDatabase;

    private function makeBook(array $overrides = []): Book
    {
        $genre = Genre::create(['name' => 'Fiksi', 'description' => 'Genre fiksi']);
        $author = Author::create(['name' => 'Penulis A', 'bio' => 'Bio penulis']);

        return Book::create(array_merge([
            'title' => 'Buku Test',
            'description' => 'Deskripsi buku',
            'price' => 100000,
            'stock' => 10,
            'genre_id' => $genre->id,
            'author_id' => $author->id,
        ], $overrides));
    }

    public function test_public_can_list_books(): void
    {
        $this->makeBook();

        $response = $this->getJson('/api/books');

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    public function test_public_can_view_single_book(): void
    {
        $book = $this->makeBook();

        $response = $this->getJson("/api/books/{$book->id}");

        $response->assertOk()
            ->assertJsonPath('id', $book->id)
            ->assertJsonPath('title', 'Buku Test');
    }

    public function test_search_filter_works(): void
    {
        $this->makeBook(['title' => 'Laravel untuk Pemula']);
        $this->makeBook(['title' => 'Python Dasar']);

        $response = $this->getJson('/api/books?search=Laravel');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Laravel untuk Pemula', $data[0]['title']);
    }

    public function test_genre_filter_works(): void
    {
        $genre1 = Genre::create(['name' => 'Fiksi']);
        $genre2 = Genre::create(['name' => 'Sains']);
        $author = Author::create(['name' => 'Penulis B']);

        Book::create(['title' => 'Novel A', 'price' => 50000, 'stock' => 5, 'genre_id' => $genre1->id, 'author_id' => $author->id]);
        Book::create(['title' => 'Sains B', 'price' => 75000, 'stock' => 3, 'genre_id' => $genre2->id, 'author_id' => $author->id]);

        $response = $this->getJson("/api/books?genre_id={$genre1->id}");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Novel A', $data[0]['title']);
    }

    public function test_admin_can_create_book(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $genre = Genre::create(['name' => 'Teknologi']);
        $author = Author::create(['name' => 'Penulis C']);

        $response = $this->postJson('/api/books', [
            'title' => 'Buku Baru',
            'price' => 150000,
            'stock' => 20,
            'genre_id' => $genre->id,
            'author_id' => $author->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Buku Baru');

        $this->assertDatabaseHas('books', ['title' => 'Buku Baru', 'price' => 150000]);
    }

    public function test_non_admin_cannot_create_book(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/books', [
            'title' => 'Buku Curang',
            'price' => 1,
            'stock' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_guest_cannot_create_book(): void
    {
        $response = $this->postJson('/api/books', [
            'title' => 'Buku Curang',
            'price' => 1,
            'stock' => 1,
        ]);

        $response->assertUnauthorized();
    }

    public function test_admin_can_update_book(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $book = $this->makeBook();

        $response = $this->putJson("/api/books/{$book->id}", [
            'price' => 200000,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.price', 200000);

        $this->assertDatabaseHas('books', ['id' => $book->id, 'price' => 200000]);
    }

    public function test_admin_can_delete_book(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $book = $this->makeBook();

        $response = $this->deleteJson("/api/books/{$book->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_non_admin_cannot_delete_book(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $book = $this->makeBook();

        $response = $this->deleteJson("/api/books/{$book->id}");

        $response->assertForbidden();
    }

    public function test_create_book_requires_title_and_price(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/books', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'price', 'stock']);
    }
}
