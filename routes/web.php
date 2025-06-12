<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessagingController;
use Illuminate\Support\Facades\Broadcast;

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
    
    // Routes pour la messagerie
    Route::get('messagerie', [MessagingController::class, 'index'])->name('messaging.index');
    Route::patch('candidatures/{application}/mark-viewed', [MessagingController::class, 'markApplicationAsViewed'])->name('applications.mark-viewed');
    Route::post('candidatures/{application}/reserve', [MessagingController::class, 'reserveApplication'])->name('applications.reserve');
    Route::post('candidatures/{application}/decline', [MessagingController::class, 'declineApplication'])->name('applications.decline');
    Route::post('candidatures/{application}/counter-offer', [MessagingController::class, 'counterOffer'])->name('applications.counter-offer');
    Route::post('candidatures/{application}/babysitter-counter', [MessagingController::class, 'babysitterCounterOffer'])->name('applications.babysitter-counter');
    Route::post('candidatures/{application}/respond-counter', [MessagingController::class, 'respondToCounterOffer'])->name('applications.respond-counter');
    
    // Routes pour le chat temps réel
    Route::post('conversations/{conversation}/messages', [MessagingController::class, 'sendMessage'])->name('conversations.send-message');
    Route::post('conversations/{conversation}/typing', [MessagingController::class, 'userTyping'])->name('conversations.typing');
    Route::get('conversations/{conversation}/messages', [MessagingController::class, 'getMessages'])->name('conversations.messages');
    Route::patch('conversations/{conversation}/messages/{message}/read', [MessagingController::class, 'markMessageAsRead'])->name('conversations.mark-message-read');
    Route::patch('conversations/{conversation}/archive', [MessagingController::class, 'archiveConversation'])->name('conversations.archive');
});

Route::get('comment-ca-marche', function () {
    return Inertia::render('comment-ca-marche');
})->name('comment-ca-marche');  

Route::get('babysitter-profile', function () {
    return Inertia::render('Babysitterprofile');
})->name('babysitter-profile');



require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/channels.php';

// Support pour le broadcasting (authentification WebSocket)
Broadcast::routes(['middleware' => ['auth']]);

// Route fallback pour les 404 - DOIT être en dernier
Route::fallback([App\Http\Controllers\ErrorController::class, 'notFound'])->name('errors.404');
