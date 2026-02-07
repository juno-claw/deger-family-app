<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Http\Resources\NoteResource;
use App\Models\Note;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class NoteApiController extends Controller
{
    /**
     * Display a listing of notes accessible by the authenticated user.
     */
    public function index(): AnonymousResourceCollection
    {
        $notes = Note::accessibleBy(auth()->user())
            ->with(['owner', 'sharedWith'])
            ->latest()
            ->get();

        return NoteResource::collection($notes);
    }

    /**
     * Store a newly created note.
     */
    public function store(StoreNoteRequest $request): NoteResource
    {
        $note = Note::create([
            ...$request->validated(),
            'owner_id' => auth()->id(),
        ]);

        $note->load(['owner', 'sharedWith']);

        return new NoteResource($note);
    }

    /**
     * Display the specified note.
     */
    public function show(Note $note): NoteResource
    {
        $this->authorize('view', $note);

        $note->load(['owner', 'sharedWith']);

        return new NoteResource($note);
    }

    /**
     * Update the specified note.
     */
    public function update(UpdateNoteRequest $request, Note $note): NoteResource
    {
        $this->authorize('update', $note);

        $note->update($request->validated());

        $note->load(['owner', 'sharedWith']);

        return new NoteResource($note);
    }

    /**
     * Remove the specified note.
     */
    public function destroy(Note $note): Response
    {
        $this->authorize('delete', $note);

        $note->delete();

        return response()->noContent();
    }
}
