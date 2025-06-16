<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\BabysitterController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\StripeVerificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Auth\SocialAuthController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Routes pour l'authentification sociale
Route::prefix('auth')->group(function () {
    Route::get('/{provider}', [SocialAuthController::class, 'redirectToProvider'])
        ->where('provider', 'google|apple')
        ->name('social.redirect');
    
    Route::get('/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback'])
        ->where('provider', 'google|apple')
        ->name('social.callback');
    
    Route::middleware('auth')->delete('/{provider}/unlink', [SocialAuthController::class, 'unlinkProvider'])
        ->where('provider', 'google|apple')
        ->name('social.unlink');
});

Route::get('/annonces', [AnnouncementController::class, 'index'])->name('announcements.index');

// Route publique pour la configuration Stripe
Route::get('api/stripe/config', [StripeController::class, 'getConfig']);

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

    // Routes pour les réservations
    Route::post('applications/{application}/create-reservation', [ReservationController::class, 'createFromApplication'])->name('applications.create-reservation');
    Route::get('applications/{application}/payment', [ReservationController::class, 'showApplicationPaymentPage'])->name('applications.payment');
    Route::get('reservations/{reservation}/payment', [ReservationController::class, 'showPaymentPage'])->name('reservations.payment');
    Route::post('reservations/{reservation}/confirm-payment', [ReservationController::class, 'confirmPayment'])->name('reservations.confirm-payment');
    Route::post('reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
    Route::post('reservations/{reservation}/start-service', [ReservationController::class, 'startService'])->name('reservations.start-service');
    Route::post('reservations/{reservation}/complete-service', [ReservationController::class, 'completeService'])->name('reservations.complete-service');
    Route::get('reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
    
    // Routes pour les avis
    Route::get('reviews', [App\Http\Controllers\ReviewController::class, 'index'])->name('reviews.index');
    Route::get('reviews/create/{reservation}', [App\Http\Controllers\ReviewController::class, 'create'])->name('reviews.create');
    Route::post('reviews/{reservation}', [App\Http\Controllers\ReviewController::class, 'store'])->name('reviews.store');
    
    // Routes pour les réclamations
    Route::get('disputes', [App\Http\Controllers\DisputeController::class, 'index'])->name('disputes.index');
    Route::get('disputes/create/{reservation}', [App\Http\Controllers\DisputeController::class, 'create'])->name('disputes.create');
    Route::post('disputes/{reservation}', [App\Http\Controllers\DisputeController::class, 'store'])->name('disputes.store');
    Route::get('disputes/{dispute}', [App\Http\Controllers\DisputeController::class, 'show'])->name('disputes.show');
    
    // Routes API pour Stripe
    Route::get('api/stripe/payment-methods', [StripeController::class, 'getPaymentMethods']);
    Route::get('api/reservations/{reservation}/payment-intent', [ReservationController::class, 'getPaymentIntent']);

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
    
    // Nouvelles routes pour la gestion des paiements
    Route::post('/babysitter/paiements/configure-schedule', [StripeController::class, 'configurePayoutSchedule'])->name('babysitter.payments.configure-schedule');
    Route::post('/babysitter/paiements/manual-payout', [StripeController::class, 'createManualPayout'])->name('babysitter.payments.manual-payout');
    Route::get('/babysitter/paiements/history', [StripeController::class, 'getPayoutHistory'])->name('babysitter.payments.history');
    Route::post('/babysitter/paiements/generate-invoice', [StripeController::class, 'generateInvoice'])->name('babysitter.payments.generate-invoice');
    
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

// Routes publiques avec slugs
Route::get('parent/{slug}', [App\Http\Controllers\ParentController::class, 'show'])->name('parent.show');
Route::get('annonce/{slug}', [AnnouncementController::class, 'show'])->name('announcements.show');

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

// Webhook Stripe pour la libération automatique des fonds (sans middleware auth)
Route::post('/stripe/webhook/funds-release', [App\Http\Controllers\StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook.funds-release');

// Routes pour les notifications
Route::middleware('auth')->group(function () {
    Route::post('/notifications/{notification}/mark-as-read', [App\Http\Controllers\DashboardController::class, 'markNotificationAsRead'])
        ->name('notifications.mark-as-read');
    Route::post('/notifications/mark-all-as-read', [App\Http\Controllers\DashboardController::class, 'markAllNotificationsAsRead'])
        ->name('notifications.mark-all-as-read');
    
    // Route pour mettre à jour la disponibilité du babysitter
    Route::post('/babysitter/toggle-availability', [App\Http\Controllers\BabysitterController::class, 'toggleAvailability'])
        ->name('babysitter.toggle-availability');
});



// Routes pour les paiements (unifiées babysitter/parent)
Route::middleware(['auth'])->group(function () {
    Route::get('/paiements', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/paiements/facture/{reservation}', [PaymentController::class, 'downloadInvoice'])->name('payments.download-invoice');
});

// Routes spécifiques pour les parents (compatibilité)
Route::middleware(['auth'])->prefix('parent')->group(function () {
    Route::get('/paiements', [PaymentController::class, 'index'])->name('parent.payments');
});

Route::middleware(['auth'])->prefix('parametres')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications');
    Route::post('/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::post('/language', [SettingsController::class, 'updateLanguage'])->name('settings.language');
    Route::delete('/account', [SettingsController::class, 'deleteAccount'])->name('settings.delete-account');
});

require __DIR__.'/auth.php';
require __DIR__.'/channels.php';

// Support pour le broadcasting (authentification WebSocket)
Broadcast::routes(['middleware' => ['auth']]);

// Route fallback pour les 404 - DOIT être en dernier
Route::fallback([App\Http\Controllers\ErrorController::class, 'notFound'])->name('errors.404');
