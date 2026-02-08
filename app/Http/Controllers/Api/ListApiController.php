<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListRequest;
use App\Http\Requests\UpdateListRequest;
use App\Http\Resources\ListResource;
use App\Models\FamilyList;
use App\Traits\HasApiSharing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ListApiController extends Controller
{
    use HasApiSharing;

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

    /**
     * Share the list with another user.
     */
    public function share(Request $request, FamilyList $list): ListResource
    {
        return $this->performApiShare($request, $list);
    }

    /**
     * Remove sharing of the list from a user.
     */
    public function unshare(Request $request, FamilyList $list): ListResource
    {
        return $this->performApiUnshare($request, $list);
    }

    // ── HasApiSharing implementation ─────────────────────

    protected function sharingPivotField(): string
    {
        return 'permission';
    }

    protected function sharingNotificationType(): string
    {
        return 'list_shared';
    }

    protected function sharingNotificationTitle(): string
    {
        return 'Liste geteilt';
    }

    protected function sharingNotificationMessage(Model $resource): string
    {
        return auth()->user()->name.' hat die Liste "'.$resource->title.'" mit dir geteilt.';
    }

    protected function sharingNotificationData(Model $resource): array
    {
        return ['list_id' => $resource->id];
    }

    protected function sharingResource(Model $resource): ListResource
    {
        return new ListResource($resource);
    }
}
