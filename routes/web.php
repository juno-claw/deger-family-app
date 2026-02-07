<?php

use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\ListItemController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::resource('lists', ListController::class)->except(['edit']);
    Route::post('lists/{list}/share', [ListController::class, 'share'])->name('lists.share');
    Route::delete('lists/{list}/unshare', [ListController::class, 'unshare'])->name('lists.unshare');
    Route::post('lists/{list}/items', [ListItemController::class, 'store'])->name('lists.items.store');
    Route::put('lists/{list}/items/{item}', [ListItemController::class, 'update'])->name('lists.items.update');
    Route::delete('lists/{list}/items/{item}', [ListItemController::class, 'destroy'])->name('lists.items.destroy');
    Route::post('lists/{list}/items/reorder', [ListItemController::class, 'reorder'])->name('lists.items.reorder');

    Route::get('calendar', [CalendarEventController::class, 'index'])->name('calendar.index');
    Route::post('calendar/events', [CalendarEventController::class, 'store'])->name('calendar.events.store');
    Route::get('calendar/events/{event}', [CalendarEventController::class, 'show'])->name('calendar.events.show');
    Route::put('calendar/events/{event}', [CalendarEventController::class, 'update'])->name('calendar.events.update');
    Route::delete('calendar/events/{event}', [CalendarEventController::class, 'destroy'])->name('calendar.events.destroy');
    Route::post('calendar/events/{event}/share', [CalendarEventController::class, 'share'])->name('calendar.events.share');

    Route::resource('notes', NoteController::class)->except(['edit']);
    Route::patch('notes/{note}/pin', [NoteController::class, 'togglePin'])->name('notes.pin');
    Route::post('notes/{note}/share', [NoteController::class, 'share'])->name('notes.share');
    Route::delete('notes/{note}/unshare', [NoteController::class, 'unshare'])->name('notes.unshare');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');
});

require __DIR__.'/settings.php';
