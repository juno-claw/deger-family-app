# Agent 5: REST API fuer Juno

Du arbeitest am Projekt "Deger Family App" unter `/var/www/html/deger-family-app`.
Laravel 12, React/Inertia, SQLite, Sanctum. Git: `git@github.com:juno-claw/deger-family-app.git`

**ZUERST:** `cd /var/www/html/deger-family-app && git pull`

**DEINE AUFGABE:** Erstelle die REST API (JSON) fuer den AI Agent "Juno".
Juno authentifiziert sich via Sanctum Bearer Token (`Authorization: Bearer {token}`).

Alle Models und Web-Controller existieren bereits.

---

## 1. API Resource Classes

### `app/Http/Resources/UserResource.php`

- Felder: `id`, `name`, `email`, `role`

### `app/Http/Resources/ListResource.php`

- Felder: `id`, `title`, `type`, `icon`, `owner` (UserResource), `items` (ListItemResource collection wenn geladen), `shared_with`, `items_count`, `completed_count`, `created_at`, `updated_at`

### `app/Http/Resources/ListItemResource.php`

- Felder: `id`, `content`, `is_completed`, `position`, `created_by`, `creator` (UserResource when loaded)

### `app/Http/Resources/CalendarEventResource.php`

- Felder: `id`, `title`, `description`, `start_at`, `end_at`, `all_day`, `recurrence`, `color`, `owner`, `shared_with`, `created_at`

### `app/Http/Resources/NoteResource.php`

- Felder: `id`, `title`, `content`, `is_pinned`, `color`, `owner`, `shared_with`, `created_at`, `updated_at`

### `app/Http/Resources/NotificationResource.php`

- Felder: `id`, `type`, `title`, `message`, `data`, `read_at`, `from_user`, `created_at`

---

## 2. API Controllers

Alle unter `App\Http\Controllers\Api` Namespace.

### `app/Http/Controllers/Api/UserApiController.php`

- `index()`: Alle User als UserResource::collection

### `app/Http/Controllers/Api/ListApiController.php`

- `index()`: Listen des authentifizierten Users (accessible) als `ListResource::collection`
  - Lade mit `['owner', 'items', 'sharedWith']`
- `store(StoreListRequest)`: Erstelle + return `new ListResource`
- `show(FamilyList $list)`: Autorisiere + return `new ListResource` (mit items loaded)
- `update(UpdateListRequest, FamilyList $list)`: Autorisiere + Update + return
- `destroy(FamilyList $list)`: Autorisiere + Delete + return 204

### `app/Http/Controllers/Api/ListItemApiController.php`

- `store(StoreListItemRequest, FamilyList $list)`: Erstelle Item + return `new ListItemResource`
- `update(Request $request, FamilyList $list, ListItem $item)`: Validiere + Update + return
- `destroy(FamilyList $list, ListItem $item)`: Delete + return 204

### `app/Http/Controllers/Api/CalendarEventApiController.php`

- `index(Request $request)`: Events filtern nach `month`/`year` oder `date_from`/`date_to`
  - Wenn `date_from` und `date_to` vorhanden: Events in diesem Zeitraum
  - Sonst: `month` und `year` Parameter (Default: aktueller Monat)
  - Lade mit `['owner', 'sharedWith']`
- `store(StoreCalendarEventRequest)`: Erstelle + return
- `show(CalendarEvent $event)`: Autorisiere + return
- `update(UpdateCalendarEventRequest, CalendarEvent $event)`: Autorisiere + Update + return
- `destroy(CalendarEvent $event)`: Autorisiere + Delete + return 204

### `app/Http/Controllers/Api/NoteApiController.php`

- `index()`: Notizen accessible by User, mit `['owner', 'sharedWith']`
- `store(StoreNoteRequest)`: Erstelle + return
- `show(Note $note)`: Autorisiere + return
- `update(UpdateNoteRequest, Note $note)`: Autorisiere + Update + return
- `destroy(Note $note)`: Autorisiere + Delete + return 204

### `app/Http/Controllers/Api/NotificationApiController.php`

- `index()`: Notifications des Users als `NotificationResource::collection`, paginiert (30 pro Seite)
- `push(Request $request)`: Erstelle Notification fuer einen anderen User
  - Validate: `user_id => required|exists:users,id`, `title => required|string|max:255`, `message => required|string|max:2000`, `type => sometimes|string|max:50`, `data => sometimes|array`
  - Erstelle Notification mit `from_user_id = auth()->id()`
  - Return neue NotificationResource, Status 201
- `markAsRead(Notification $notification)`: Pruefe `user_id`, markiere + return 200
- `markAllAsRead()`: Markiere alle des Users + return 200

---

## 3. API Routen

**`routes/api.php`:**

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\ListApiController;
use App\Http\Controllers\Api\ListItemApiController;
use App\Http\Controllers\Api\CalendarEventApiController;
use App\Http\Controllers\Api\NoteApiController;
use App\Http\Controllers\Api\NotificationApiController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Users
    Route::get('users', [UserApiController::class, 'index']);
    Route::get('users/me', fn() => new \App\Http\Resources\UserResource(auth()->user()));

    // Lists
    Route::apiResource('lists', ListApiController::class);
    Route::post('lists/{list}/items', [ListItemApiController::class, 'store']);
    Route::put('lists/{list}/items/{item}', [ListItemApiController::class, 'update']);
    Route::delete('lists/{list}/items/{item}', [ListItemApiController::class, 'destroy']);

    // Calendar Events
    Route::apiResource('calendar/events', CalendarEventApiController::class);

    // Notes
    Route::apiResource('notes', NoteApiController::class);

    // Notifications
    Route::get('notifications', [NotificationApiController::class, 'index']);
    Route::post('notifications/push', [NotificationApiController::class, 'push']);
    Route::patch('notifications/{notification}/read', [NotificationApiController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationApiController::class, 'markAllAsRead']);
});
```

---

## 4. Route Model Binding fuer API

Stelle sicher dass `FamilyList` korrekt als `list` gebunden wird fuer API-Routen.

In `bootstrap/app.php` oder `AppServiceProvider::boot()`:

```php
Route::model('list', \App\Models\FamilyList::class);
```

Falls dies bereits durch einen anderen Agent eingerichtet wurde, pruefe ob es funktioniert.

---

## 5. Teste die API

Fuehre einen schnellen Test durch:

```bash
# Hole den Juno Token
php artisan tinker --execute="echo App\Models\User::where('email', 'juno@deger.family')->first()->tokens->first()->id;"

# Oder erstelle einen neuen Token falls noetig
php artisan tinker --execute="\$u = App\Models\User::where('email', 'juno@deger.family')->first(); echo \$u->createToken('juno-api')->plainTextToken;"
```

Teste mit curl (ersetze TOKEN):

```bash
curl -H "Authorization: Bearer TOKEN" -H "Accept: application/json" http://localhost:8000/api/v1/users
curl -H "Authorization: Bearer TOKEN" -H "Accept: application/json" http://localhost:8000/api/v1/lists
curl -H "Authorization: Bearer TOKEN" -H "Accept: application/json" http://localhost:8000/api/v1/users/me
```

---

## 6. Git

```bash
git add -A && git commit -m "Add REST API v1 for Juno AI agent

- API resource classes for all models
- API controllers for lists, calendar events, notes, notifications
- Push notification endpoint for Juno to notify family members
- All endpoints under /api/v1/ with Sanctum authentication
- User listing and self-identification endpoints" && git push
```
