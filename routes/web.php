<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AnnouncementController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/annonces', [AnnouncementController::class, 'index'])->name('announcements.index');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('profil', [ProfileController::class, 'show'])->name('profil');
    Route::put('profil', [ProfileController::class, 'update'])->name('profil.update');
    
    // Routes pour les annonces
    Route::get('annonces/create', [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('annonces', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('mes-annonces', [AnnouncementController::class, 'myAnnouncements'])->name('announcements.my');
    Route::resource('announcements', AnnouncementController::class)->except(['create', 'store']);
});

Route::get('comment-ca-marche', function () {
    return Inertia::render('comment-ca-marche');
})->name('comment-ca-marche');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
