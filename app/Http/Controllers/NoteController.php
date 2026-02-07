<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Note;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NoteController extends Controller
{
    /**
     * Display a listing of notes.
     */
    public function index()
    {
        $notes = Note::accessibleBy(auth()->user())
            ->with(['owner', 'sharedWith'])
            ->latest()
            ->get();

        // Sort: pinned first, then by updated_at desc
        $notes = $notes->sortByDesc('updated_at')->sortByDesc('is_pinned')->values();

        $ownNotes = $notes->where('owner_id', auth()->id())->values();
        $sharedNotes = $notes->where('owner_id', '!=', auth()->id())->values();

        $users = User::where('id', '!=', auth()->id())->get(['id', 'name', 'email']);

        return Inertia::render('notes/index', compact('ownNotes', 'sharedNotes', 'users'));
    }

    /**
     * Store a newly created note.
     */
    public function store(StoreNoteRequest $request)
    {
        $note = Note::create([
            ...$request->validated(),
            'owner_id' => auth()->id(),
        ]);

        return redirect()->route('notes.show', $note);
    }

    /**
     * Display the specified note.
     */
    public function show(Note $note)
    {
        $this->authorize('view', $note);

        $note->load(['owner', 'sharedWith']);

        $users = User::where('id', '!=', auth()->id())->get(['id', 'name', 'email']);

        return Inertia::render('notes/show', compact('note', 'users'));
    }

    /**
     * Update the specified note.
     */
    public function update(UpdateNoteRequest $request, Note $note)
    {
        $this->authorize('update', $note);

        $note->update($request->validated());

        // For autosave via fetch (non-Inertia requests)
        if (! $request->header('X-Inertia')) {
            return response()->json(['success' => true]);
        }

        return redirect()->back();
    }

    /**
     * Remove the specified note.
     */
    public function destroy(Note $note)
    {
        $this->authorize('delete', $note);

        $note->delete();

        return redirect()->route('notes.index');
    }

    /**
     * Toggle the pinned status of a note.
     */
    public function togglePin(Note $note)
    {
        $this->authorize('update', $note);

        $note->update(['is_pinned' => ! $note->is_pinned]);

        return redirect()->back();
    }

    /**
     * Share a note with another user.
     */
    public function share(Request $request, Note $note)
    {
        $this->authorize('share', $note);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission' => 'required|in:view,edit',
        ]);

        $note->sharedWith()->syncWithoutDetaching([
            $request->user_id => ['permission' => $request->permission],
        ]);

        Notification::create([
            'user_id' => $request->user_id,
            'from_user_id' => auth()->id(),
            'type' => 'note_shared',
            'title' => 'Notiz geteilt',
            'message' => auth()->user()->name . ' hat die Notiz "' . $note->title . '" mit dir geteilt.',
            'data' => ['note_id' => $note->id],
        ]);

        return redirect()->back();
    }

    /**
     * Remove sharing of a note with a user.
     */
    public function unshare(Request $request, Note $note)
    {
        $this->authorize('share', $note);

        $note->sharedWith()->detach($request->user_id);

        return redirect()->back();
    }
}
