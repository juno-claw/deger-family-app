# Agent 3D: Backend -- Notifications & Sharing System

Du arbeitest am Projekt "Deger Family App" unter `/var/www/html/deger-family-app`.
Laravel 12, React/Inertia, SQLite. Git Remote: `git@github.com:juno-claw/deger-family-app.git`

**ZUERST:** `cd /var/www/html/deger-family-app && git pull`

**DEINE AUFGABE:** Erstelle das Notification-System und den Sharing-Service.

Existierendes Model (Phase 2A):

- `App\Models\Notification` mit: `user()`, `fromUser()`, `scopeUnread()`

**WICHTIG:** Aendere NUR Notification/Sharing-bezogene Dateien.

---

## 1. NotificationService

Erstelle `app/Services/NotificationService.php`:

```php
<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public function notify(User $user, ?User $fromUser, string $type, string $title, string $message, array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'from_user_id' => $fromUser?->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)->unread()->count();
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->update(['read_at' => now()]);
    }

    public function markAllAsRead(User $user): void
    {
        Notification::where('user_id', $user->id)->unread()->update(['read_at' => now()]);
    }
}
```

---

## 2. NotificationController

**`app/Http/Controllers/NotificationController.php`:**

### `index()`

- `$notifications = Notification::where('user_id', auth()->id())->with('fromUser')->latest()->paginate(30)`
- `return Inertia::render('notifications/index', ['notifications' => $notifications])`

### `markAsRead(Notification $notification)`

- Pruefe: `$notification->user_id === auth()->id()`
- `app(NotificationService::class)->markAsRead($notification)`
- redirect back

### `markAllAsRead()`

- `app(NotificationService::class)->markAllAsRead(auth()->user())`
- redirect back

### `unreadCount()`

- `$count = app(NotificationService::class)->getUnreadCount(auth()->user())`
- `return response()->json(['count' => $count])`

---

## 3. Unread Count in Shared Data (WICHTIG fuer das Bell-Icon)

Bearbeite `app/Http/Middleware/HandleInertiaRequests.php`:

In der `share()` Methode, fuege hinzu:

```php
'notificationCount' => fn() => auth()->check()
    ? \App\Models\Notification::where('user_id', auth()->id())->unread()->count()
    : 0,
```

Das macht den Notification-Count auf **JEDER** Seite verfuegbar via `usePage().props`.

---

## 4. Routen

In `routes/web.php`, innerhalb auth Middleware, **ERSETZE** die `/notifications` Closure-Route:

```php
use App\Http\Controllers\NotificationController;

Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');
```

---

## 5. TypeScript Types erweitern

Bearbeite `resources/js/types/index.ts`:

Fuege zu `SharedData` hinzu:

```typescript
export type SharedData = {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    notificationCount: number;
    [key: string]: unknown;
};
```

---

## 6. Git

```bash
git add -A && git commit -m "Add notification system and shared data

- NotificationService for creating and managing notifications
- NotificationController with index, markAsRead, markAllAsRead, unreadCount
- Unread notification count shared via Inertia middleware on every page
- TypeScript types updated for notification count" && git push
```
