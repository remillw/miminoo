<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/annonces', [AnnouncementController::class, 'index'])->name('announcements.index');

// Route API pour stocker la position utilisateur
Route::post('/api/set-user-location', function(\Illuminate\Http\Request $request) {
    $request->validate([
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
    ]);
    
    session([
        'user_latitude' => $request->latitude,
        'user_longitude' => $request->longitude,
        'location_set_at' => now(),
    ]);
    
    return response()->json(['success' => true]);
});

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('profil', [ProfileController::class, 'show'])->name('profil');
    Route::put('profil', [ProfileController::class, 'update'])->name('profil.update');
    
    // Routes pour les annonces avec /annonces
    Route::get('annonces/create', [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('annonces', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('mes-annonces', [AnnouncementController::class, 'myAnnouncements'])->name('announcements.my');
    Route::post('annonces/{announcement}/apply', [AnnouncementController::class, 'apply'])->name('announcements.apply');
    Route::get('annonces/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');
    Route::get('annonces/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::put('annonces/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('annonces/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
});

Route::get('comment-ca-marche', function () {
    return Inertia::render('comment-ca-marche');
})->name('comment-ca-marche');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
