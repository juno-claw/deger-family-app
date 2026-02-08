<?php

namespace Tests\Feature\Api;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecipeApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    // ── Index ──────────────────────────────────────────

    public function test_can_list_own_recipes(): void
    {
        Recipe::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->getJson('/api/v1/recipes');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'title', 'category', 'ingredients', 'instructions', 'owner']],
            ]);
    }

    public function test_can_list_shared_recipes(): void
    {
        $owner = User::factory()->create();
        $recipe = Recipe::factory()->create(['owner_id' => $owner->id]);
        $recipe->sharedWith()->attach($this->user->id, ['permission' => 'view']);

        $response = $this->getJson('/api/v1/recipes');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', $recipe->title);
    }

    public function test_cannot_see_other_users_private_recipes(): void
    {
        $other = User::factory()->create();
        Recipe::factory()->create(['owner_id' => $other->id]);

        $response = $this->getJson('/api/v1/recipes');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ── Store ──────────────────────────────────────────

    public function test_can_create_recipe(): void
    {
        $response = $this->postJson('/api/v1/recipes', [
            'title' => 'Spaghetti Bolognese',
            'description' => 'Klassiker',
            'category' => 'cooking',
            'servings' => 4,
            'prep_time' => 15,
            'cook_time' => 30,
            'ingredients' => "500g Spaghetti\n400g Hackfleisch",
            'instructions' => "Wasser kochen\nSosse zubereiten",
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Spaghetti Bolognese')
            ->assertJsonPath('data.category', 'cooking')
            ->assertJsonPath('data.servings', 4)
            ->assertJsonPath('data.owner.id', $this->user->id);

        $this->assertDatabaseHas('recipes', [
            'title' => 'Spaghetti Bolognese',
            'owner_id' => $this->user->id,
        ]);
    }

    public function test_cannot_create_recipe_without_title(): void
    {
        $response = $this->postJson('/api/v1/recipes', [
            'category' => 'cooking',
            'ingredients' => 'Some ingredients',
            'instructions' => 'Some instructions',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('title');
    }

    public function test_cannot_create_recipe_with_invalid_category(): void
    {
        $response = $this->postJson('/api/v1/recipes', [
            'title' => 'Test',
            'category' => 'invalid',
            'ingredients' => 'Some ingredients',
            'instructions' => 'Some instructions',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('category');
    }

    public function test_cannot_create_recipe_without_ingredients(): void
    {
        $response = $this->postJson('/api/v1/recipes', [
            'title' => 'Test',
            'category' => 'cooking',
            'instructions' => 'Some instructions',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('ingredients');
    }

    // ── Show ──────────────────────────────────────────

    public function test_can_show_own_recipe(): void
    {
        $recipe = Recipe::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->getJson("/api/v1/recipes/{$recipe->id}");

        $response->assertOk()
            ->assertJsonPath('data.title', $recipe->title)
            ->assertJsonStructure([
                'data' => ['id', 'title', 'description', 'category', 'servings', 'prep_time', 'cook_time', 'ingredients', 'instructions', 'is_favorite', 'owner', 'shared_with'],
            ]);
    }

    public function test_cannot_show_other_users_private_recipe(): void
    {
        $other = User::factory()->create();
        $recipe = Recipe::factory()->create(['owner_id' => $other->id]);

        $response = $this->getJson("/api/v1/recipes/{$recipe->id}");

        $response->assertForbidden();
    }

    // ── Update ─────────────────────────────────────────

    public function test_can_update_own_recipe(): void
    {
        $recipe = Recipe::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->putJson("/api/v1/recipes/{$recipe->id}", [
            'title' => 'Updated Recipe',
            'category' => 'baking',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Recipe')
            ->assertJsonPath('data.category', 'baking');
    }

    public function test_cannot_update_other_users_recipe(): void
    {
        $other = User::factory()->create();
        $recipe = Recipe::factory()->create(['owner_id' => $other->id]);

        $response = $this->putJson("/api/v1/recipes/{$recipe->id}", [
            'title' => 'Hacked',
        ]);

        $response->assertForbidden();
    }

    public function test_shared_user_with_edit_can_update(): void
    {
        $owner = User::factory()->create();
        $recipe = Recipe::factory()->create(['owner_id' => $owner->id]);
        $recipe->sharedWith()->attach($this->user->id, ['permission' => 'edit']);

        $response = $this->putJson("/api/v1/recipes/{$recipe->id}", [
            'title' => 'Updated by editor',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated by editor');
    }

    public function test_shared_user_with_view_cannot_update(): void
    {
        $owner = User::factory()->create();
        $recipe = Recipe::factory()->create(['owner_id' => $owner->id]);
        $recipe->sharedWith()->attach($this->user->id, ['permission' => 'view']);

        $response = $this->putJson("/api/v1/recipes/{$recipe->id}", [
            'title' => 'Should fail',
        ]);

        $response->assertForbidden();
    }

    // ── Destroy ────────────────────────────────────────

    public function test_can_delete_own_recipe(): void
    {
        $recipe = Recipe::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->deleteJson("/api/v1/recipes/{$recipe->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
    }

    public function test_cannot_delete_other_users_recipe(): void
    {
        $other = User::factory()->create();
        $recipe = Recipe::factory()->create(['owner_id' => $other->id]);

        $response = $this->deleteJson("/api/v1/recipes/{$recipe->id}");

        $response->assertForbidden();
    }
}
