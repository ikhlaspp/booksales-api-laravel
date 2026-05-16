<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_returns_token(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'user', 'role'])
            ->assertJsonPath('token_type', 'Bearer');
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_with_nonexistent_email_returns_401(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_without_email_returns_422(): void
    {
        $response = $this->postJson('/api/login', [
            'password' => 'password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_without_password_returns_422(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_update_password_succeeds(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/user/password', [
            'current_password' => 'old-password',
            'new_password' => 'new-password123',
            'new_password_confirmation' => 'new-password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Password berhasil diubah.');

        $this->assertTrue(
            Hash::check('new-password123', $user->fresh()->password)
        );
    }

    public function test_update_password_with_wrong_current_returns_422(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/user/password', [
            'current_password' => 'wrong-password',
            'new_password' => 'new-password123',
            'new_password_confirmation' => 'new-password123',
        ]);

        $response->assertUnprocessable();
    }

    public function test_update_password_unauthenticated_returns_401(): void
    {
        $response = $this->putJson('/api/user/password', [
            'current_password' => 'old',
            'new_password' => 'new123456',
            'new_password_confirmation' => 'new123456',
        ]);

        $response->assertUnauthorized();
    }
}
