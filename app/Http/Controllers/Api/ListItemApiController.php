<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListItemRequest;
use App\Http\Resources\ListItemResource;
use App\Models\FamilyList;
use App\Models\ListItem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ListItemApiController extends Controller
{
    /**
     * Store a newly created list item.
     */
    public function store(StoreListItemRequest $request, FamilyList $list): ListItemResource
    {
        $this->authorize('update', $list);

        $item = ListItem::create([
            'list_id' => $list->id,
            'content' => $request->validated('content'),
            'position' => $list->items()->max('position') + 1,
            'created_by' => auth()->id(),
        ]);

        return new ListItemResource($item);
    }

    /**
     * Update the specified list item.
     */
    public function update(Request $request, FamilyList $list, ListItem $item): ListItemResource
    {
        $this->authorize('update', $list);

        $validated = $request->validate([
            'content' => 'sometimes|string|max:500',
            'is_completed' => 'sometimes|boolean',
        ]);

        $item->update($validated);

        return new ListItemResource($item);
    }

    /**
     * Remove the specified list item.
     */
    public function destroy(FamilyList $list, ListItem $item): Response
    {
        $this->authorize('update', $list);

        $item->delete();

        return response()->noContent();
    }
}
