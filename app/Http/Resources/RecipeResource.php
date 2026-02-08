<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'servings' => $this->servings,
            'prep_time' => $this->prep_time,
            'cook_time' => $this->cook_time,
            'ingredients' => $this->ingredients,
            'instructions' => $this->instructions,
            'is_favorite' => $this->is_favorite,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'shared_with' => UserResource::collection($this->whenLoaded('sharedWith')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
