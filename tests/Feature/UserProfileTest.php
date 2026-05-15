<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_their_address(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/user/profile', [
            'address'     => 'Jl. Merdeka No. 123',
            'city'        => 'Jakarta Selatan',
            'postal_code' => '12345',
        ]);

        $response->assertOk()
            ->assertJsonPath('address', 'Jl. Merdeka No. 123')
            ->assertJsonPath('city', 'Jakarta Selatan')
            ->assertJsonPath('postal_code', '12345');

        $this->assertDatabaseHas('users', [
            'id'          => $user->id,
            'address'     => 'Jl. Merdeka No. 123',
            'city'        => 'Jakarta Selatan',
            'postal_code' => '12345',
        ]);
    }

    public function test_unauthenticated_user_cannot_update_profile(): void
    {
        $response = $this->putJson('/api/user/profile', [
            'address' => 'X',
        ]);

        $response->assertUnauthorized();
    }

    public function test_address_fields_are_nullable(): void
    {
        $user = User::factory()->create([
            'address'     => 'Old',
            'city'        => 'Old City',
            'postal_code' => '00000',
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/user/profile', [
            'address'     => null,
            'city'        => null,
            'postal_code' => null,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id'      => $user->id,
            'address' => null,
            'city'    => null,
        ]);
    }

    public function test_overly_long_strings_are_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/user/profile', [
            'address' => str_repeat('A', 300),
        ]);

        $response->assertStatus(422);
    }
}
