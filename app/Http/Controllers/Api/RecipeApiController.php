<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecipeRequest;
use App\Http\Requests\UpdateRecipeRequest;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\Traits\HasApiSharing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RecipeApiController extends Controller
{
    use HasApiSharing;

    /**
     * Display a listing of recipes accessible by the authenticated user.
     */
    public function index(): AnonymousResourceCollection
    {
        $recipes = Recipe::accessibleBy(auth()->user())
            ->with(['owner', 'sharedWith'])
            ->latest()
            ->get();

        return RecipeResource::collection($recipes);
    }

    /**
     * Store a newly created recipe.
     */
    public function store(StoreRecipeRequest $request): RecipeResource
    {
        $recipe = Recipe::create([
            ...$request->validated(),
            'owner_id' => auth()->id(),
        ]);

        $recipe->load(['owner', 'sharedWith']);

        return new RecipeResource($recipe);
    }

    /**
     * Display the specified recipe.
     */
    public function show(Recipe $recipe): RecipeResource
    {
        $this->authorize('view', $recipe);

        $recipe->load(['owner', 'sharedWith']);

        return new RecipeResource($recipe);
    }

    /**
     * Update the specified recipe.
     */
    public function update(UpdateRecipeRequest $request, Recipe $recipe): RecipeResource
    {
        $this->authorize('update', $recipe);

        $recipe->update($request->validated());

        $recipe->load(['owner', 'sharedWith']);

        return new RecipeResource($recipe);
    }

    /**
     * Remove the specified recipe.
     */
    public function destroy(Recipe $recipe): Response
    {
        $this->authorize('delete', $recipe);

        $recipe->delete();

        return response()->noContent();
    }

    /**
     * Share the recipe with another user.
     */
    public function share(Request $request, Recipe $recipe): RecipeResource
    {
        return $this->performApiShare($request, $recipe);
    }

    /**
     * Remove sharing of the recipe from a user.
     */
    public function unshare(Request $request, Recipe $recipe): RecipeResource
    {
        return $this->performApiUnshare($request, $recipe);
    }

    // ── HasApiSharing implementation ─────────────────────

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

    protected function sharingResource(Model $resource): RecipeResource
    {
        return new RecipeResource($resource);
    }
}
