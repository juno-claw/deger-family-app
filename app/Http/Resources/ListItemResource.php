<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListItemResource extends JsonResource
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
            'content' => $this->content,
            'is_completed' => $this->is_completed,
            'position' => $this->position,
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
        ];
    }
}
