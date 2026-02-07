# Agent 3C: Backend -- Notizen API

Du arbeitest am Projekt "Deger Family App" unter `/var/www/html/deger-family-app`.
Laravel 12, React/Inertia, SQLite. Git Remote: `git@github.com:juno-claw/deger-family-app.git`

**ZUERST:** `cd /var/www/html/deger-family-app && git pull`

**DEINE AUFGABE:** Erstelle die Backend-Logik fuer Notizen.

Existierende Models (Phase 2A):

- `App\Models\Note` mit: `owner()`, `sharedWith()`, `scopeAccessibleBy()`
- `App\Models\User` mit: `notes()`, `sharedNotes()`

**WICHTIG:** Aendere NUR Notizen-bezogene Dateien.

---

## 1. Policy

Erstelle `app/Policies/NotePolicy.php`:

- `viewAny($user)`: true
- `view($user, $note)`: Owner oder in sharedWith
- `create($user)`: true
- `update($user, $note)`: Owner oder sharedWith mit permission='edit'
- `delete($user, $note)`: Nur Owner
- `share($user, $note)`: Nur Owner

Registriere in `AppServiceProvider` (boot):

```php
Gate::policy(Note::class, NotePolicy::class);
```

---

## 2. Form Requests

**`app/Http/Requests/StoreNoteRequest.php`:**

- `rules()`: `title => required|string|max:255`, `content => nullable|string`, `color => nullable|string|max:7`

**`app/Http/Requests/UpdateNoteRequest.php`:**

- `rules()`: `title => sometimes|string|max:255`, `content => nullable|string`, `color => nullable|string|max:7`, `is_pinned => sometimes|boolean`

---

## 3. NoteController

**`app/Http/Controllers/NoteController.php`:**

### `index()`

- `$notes = Note::accessibleBy(auth()->user())->with(['owner', 'sharedWith'])->latest()->get()`
- Sortiere: Pinned zuerst, dann nach `updated_at` desc
- Trenne in `ownNotes` und `sharedNotes`
- `$users = User::where('id', '!=', auth()->id())->get(['id', 'name', 'email'])`
- `return Inertia::render('notes/index', compact('ownNotes', 'sharedNotes', 'users'))`

### `store(StoreNoteRequest)`

- Erstelle mit `owner_id = auth()->id()`
- redirect to `notes.show` mit der neuen Note

### `show(Note $note)`

- `$this->authorize('view', $note)`
- `$note->load(['owner', 'sharedWith'])`
- `$users = User::where('id', '!=', auth()->id())->get(['id', 'name', 'email'])`
- `return Inertia::render('notes/show', compact('note', 'users'))`

### `update(UpdateNoteRequest, Note $note)`

- `$this->authorize('update', $note)`
- `$note->update($request->validated())`
- Wenn Request von Inertia kommt: redirect back
- Sonst (fuer Autosave via fetch): `return response()->json(['success' => true])`

### `destroy(Note $note)`

- `$this->authorize('delete', $note)`
- `$note->delete()`
- redirect to `notes.index`

### `togglePin(Note $note)`

- `$this->authorize('update', $note)`
- `$note->update(['is_pinned' => !$note->is_pinned])`
- redirect back

### `share(Request $request, Note $note)`

- `$this->authorize('share', $note)`
- Validate: `user_id => required|exists:users,id`, `permission => required|in:view,edit`
- `$note->sharedWith()->syncWithoutDetaching([$request->user_id => ['permission' => $request->permission]])`
- Erstelle Notification:

```
user_id = $request->user_id
from_user_id = auth()->id()
type = 'note_shared'
title = 'Notiz geteilt'
message = auth()->user()->name . ' hat die Notiz "' . $note->title . '" mit dir geteilt.'
data = ['note_id' => $note->id]
```

- redirect back

### `unshare(Request $request, Note $note)`

- `$this->authorize('share', $note)`
- `$note->sharedWith()->detach($request->user_id)`
- redirect back

---

## 4. Routen

In `routes/web.php`, innerhalb auth Middleware, **ERSETZE** die `/notes` Closure-Route:

```php
use App\Http\Controllers\NoteController;

Route::resource('notes', NoteController::class)->except(['edit']);
Route::patch('notes/{note}/pin', [NoteController::class, 'togglePin'])->name('notes.pin');
Route::post('notes/{note}/share', [NoteController::class, 'share'])->name('notes.share');
Route::delete('notes/{note}/unshare', [NoteController::class, 'unshare'])->name('notes.unshare');
```

---

## 5. Git

```bash
git add -A && git commit -m "Add notes backend: controller, policy, and routes

- NoteController with full CRUD, pin toggle, sharing
- NotePolicy for authorization (owner/shared access)
- Autosave support via JSON response for non-Inertia requests
- Pinned notes sorted first
- Note sharing with notification creation" && git push
```
