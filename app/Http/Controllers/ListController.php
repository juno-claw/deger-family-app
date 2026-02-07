<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListRequest;
use App\Http\Requests\UpdateListRequest;
use App\Models\FamilyList;
use App\Models\User;
use App\Traits\HasSharing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ListController extends Controller
{
    use HasSharing;

    /**
     * Display a listing of the lists.
     */
    public function index(): Response
    {
        $lists = FamilyList::accessibleBy(auth()->user())
            ->with(['owner', 'items', 'sharedWith'])
            ->latest()
            ->get();

        $ownLists = $lists->where('owner_id', auth()->id())->values();
        $sharedLists = $lists->where('owner_id', '!=', auth()->id())->values();

        return Inertia::render('lists/index', compact('ownLists', 'sharedLists'));
    }

    /**
     * Store a newly created list.
     */
    public function store(StoreListRequest $request): RedirectResponse
    {
        FamilyList::create([
            ...$request->validated(),
            'owner_id' => auth()->id(),
        ]);

        return redirect()->route('lists.index')->with('success', 'Liste erfolgreich erstellt.');
    }

    /**
     * Display the specified list.
     */
    public function show(FamilyList $list): Response
    {
        $this->authorize('view', $list);

        $list->load([
            'items' => fn ($q) => $q->orderBy('position'),
            'owner',
            'sharedWith',
        ]);

        $users = User::where('id', '!=', auth()->id())->get(['id', 'name', 'email']);

        return Inertia::render('lists/show', compact('list', 'users'));
    }

    /**
     * Update the specified list.
     */
    public function update(UpdateListRequest $request, FamilyList $list): RedirectResponse
    {
        $this->authorize('update', $list);

        $list->update($request->validated());

        return redirect()->back();
    }

    /**
     * Remove the specified list.
     */
    public function destroy(FamilyList $list): RedirectResponse
    {
        $this->authorize('delete', $list);

        $list->delete();

        return redirect()->route('lists.index');
    }

    /**
     * Share the list with another user.
     */
    public function share(Request $request, FamilyList $list): RedirectResponse
    {
        return $this->performShare($request, $list);
    }

    /**
     * Unshare the list from a user.
     */
    public function unshare(Request $request, FamilyList $list): RedirectResponse
    {
        return $this->performUnshare($request, $list);
    }

    // ── HasSharing implementation ─────────────────────

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
}
