<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeWebTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    // ── Index ──────────────────────────────────────────

    public function test_recipes_page_loads_successfully(): void
    {
        $response = $this->get('/recipes');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('recipes/index')
            ->has('ownRecipes')
            ->has('sharedRecipes')
            ->has('users')
        );
    }

    // ── Create ─────────────────────────────────────────

    public function test_create_page_loads_successfully(): void
    {
        $response = $this->get('/recipes/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('recipes/create')
        );
    }

    // ── Show ──────────────────────────────────────────

    public function test_can_view_own_recipe(): void
    {
        $recipe = Recipe::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->get("/recipes/{$recipe->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('recipes/show')
            ->where('recipe.title', $recipe->title)
        );
    }

    // ── Store ─────────────────────────────────────────

    public function test_can_create_recipe(): void
    {
        $response = $this->post('/recipes', [
            'title' => 'Spaghetti Bolognese',
            'description' => 'Klassiker der italienischen Kueche',
            'category' => 'cooking',
            'servings' => 4,
            'prep_time' => 15,
            'cook_time' => 30,
            'ingredients' => "500g Spaghetti\n400g Hackfleisch\n1 Dose Tomaten",
            'instructions' => "Wasser kochen\nSpaghetti kochen\nSosse zubereiten",
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('recipes', [
            'title' => 'Spaghetti Bolognese',
            'owner_id' => $this->user->id,
            'category' => 'cooking',
        ]);
    }

    public function test_create_recipe_requires_title(): void
    {
        $response = $this->post('/recipes', [
            'category' => 'cooking',
            'ingredients' => 'Some ingredients',
            'instructions' => 'Some instructions',
        ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_create_recipe_requires_ingredients(): void
    {
        $response = $this->post('/recipes', [
            'title' => 'Test Recipe',
            'category' => 'cooking',
            'instructions' => 'Some instructions',
        ]);

        $response->assertSessionHasErrors('ingredients');
    }

    public function test_create_recipe_requires_valid_category(): void
    {
        $response = $this->post('/recipes', [
            'title' => 'Test Recipe',
            'category' => 'invalid',
            'ingredients' => 'Some ingredients',
            'instructions' => 'Some instructions',
        ]);

        $response->assertSessionHasErrors('category');
    }

    // ── Update ────────────────────────────────────────

    public function test_can_update_own_recipe(): void
    {
        $recipe = Recipe::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->put("/recipes/{$recipe->id}", [
            'title' => 'Updated Recipe',
            'category' => 'baking',
            'ingredients' => 'Updated ingredients',
            'instructions' => 'Updated instructions',
        ], ['X-Inertia' => 'true', 'X-Inertia-Version' => '']);

        $response->assertRedirect();

        $recipe->refresh();
        $this->assertEquals('Updated Recipe', $recipe->title);
        $this->assertEquals('baking', $recipe->category);
    }

    // ── Favorite ──────────────────────────────────────

    public function test_can_toggle_favorite(): void
    {
        $recipe = Recipe::factory()->create([
            'owner_id' => $this->user->id,
            'is_favorite' => false,
        ]);

        $response = $this->patch("/recipes/{$recipe->id}/favorite");

        $response->assertRedirect();
        $this->assertTrue($recipe->fresh()->is_favorite);
    }

    // ── Destroy ───────────────────────────────────────

    public function test_can_delete_own_recipe(): void
    {
        $recipe = Recipe::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->delete("/recipes/{$recipe->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
    }

    // ── Authorization ─────────────────────────────────

    public function test_cannot_view_others_recipe(): void
    {
        $other = User::factory()->create();
        $recipe = Recipe::factory()->create(['owner_id' => $other->id]);

        $response = $this->get("/recipes/{$recipe->id}");

        $response->assertForbidden();
    }

    public function test_cannot_delete_others_recipe(): void
    {
        $other = User::factory()->create();
        $recipe = Recipe::factory()->create(['owner_id' => $other->id]);

        $response = $this->delete("/recipes/{$recipe->id}");

        $response->assertForbidden();
    }

    // ── Guests ────────────────────────────────────────

    public function test_guests_cannot_access_recipes(): void
    {
        auth()->logout();

        $response = $this->get('/recipes');

        $response->assertRedirect(route('login'));
    }
}
