# Agent 3B: Backend -- Kalender API

Du arbeitest am Projekt "Deger Family App" unter `/var/www/html/deger-family-app`.
Laravel 12, React/Inertia, SQLite. Git Remote: `git@github.com:juno-claw/deger-family-app.git`

**ZUERST:** `cd /var/www/html/deger-family-app && git pull`

**DEINE AUFGABE:** Erstelle die Backend-Logik fuer Kalender-Events.

Existierende Models (Phase 2A):

- `App\Models\CalendarEvent` mit: `owner()`, `sharedWith()`, `scopeAccessibleBy()`
- `App\Models\User` mit: `calendarEvents()`, `sharedCalendarEvents()`

**WICHTIG:** Aendere NUR Kalender-bezogene Dateien. Andere Features werden von anderen Agents bearbeitet.

---

## 1. Policy

Erstelle `app/Policies/CalendarEventPolicy.php`:

- `viewAny($user)`: true
- `view($user, $event)`: Owner oder in sharedWith
- `create($user)`: true
- `update($user, $event)`: Owner oder sharedWith mit status='accepted'
- `delete($user, $event)`: Nur Owner
- `share($user, $event)`: Nur Owner

Registriere in `AppServiceProvider` (boot):

```php
Gate::policy(CalendarEvent::class, CalendarEventPolicy::class);
```

---

## 2. Form Requests

**`app/Http/Requests/StoreCalendarEventRequest.php`:**

- `rules()`:
  - `title => required|string|max:255`
  - `description => nullable|string|max:2000`
  - `start_at => required|date`
  - `end_at => nullable|date|after_or_equal:start_at`
  - `all_day => boolean`
  - `recurrence => in:none,daily,weekly,monthly,yearly`
  - `color => nullable|string|max:7`

**`app/Http/Requests/UpdateCalendarEventRequest.php`:**

- Gleiche Rules aber alle `sometimes` statt `required`

---

## 3. CalendarEventController

**`app/Http/Controllers/CalendarEventController.php`:**

### `index(Request $request)`

- Query-Parameter: `month` (default: aktueller Monat), `year` (default: aktuelles Jahr)
- Lade alle Events die im angegebenen Monat liegen (start_at oder end_at im Monat)
- Filtere mit `accessibleBy(auth()->user())`
- `$events->load(['owner', 'sharedWith'])`
- `$users = User::where('id', '!=', auth()->id())->get(['id', 'name', 'email'])`
- Farbcodierung: Wenn `event->color` null, setze Default basierend auf Owner:
  - Olli (id=1) = `#3b82f6` (blau)
  - Sabsy (id=2) = `#ec4899` (pink)
  - Juno (id=3) = `#22c55e` (gruen)
- `return Inertia::render('calendar/index', compact('events', 'users', 'month', 'year'))`

### `store(StoreCalendarEventRequest)`

- Erstelle mit `owner_id = auth()->id()`
- redirect back

### `show(CalendarEvent $event)`

- `$this->authorize('view', $event)`
- `$event->load(['owner', 'sharedWith'])`
- `return Inertia::render('calendar/show', compact('event'))`

### `update(UpdateCalendarEventRequest, CalendarEvent $event)`

- `$this->authorize('update', $event)`
- `$event->update($request->validated())`
- redirect back

### `destroy(CalendarEvent $event)`

- `$this->authorize('delete', $event)`
- `$event->delete()`
- redirect back

### `share(Request $request, CalendarEvent $event)`

- `$this->authorize('share', $event)`
- Validate: `user_id => required|exists:users,id`
- `$event->sharedWith()->syncWithoutDetaching([$request->user_id => ['status' => 'pending']])`
- Erstelle Notification:

```
user_id = $request->user_id
from_user_id = auth()->id()
type = 'event_shared'
title = 'Kalender-Einladung'
message = auth()->user()->name . ' hat dich zu "' . $event->title . '" eingeladen.'
data = ['event_id' => $event->id]
```

- redirect back

---

## 4. Routen

In `routes/web.php`, innerhalb auth Middleware, **ERSETZE** die `/calendar` Closure-Route:

```php
use App\Http\Controllers\CalendarEventController;

Route::get('calendar', [CalendarEventController::class, 'index'])->name('calendar.index');
Route::post('calendar/events', [CalendarEventController::class, 'store'])->name('calendar.events.store');
Route::get('calendar/events/{event}', [CalendarEventController::class, 'show'])->name('calendar.events.show');
Route::put('calendar/events/{event}', [CalendarEventController::class, 'update'])->name('calendar.events.update');
Route::delete('calendar/events/{event}', [CalendarEventController::class, 'destroy'])->name('calendar.events.destroy');
Route::post('calendar/events/{event}/share', [CalendarEventController::class, 'share'])->name('calendar.events.share');
```

---

## 5. Git

```bash
git add -A && git commit -m "Add calendar backend: controller, policy, and routes

- CalendarEventController with CRUD and sharing
- CalendarEventPolicy for authorization
- Month/year filtering for calendar view
- Default color coding per user (Olli=blue, Sabsy=pink, Juno=green)
- Event sharing with notification creation" && git push
```
