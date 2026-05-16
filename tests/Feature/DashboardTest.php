<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'total_users',
                'total_books',
                'total_transactions',
                'revenue',
                'recent_transactions',
                'sales_trend',
            ]);
    }

    public function test_dashboard_total_users_is_accurate(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonPath('total_users', 4); // 3 users + 1 admin
    }

    public function test_non_admin_cannot_access_dashboard(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/dashboard');

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/dashboard');

        $response->assertUnauthorized();
    }
}
