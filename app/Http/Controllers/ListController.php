<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListRequest;
use App\Http\Requests\UpdateListRequest;
use App\Models\FamilyList;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ListController extends Controller
{
    /**
     * Display a listing of the lists.
     */
    public function index()
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
    public function store(StoreListRequest $request)
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
    public function show(FamilyList $list)
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
    public function update(UpdateListRequest $request, FamilyList $list)
    {
        $this->authorize('update', $list);

        $list->update($request->validated());

        return redirect()->back();
    }

    /**
     * Remove the specified list.
     */
    public function destroy(FamilyList $list)
    {
        $this->authorize('delete', $list);

        $list->delete();

        return redirect()->route('lists.index');
    }

    /**
     * Share the list with another user.
     */
    public function share(Request $request, FamilyList $list)
    {
        $this->authorize('share', $list);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission' => 'required|in:view,edit',
        ]);

        $list->sharedWith()->syncWithoutDetaching([
            $request->user_id => ['permission' => $request->permission],
        ]);

        Notification::create([
            'user_id' => $request->user_id,
            'from_user_id' => auth()->id(),
            'type' => 'list_shared',
            'title' => 'Liste geteilt',
            'message' => auth()->user()->name . ' hat die Liste "' . $list->title . '" mit dir geteilt.',
            'data' => ['list_id' => $list->id],
        ]);

        return redirect()->back();
    }

    /**
     * Unshare the list from a user.
     */
    public function unshare(Request $request, FamilyList $list)
    {
        $this->authorize('share', $list);

        $list->sharedWith()->detach($request->user_id);

        return redirect()->back();
    }
}
