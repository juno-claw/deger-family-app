<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListResource extends JsonResource
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
            'type' => $this->type,
            'icon' => $this->icon,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'items' => ListItemResource::collection($this->whenLoaded('items')),
            'shared_with' => UserResource::collection($this->whenLoaded('sharedWith')),
            'items_count' => $this->whenCounted('items', $this->items_count),
            'completed_count' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->where('is_completed', true)->count()
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
