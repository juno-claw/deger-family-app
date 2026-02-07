<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListRequest;
use App\Http\Requests\UpdateListRequest;
use App\Http\Resources\ListResource;
use App\Models\FamilyList;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ListApiController extends Controller
{
    /**
     * Display a listing of lists accessible by the authenticated user.
     */
    public function index(): AnonymousResourceCollection
    {
        $lists = FamilyList::accessibleBy(auth()->user())
            ->with(['owner', 'items', 'sharedWith'])
            ->latest()
            ->get();

        return ListResource::collection($lists);
    }

    /**
     * Store a newly created list.
     */
    public function store(StoreListRequest $request): ListResource
    {
        $list = FamilyList::create([
            ...$request->validated(),
            'owner_id' => auth()->id(),
        ]);

        $list->load(['owner', 'items', 'sharedWith']);

        return new ListResource($list);
    }

    /**
     * Display the specified list.
     */
    public function show(FamilyList $list): ListResource
    {
        $this->authorize('view', $list);

        $list->load([
            'items' => fn ($q) => $q->orderBy('position'),
            'owner',
            'sharedWith',
        ]);

        return new ListResource($list);
    }

    /**
     * Update the specified list.
     */
    public function update(UpdateListRequest $request, FamilyList $list): ListResource
    {
        $this->authorize('update', $list);

        $list->update($request->validated());

        $list->load(['owner', 'items', 'sharedWith']);

        return new ListResource($list);
    }

    /**
     * Remove the specified list.
     */
    public function destroy(FamilyList $list): Response
    {
        $this->authorize('delete', $list);

        $list->delete();

        return response()->noContent();
    }
}
