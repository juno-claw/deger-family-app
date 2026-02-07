<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Note;
use App\Models\User;
use App\Traits\HasSharing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    use HasSharing;

    /**
     * Display a listing of notes.
     */
    public function index(): Response
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
    public function store(StoreNoteRequest $request): RedirectResponse
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
    public function show(Note $note): Response
    {
        $this->authorize('view', $note);

        $note->load(['owner', 'sharedWith']);

        $users = User::where('id', '!=', auth()->id())->get(['id', 'name', 'email']);

        return Inertia::render('notes/show', compact('note', 'users'));
    }

    /**
     * Update the specified note.
     */
    public function update(UpdateNoteRequest $request, Note $note): RedirectResponse|JsonResponse
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
    public function destroy(Note $note): RedirectResponse
    {
        $this->authorize('delete', $note);

        $note->delete();

        return redirect()->route('notes.index');
    }

    /**
     * Toggle the pinned status of a note.
     */
    public function togglePin(Note $note): RedirectResponse
    {
        $this->authorize('update', $note);

        $note->update(['is_pinned' => ! $note->is_pinned]);

        return redirect()->back();
    }

    /**
     * Share a note with another user.
     */
    public function share(Request $request, Note $note): RedirectResponse
    {
        return $this->performShare($request, $note);
    }

    /**
     * Remove sharing of a note with a user.
     */
    public function unshare(Request $request, Note $note): RedirectResponse
    {
        return $this->performUnshare($request, $note);
    }

    // ── HasSharing implementation ─────────────────────

    protected function sharingPivotField(): string
    {
        return 'permission';
    }

    protected function sharingNotificationType(): string
    {
        return 'note_shared';
    }

    protected function sharingNotificationTitle(): string
    {
        return 'Notiz geteilt';
    }

    protected function sharingNotificationMessage(Model $resource): string
    {
        return auth()->user()->name.' hat die Notiz "'.$resource->title.'" mit dir geteilt.';
    }

    protected function sharingNotificationData(Model $resource): array
    {
        return ['note_id' => $resource->id];
    }
}
