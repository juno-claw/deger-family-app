<?php

use App\Http\Controllers\Api\CalendarEventApiController;
use App\Http\Controllers\Api\GoogleCalendarApiController;
use App\Http\Controllers\Api\ListApiController;
use App\Http\Controllers\Api\ListItemApiController;
use App\Http\Controllers\Api\NoteApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\RecipeApiController;
use App\Http\Controllers\Api\ShortcutApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Middleware\EnsureLocalhost;
use Illuminate\Support\Facades\Route;

// Telegram Shortcuts (localhost only, no auth)
Route::prefix('v1/shortcuts')->middleware(EnsureLocalhost::class)->group(function () {
    Route::get('einkauf', [ShortcutApiController::class, 'einkauf'])->name('shortcuts.einkauf');
    Route::get('todo', [ShortcutApiController::class, 'todo'])->name('shortcuts.todo');
    Route::get('kalender', [ShortcutApiController::class, 'kalender'])->name('shortcuts.kalender');
    Route::get('notizen', [ShortcutApiController::class, 'notizen'])->name('shortcuts.notizen');
    Route::get('rezepte', [ShortcutApiController::class, 'rezepte'])->name('shortcuts.rezepte');
});

Route::prefix('v1')->middleware('auth:sanctum')->name('api.')->group(function () {
    // Users
    Route::get('users', [UserApiController::class, 'index'])->name('users.index');
    Route::get('users/me', fn () => new \App\Http\Resources\UserResource(auth()->user()))->name('users.me');

    // Lists
    Route::apiResource('lists', ListApiController::class);
    Route::post('lists/{list}/share', [ListApiController::class, 'share'])->name('lists.share');
    Route::delete('lists/{list}/share', [ListApiController::class, 'unshare'])->name('lists.unshare');
    Route::post('lists/{list}/items', [ListItemApiController::class, 'store'])->name('lists.items.store');
    Route::put('lists/{list}/items/{item}', [ListItemApiController::class, 'update'])->name('lists.items.update');
    Route::delete('lists/{list}/items/{item}', [ListItemApiController::class, 'destroy'])->name('lists.items.destroy');

    // Calendar Events
    Route::apiResource('calendar/events', CalendarEventApiController::class);
    Route::post('calendar/events/{event}/share', [CalendarEventApiController::class, 'share'])->name('events.share');
    Route::delete('calendar/events/{event}/share', [CalendarEventApiController::class, 'unshare'])->name('events.unshare');

    // Notes
    Route::apiResource('notes', NoteApiController::class);
    Route::post('notes/{note}/share', [NoteApiController::class, 'share'])->name('notes.share');
    Route::delete('notes/{note}/share', [NoteApiController::class, 'unshare'])->name('notes.unshare');

    // Recipes
    Route::apiResource('recipes', RecipeApiController::class);
    Route::post('recipes/{recipe}/share', [RecipeApiController::class, 'share'])->name('recipes.share');
    Route::delete('recipes/{recipe}/share', [RecipeApiController::class, 'unshare'])->name('recipes.unshare');

    // Notifications
    Route::get('notifications', [NotificationApiController::class, 'index'])->name('notifications.index');
    Route::post('notifications/push', [NotificationApiController::class, 'push'])->name('notifications.push');
    Route::patch('notifications/{notification}/read', [NotificationApiController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationApiController::class, 'markAllAsRead'])->name('notifications.readAll');

    // Google Calendar
    Route::get('google-calendar/status', [GoogleCalendarApiController::class, 'status'])->name('google-calendar.status');
    Route::post('google-calendar/connect', [GoogleCalendarApiController::class, 'connect'])->name('google-calendar.connect');
    Route::delete('google-calendar/disconnect', [GoogleCalendarApiController::class, 'disconnect'])->name('google-calendar.disconnect');
    Route::patch('google-calendar/toggle', [GoogleCalendarApiController::class, 'toggle'])->name('google-calendar.toggle');
});
