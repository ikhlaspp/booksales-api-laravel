<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GenreAuthorTest extends TestCase
{
    use RefreshDatabase;

    // ─────────── GENRE ───────────

    public function test_public_can_list_genres(): void
    {
        Genre::create(['name' => 'Fiksi']);
        Genre::create(['name' => 'Sains']);

        $response = $this->getJson('/api/genres');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_public_can_show_genre(): void
    {
        $genre = Genre::create(['name' => 'Horor']);

        $response = $this->getJson("/api/genres/{$genre->id}");

        $response->assertOk()
            ->assertJsonPath('name', 'Horor');
    }

    public function test_admin_can_create_genre(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/genres', [
            'name' => 'Thriller',
            'description' => 'Genre thriller',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('genres', ['name' => 'Thriller']);
    }

    public function test_non_admin_cannot_create_genre(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/genres', ['name' => 'Curang']);

        $response->assertForbidden();
    }

    public function test_admin_can_update_genre(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $genre = Genre::create(['name' => 'Lama']);

        $response = $this->putJson("/api/genres/{$genre->id}", ['name' => 'Baru']);

        $response->assertOk();
        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => 'Baru']);
    }

    public function test_admin_can_delete_genre(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $genre = Genre::create(['name' => 'Hapus Saya']);

        $response = $this->deleteJson("/api/genres/{$genre->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }

    public function test_non_admin_cannot_delete_genre(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::create(['name' => 'Aman']);

        $response = $this->deleteJson("/api/genres/{$genre->id}");

        $response->assertForbidden();
    }

    // ─────────── AUTHOR ───────────

    public function test_public_can_list_authors(): void
    {
        Author::create(['name' => 'Penulis A']);
        Author::create(['name' => 'Penulis B']);

        $response = $this->getJson('/api/authors');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_public_can_show_author(): void
    {
        $author = Author::create(['name' => 'Pramoedya']);

        $response = $this->getJson("/api/authors/{$author->id}");

        $response->assertOk()
            ->assertJsonPath('name', 'Pramoedya');
    }

    public function test_admin_can_create_author(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/authors', [
            'name' => 'Penulis Baru',
            'bio' => 'Bio singkat penulis.',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('authors', ['name' => 'Penulis Baru']);
    }

    public function test_non_admin_cannot_create_author(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/authors', ['name' => 'Curang']);

        $response->assertForbidden();
    }

    public function test_admin_can_delete_author(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $author = Author::create(['name' => 'Hapus Saya']);

        $response = $this->deleteJson("/api/authors/{$author->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('authors', ['id' => $author->id]);
    }
}
