<?php

use App\Http\Controllers\Api\CalendarEventApiController;
use App\Http\Controllers\Api\ListApiController;
use App\Http\Controllers\Api\ListItemApiController;
use App\Http\Controllers\Api\NoteApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\UserApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->name('api.')->group(function () {
    // Users
    Route::get('users', [UserApiController::class, 'index'])->name('users.index');
    Route::get('users/me', fn () => new \App\Http\Resources\UserResource(auth()->user()))->name('users.me');

    // Lists
    Route::apiResource('lists', ListApiController::class);
    Route::post('lists/{list}/items', [ListItemApiController::class, 'store'])->name('lists.items.store');
    Route::put('lists/{list}/items/{item}', [ListItemApiController::class, 'update'])->name('lists.items.update');
    Route::delete('lists/{list}/items/{item}', [ListItemApiController::class, 'destroy'])->name('lists.items.destroy');

    // Calendar Events
    Route::apiResource('calendar/events', CalendarEventApiController::class);

    // Notes
    Route::apiResource('notes', NoteApiController::class);

    // Notifications
    Route::get('notifications', [NotificationApiController::class, 'index'])->name('notifications.index');
    Route::post('notifications/push', [NotificationApiController::class, 'push'])->name('notifications.push');
    Route::patch('notifications/{notification}/read', [NotificationApiController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationApiController::class, 'markAllAsRead'])->name('notifications.readAll');
});
