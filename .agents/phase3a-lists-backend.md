# Agent 3A: Backend -- Listen API

Du arbeitest am Projekt "Deger Family App" unter `/var/www/html/deger-family-app`.
Laravel 12, React/Inertia, SQLite. Git Remote: `git@github.com:juno-claw/deger-family-app.git`

**ZUERST:** `cd /var/www/html/deger-family-app && git pull`

**DEINE AUFGABE:** Erstelle die Backend-Logik fuer Listen (Todos & Einkaufslisten).

Die folgenden Models existieren bereits (durch Phase 2A):

- `App\Models\FamilyList` (Tabelle `lists`) mit: `owner()`, `items()`, `sharedWith()`, `scopeAccessibleBy()`
- `App\Models\ListItem` mit: `familyList()`, `creator()`
- `App\Models\User` mit: `lists()`, `sharedLists()`

**WICHTIG:** Aendere NUR Dateien die mit Listen zu tun haben. Andere Controller/Seiten werden von anderen Agents erstellt.

---

## 1. Policy erstellen

Erstelle `app/Policies/FamilyListPolicy.php`:

- `viewAny($user)`: return true (jeder eingeloggte User)
- `view($user, $familyList)`: Owner oder in sharedWith
- `create($user)`: return true
- `update($user, $familyList)`: Owner oder sharedWith mit permission='edit'
- `delete($user, $familyList)`: Nur Owner
- `share($user, $familyList)`: Nur Owner

Registriere die Policy in `app/Providers/AppServiceProvider.php` (boot method):

```php
Gate::policy(FamilyList::class, FamilyListPolicy::class);
```

---

## 2. Form Requests erstellen

**`app/Http/Requests/StoreListRequest.php`:**

- `authorize()`: return true
- `rules()`: `title => required|string|max:255`, `type => required|in:todo,shopping`, `icon => nullable|string|max:50`

**`app/Http/Requests/UpdateListRequest.php`:**

- `authorize()`: return true
- `rules()`: `title => sometimes|string|max:255`, `type => sometimes|in:todo,shopping`, `icon => nullable|string|max:50`

**`app/Http/Requests/StoreListItemRequest.php`:**

- `authorize()`: return true
- `rules()`: `content => required|string|max:500`

---

## 3. ListController erstellen

**`app/Http/Controllers/ListController.php`:**

### `index()`

- `$lists = FamilyList::accessibleBy(auth()->user())->with(['owner', 'items', 'sharedWith'])->latest()->get()`
- Trenne in `ownLists` (owner_id == auth id) und `sharedLists`
- `return Inertia::render('lists/index', compact('ownLists', 'sharedLists'))`

### `store(StoreListRequest)`

- Erstelle Liste mit `owner_id = auth()->id()`
- redirect zurueck zu `lists.index` mit success flash

### `show(FamilyList $list)`

- `$this->authorize('view', $list)`
- `$list->load(['items' => fn($q) => $q->orderBy('position'), 'owner', 'sharedWith'])`
- Lade alle User fuer Share-Dialog: `$users = User::where('id', '!=', auth()->id())->get(['id', 'name', 'email'])`
- `return Inertia::render('lists/show', compact('list', 'users'))`

### `update(UpdateListRequest, FamilyList $list)`

- `$this->authorize('update', $list)`
- `$list->update($request->validated())`
- redirect back

### `destroy(FamilyList $list)`

- `$this->authorize('delete', $list)`
- `$list->delete()`
- redirect to `lists.index`

### `share(Request $request, FamilyList $list)`

- `$this->authorize('share', $list)`
- Validate: `user_id => required|exists:users,id`, `permission => required|in:view,edit`
- `$list->sharedWith()->syncWithoutDetaching([$request->user_id => ['permission' => $request->permission]])`
- Erstelle Notification fuer den Empfaenger (direkt mit `Notification::create`):

```
user_id = $request->user_id
from_user_id = auth()->id()
type = 'list_shared'
title = 'Liste geteilt'
message = auth()->user()->name . ' hat die Liste "' . $list->title . '" mit dir geteilt.'
data = ['list_id' => $list->id]
```

- redirect back

### `unshare(Request $request, FamilyList $list)`

- `$this->authorize('share', $list)`
- `$list->sharedWith()->detach($request->user_id)`
- redirect back

---

## 4. ListItemController erstellen

**`app/Http/Controllers/ListItemController.php`:**

### `store(StoreListItemRequest, FamilyList $list)`

- Autorisierung: User muss Liste bearbeiten duerfen (Owner oder edit-Permission)
- Erstelle Item mit: `list_id`, `content`, `position = $list->items()->max('position') + 1`, `created_by = auth()->id()`
- redirect back

### `update(Request $request, FamilyList $list, ListItem $item)`

- Validate: `content => sometimes|string|max:500`, `is_completed => sometimes|boolean`
- `$item->update($request->only(['content', 'is_completed']))`
- redirect back

### `destroy(FamilyList $list, ListItem $item)`

- `$item->delete()`
- redirect back

### `reorder(Request $request, FamilyList $list)`

- Validate: `items => required|array`, `items.* => integer` (Array von Item-IDs in neuer Reihenfolge)
- Foreach items: `ListItem::where('id', $id)->where('list_id', $list->id)->update(['position' => $index])`
- redirect back

---

## 5. Routen registrieren

In `routes/web.php`, innerhalb der auth Middleware-Gruppe, **ERSETZE** die bestehende Closure-Route fuer `/lists`:

```php
use App\Http\Controllers\ListController;
use App\Http\Controllers\ListItemController;

Route::resource('lists', ListController::class)->except(['edit']);
Route::post('lists/{list}/share', [ListController::class, 'share'])->name('lists.share');
Route::delete('lists/{list}/unshare', [ListController::class, 'unshare'])->name('lists.unshare');
Route::post('lists/{list}/items', [ListItemController::class, 'store'])->name('lists.items.store');
Route::put('lists/{list}/items/{item}', [ListItemController::class, 'update'])->name('lists.items.update');
Route::delete('lists/{list}/items/{item}', [ListItemController::class, 'destroy'])->name('lists.items.destroy');
Route::post('lists/{list}/items/reorder', [ListItemController::class, 'reorder'])->name('lists.items.reorder');
```

**ACHTUNG:** Da `list` ein PHP reserved keyword ist, verwende Route Model Binding mit explizitem Typ. In den Controller-Methoden den Parameter als `FamilyList $list` type-hinten und in `bootstrap/app.php` oder `AppServiceProvider::boot()`:

```php
Route::model('list', \App\Models\FamilyList::class);
```

---

## 6. Git

```bash
git add -A && git commit -m "Add lists backend: controllers, policies, and routes

- ListController with full CRUD, sharing, and unsharing
- ListItemController with create, update, delete, and reorder
- FamilyListPolicy for authorization (owner/shared access)
- Form requests for validation
- Routes registered with proper model binding" && git push
```
