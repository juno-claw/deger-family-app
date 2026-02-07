<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('/lists', function () {
        return Inertia::render('lists/index');
    })->name('lists.index');

    Route::get('/calendar', function () {
        return Inertia::render('calendar/index');
    })->name('calendar.index');

    Route::get('/notes', function () {
        return Inertia::render('notes/index');
    })->name('notes.index');

    Route::get('/notifications', function () {
        return Inertia::render('notifications/index');
    })->name('notifications.index');
});

require __DIR__.'/settings.php';
