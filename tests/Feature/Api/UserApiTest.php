<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_all_users(): void
    {
        $users = User::factory()->count(3)->create();
        Sanctum::actingAs($users->first());

        $response = $this->getJson('/api/v1/users');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'name', 'email', 'role'],
                ],
            ]);
    }

    public function test_authenticated_user_can_get_own_profile(): void
    {
        $user = User::factory()->create(['role' => 'ai_agent']);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/users/me');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => 'ai_agent',
                ],
            ]);
    }
}
