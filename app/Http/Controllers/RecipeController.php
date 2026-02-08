<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecipeRequest;
use App\Http\Requests\UpdateRecipeRequest;
use App\Models\Recipe;
use App\Models\User;
use App\Traits\HasSharing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    use HasSharing;

    /**
     * Display a listing of recipes.
     */
    public function index(Request $request): Response
    {
        $recipes = Recipe::accessibleBy(auth()->user())
            ->with(['owner', 'sharedWith'])
            ->latest()
            ->get();

        // Sort: favorites first, then by updated_at desc
        $recipes = $recipes->sortByDesc('updated_at')->sortByDesc('is_favorite')->values();

        $ownRecipes = $recipes->where('owner_id', auth()->id())->values();
        $sharedRecipes = $recipes->where('owner_id', '!=', auth()->id())->values();

        $users = User::where('id', '!=', auth()->id())->get(['id', 'name', 'email']);

        $category = $request->query('category');

        return Inertia::render('recipes/index', compact('ownRecipes', 'sharedRecipes', 'users', 'category'));
    }

    /**
     * Show the form for creating a new recipe.
     */
    public function create(): Response
    {
        return Inertia::render('recipes/create');
    }

    /**
     * Store a newly created recipe.
     */
    public function store(StoreRecipeRequest $request): RedirectResponse
    {
        $recipe = Recipe::create([
            ...$request->validated(),
            'owner_id' => auth()->id(),
        ]);

        return redirect()->route('recipes.show', $recipe);
    }

    /**
     * Display the specified recipe.
     */
    public function show(Recipe $recipe): Response
    {
        $this->authorize('view', $recipe);

        $recipe->load(['owner', 'sharedWith']);

        $users = User::where('id', '!=', auth()->id())->get(['id', 'name', 'email']);

        return Inertia::render('recipes/show', compact('recipe', 'users'));
    }

    /**
     * Update the specified recipe.
     */
    public function update(UpdateRecipeRequest $request, Recipe $recipe): RedirectResponse
    {
        $this->authorize('update', $recipe);

        $recipe->update($request->validated());

        return redirect()->back();
    }

    /**
     * Remove the specified recipe.
     */
    public function destroy(Recipe $recipe): RedirectResponse
    {
        $this->authorize('delete', $recipe);

        $recipe->delete();

        return redirect()->route('recipes.index');
    }

    /**
     * Toggle the favorite status of a recipe.
     */
    public function toggleFavorite(Recipe $recipe): RedirectResponse
    {
        $this->authorize('update', $recipe);

        $recipe->update(['is_favorite' => ! $recipe->is_favorite]);

        return redirect()->back();
    }

    /**
     * Share a recipe with another user.
     */
    public function share(Request $request, Recipe $recipe): RedirectResponse
    {
        return $this->performShare($request, $recipe);
    }

    /**
     * Remove sharing of a recipe with a user.
     */
    public function unshare(Request $request, Recipe $recipe): RedirectResponse
    {
        return $this->performUnshare($request, $recipe);
    }

    // ── HasSharing implementation ─────────────────────

    protected function sharingPivotField(): string
    {
        return 'permission';
    }

    protected function sharingNotificationType(): string
    {
        return 'recipe_shared';
    }

    protected function sharingNotificationTitle(): string
    {
        return 'Rezept geteilt';
    }

    protected function sharingNotificationMessage(Model $resource): string
    {
        return auth()->user()->name.' hat das Rezept "'.$resource->title.'" mit dir geteilt.';
    }

    protected function sharingNotificationData(Model $resource): array
    {
        return ['recipe_id' => $resource->id];
    }
}
