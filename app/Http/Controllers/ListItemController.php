<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListItemRequest;
use App\Models\FamilyList;
use App\Models\ListItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ListItemController extends Controller
{
    /**
     * Store a newly created list item.
     */
    public function store(StoreListItemRequest $request, FamilyList $list): RedirectResponse
    {
        $this->authorize('update', $list);

        $maxPosition = $list->items()->lockForUpdate()->max('position') ?? -1;

        ListItem::create([
            'list_id' => $list->id,
            'content' => $request->validated('content'),
            'position' => $maxPosition + 1,
            'created_by' => auth()->id(),
        ]);

        return redirect()->back();
    }

    /**
     * Update the specified list item.
     */
    public function update(Request $request, FamilyList $list, ListItem $item): RedirectResponse
    {
        $this->authorize('update', $list);

        $request->validate([
            'content' => 'sometimes|string|max:500',
            'is_completed' => 'sometimes|boolean',
        ]);

        $item->update($request->only(['content', 'is_completed']));

        return redirect()->back();
    }

    /**
     * Remove the specified list item.
     */
    public function destroy(FamilyList $list, ListItem $item): RedirectResponse
    {
        $this->authorize('update', $list);

        $item->delete();

        return redirect()->back();
    }

    /**
     * Reorder list items.
     */
    public function reorder(Request $request, FamilyList $list): RedirectResponse
    {
        $this->authorize('update', $list);

        $request->validate([
            'items' => 'required|array',
            'items.*' => 'integer',
        ]);

        foreach ($request->items as $index => $id) {
            ListItem::where('id', $id)
                ->where('list_id', $list->id)
                ->update(['position' => $index]);
        }

        return redirect()->back();
    }
}
