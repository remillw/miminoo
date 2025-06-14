<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\BabysitterController;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\StripeVerificationController;

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

    // Routes Stripe Identity pour vérification d'identité
    Route::prefix('babysitter')->name('babysitter.')->group(function () {
        Route::get('/identity-verification', [App\Http\Controllers\StripeIdentityController::class, 'index'])->name('identity-verification');
        Route::post('/identity-verification/create-session', [App\Http\Controllers\StripeIdentityController::class, 'createSession'])->name('identity.create-session');
        Route::post('/identity-verification/verify-and-link', [App\Http\Controllers\StripeIdentityController::class, 'verifyAndLink'])->name('identity.verify-and-link');
        Route::get('/identity-verification/status', [App\Http\Controllers\StripeIdentityController::class, 'getStatus'])->name('identity.status');
        Route::get('/identity-verification/success', [App\Http\Controllers\StripeIdentityController::class, 'success'])->name('identity.success');
        Route::get('/identity-verification/failure', [App\Http\Controllers\StripeIdentityController::class, 'failure'])->name('identity.failure');
    });
});

Route::get('comment-ca-marche', function () {
    return Inertia::render('comment-ca-marche');
})->name('comment-ca-marche');  

// Routes pour les babysitters - DOIT être AVANT babysitter/{slug}
Route::middleware(['auth', 'role:babysitter'])->group(function () {
    Route::post('/babysitter/request-verification', [BabysitterController::class, 'requestVerification'])
        ->middleware('check.babysitter.verification')
        ->name('babysitter.request-verification');
    
    // Page de gestion des paiements dans la sidebar
    Route::get('/babysitter/paiements', [StripeController::class, 'paymentsPage'])->name('babysitter.payments');
    
    // Routes Stripe Connect
    Route::get('/stripe/connect', [StripeController::class, 'connect'])->name('babysitter.stripe.connect');
    Route::post('/stripe/create-onboarding-link', [StripeController::class, 'createOnboardingLink'])->name('babysitter.stripe.create-link');
    Route::post('/stripe/create-verification-link', [StripeController::class, 'createVerificationLink'])->name('stripe.create-verification-link');
    Route::get('/stripe/onboarding/success', [StripeController::class, 'onboardingSuccess'])->name('babysitter.stripe.onboarding.success');
    Route::get('/stripe/onboarding/refresh', [StripeController::class, 'onboardingRefresh'])->name('babysitter.stripe.onboarding.refresh');
    Route::get('/stripe/verification/success', [StripeController::class, 'onboardingSuccess'])->name('babysitter.stripe.verification.success');
    Route::get('/stripe/verification/refresh', [StripeController::class, 'onboardingRefresh'])->name('babysitter.stripe.verification.refresh');
    Route::get('/api/stripe/account-status', [StripeController::class, 'getAccountStatus'])->name('babysitter.stripe.status');
    
    // Routes Stripe Identity (nouvelles)
    Route::post('/stripe/identity/create-session', [StripeController::class, 'createIdentityVerificationSession'])->name('stripe.identity.create-session');
    Route::post('/stripe/identity/verify-and-link', [StripeController::class, 'verifyAndLinkIdentity'])->name('stripe.identity.verify-and-link');
    Route::post('/stripe/identity/resolve-eventually-due', [StripeController::class, 'resolveEventuallyDue'])->name('stripe.identity.resolve-eventually-due');
    Route::get('/api/stripe/identity/status', [StripeController::class, 'getIdentityStatus'])->name('stripe.identity.status');
    Route::get('/api/stripe/onboarding-status', [StripeController::class, 'getOnboardingStatus'])->name('stripe.onboarding-status');
    
    // Routes pour la vérification d'identité Stripe
    Route::get('/babysitter/verification-stripe', [StripeVerificationController::class, 'show'])->name('babysitter.verification-stripe');
    Route::post('/babysitter/verification-stripe/create-link', [StripeVerificationController::class, 'createVerificationLink'])->name('babysitter.stripe.verification.link');
    Route::post('/babysitter/verification-stripe/upload', [StripeVerificationController::class, 'uploadDocument'])->name('babysitter.stripe.verification.upload');
    Route::get('/api/stripe/verification-status', [StripeVerificationController::class, 'checkVerificationStatus'])->name('babysitter.stripe.verification.status');
    Route::get('/stripe/verification/success', [StripeVerificationController::class, 'success'])->name('babysitter.stripe.verification.success');
    Route::get('/stripe/verification/refresh', [StripeVerificationController::class, 'refresh'])->name('babysitter.stripe.verification.refresh');
});

Route::get('babysitter/{slug}', [BabysitterController::class, 'show'])->name('babysitter.show');

// Routes pour l'administration
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/babysitter-moderation', [App\Http\Controllers\Admin\BabysitterModerationController::class, 'index'])->name('babysitter-moderation.index');
    Route::post('/babysitter-moderation/{babysitter}/verify', [App\Http\Controllers\Admin\BabysitterModerationController::class, 'verify'])->name('babysitter-moderation.verify');
    
    // Routes pour la gestion des comptes Stripe Connect
    Route::get('/stripe-connect', [App\Http\Controllers\Admin\StripeConnectController::class, 'index'])->name('stripe-connect.index');
    Route::get('/stripe-connect/{user}', [App\Http\Controllers\Admin\StripeConnectController::class, 'show'])->name('stripe-connect.show');
    Route::delete('/stripe-connect/{user}', [App\Http\Controllers\Admin\StripeConnectController::class, 'delete'])->name('stripe-connect.delete');
    Route::post('/stripe-connect/{user}/reject', [App\Http\Controllers\Admin\StripeConnectController::class, 'reject'])->name('stripe-connect.reject');
    Route::get('/stripe-connect/refresh', [App\Http\Controllers\Admin\StripeConnectController::class, 'refresh'])->name('stripe-connect.refresh');

    // Nouvelles routes pour les comptes non liés
    Route::delete('/stripe-connect/account/{stripeAccountId}', [App\Http\Controllers\Admin\StripeConnectController::class, 'deleteByAccountId'])->name('stripe-connect.delete-account');
    Route::post('/stripe-connect/account/{stripeAccountId}/reject', [App\Http\Controllers\Admin\StripeConnectController::class, 'rejectByAccountId'])->name('stripe-connect.reject-account');
});

Route::post('/stripe/webhook', [StripeController::class, 'webhook'])->name('stripe.webhook');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/channels.php';

// Support pour le broadcasting (authentification WebSocket)
Broadcast::routes(['middleware' => ['auth']]);

// Route fallback pour les 404 - DOIT être en dernier
Route::fallback([App\Http\Controllers\ErrorController::class, 'notFound'])->name('errors.404');
