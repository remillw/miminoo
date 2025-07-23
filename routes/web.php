<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\BabysitterController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\StripeVerificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\GoogleAuthController;

Route::get('/', function () {
    return Inertia::render('waitlist');
})->name('home');


Route::get('accueil', function () {
    return Inertia::render('Welcome');
})->name('Welcome');

// Routes pour l'authentification Google uniquement
Route::prefix('auth')->group(function () {
    Route::get('/google', [GoogleAuthController::class, 'redirect'])
        ->name('google.redirect');
    
    Route::get('/google/callback', [GoogleAuthController::class, 'callback'])
        ->name('google.callback');
    
    Route::post('/google/complete', [GoogleAuthController::class, 'completeRegistration'])
        ->name('google.complete');
    
    // Page de transition pour l'app mobile après auth Google
    Route::get('/mobile/callback', function () {
        return view('mobile-auth-callback');
    })->name('mobile.auth.callback');
    
    // Apple - Commenté pour implémentation future
    /*
    Route::get('/apple', [SocialAuthController::class, 'redirectToProvider'])
        ->defaults('provider', 'apple')
        ->name('apple.redirect');
    
    Route::get('/apple/callback', [SocialAuthController::class, 'handleProviderCallback'])
        ->defaults('provider', 'apple')
        ->name('apple.callback');
    */
    
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

Route::get('tableau-de-bord', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// Routes publiques pour création d'annonce (avec ou sans connexion)
Route::get('creer-une-annonce', [AnnouncementController::class, 'create'])->name('creer.une.annonce');
Route::post('annonces', [AnnouncementController::class, 'store'])->name('announcements.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('profil', [ProfileController::class, 'show'])->name('profil');
    Route::put('profil', [ProfileController::class, 'update'])->name('profil.update');
    
    // Routes pour les annonces authentifiées
    Route::get('mes-annonces', [AnnouncementController::class, 'myAnnouncements'])->name('announcements.my');
    Route::get('mes-annonces-et-reservations', [AnnouncementController::class, 'myAnnouncementsAndReservations'])->name('parent.announcements-reservations');
    Route::post('annonces/{announcement}/apply', [AnnouncementController::class, 'apply'])
        ->middleware('check.babysitter.verification:apply')
        ->name('announcements.apply');
    // Routes d'édition d'annonce pour les parents - déplacées vers le bas pour éviter les conflits
    Route::delete('annonces/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    Route::post('annonces/{announcement}/cancel', [AnnouncementController::class, 'cancel'])->name('announcements.cancel');
    

    
    // Routes pour la messagerie
    Route::get('messagerie', [MessagingController::class, 'index'])->name('messaging.index');
          Route::post('candidatures/{application}/mark-viewed', [MessagingController::class, 'markApplicationAsViewed'])->name('applications.mark-viewed');
      Route::get('candidatures/{application}/mark-viewed', [MessagingController::class, 'markApplicationAsViewed'])->name('applications.mark-viewed-get');
    Route::post('candidatures/{application}/reserve', [MessagingController::class, 'reserveApplication'])->name('applications.reserve');
    Route::post('candidatures/{application}/decline', [MessagingController::class, 'declineApplication'])->name('applications.decline');
    Route::post('candidatures/{application}/counter-offer', [MessagingController::class, 'counterOffer'])->name('applications.counter-offer');
    Route::post('candidatures/{application}/babysitter-counter', [MessagingController::class, 'babysitterCounterOffer'])->name('applications.babysitter-counter');
    Route::post('candidatures/{application}/respond-counter', [MessagingController::class, 'respondToCounterOffer'])->name('applications.respond-counter');
    Route::post('candidatures/{application}/cancel', [MessagingController::class, 'cancelApplication'])->name('applications.cancel');
    Route::post('candidatures/{application}/cancel-by-parent', [MessagingController::class, 'cancelReservationByParent'])->name('applications.cancel-by-parent');
    
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
    Route::get('avis', [App\Http\Controllers\ReviewController::class, 'index'])->name('reviews.index');
    Route::get('avis/creer/{reservation}', [App\Http\Controllers\ReviewController::class, 'create'])->name('reviews.create');
    Route::post('avis/{reservation}', [App\Http\Controllers\ReviewController::class, 'store'])->name('reviews.store');
    
    // Routes pour les réclamations
    Route::get('reclamations', [App\Http\Controllers\DisputeController::class, 'index'])->name('disputes.index');
    Route::get('reclamations/creer/{reservation}', [App\Http\Controllers\DisputeController::class, 'create'])->name('disputes.create');
    Route::post('reclamations/{reservation}', [App\Http\Controllers\DisputeController::class, 'store'])->name('disputes.store');
    Route::get('reclamations/{dispute}', [App\Http\Controllers\DisputeController::class, 'show'])->name('disputes.show');
    
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

Route::get('contact', [ContactController::class, 'index'])->name('contact');
Route::post('contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('mentions-legales', function () {
    return Inertia::render('MentionsLegales');
})->name('mentions-legales');

Route::get('politique-de-confidentialite', function () {
    return Inertia::render('PolitiqueConfidentialité');
})->name('politique-de-confidentialite');

Route::get('conditions-generales-d-utilisation', function () {
    return Inertia::render('CGU');
})->name('conditions-generales-d-utilisation');

Route::get('faq', function () {
    return Inertia::render('Faq');
})->name('faq');

Route::get('devenir-babysitter', function () {
    return Inertia::render('devenir-babysitter');
})->name('devenir-babysitter');


Route::get('a-propos', function () {        
    return Inertia::render('a-propos');
})->name('a-propos');




// Routes pour les babysitters - DOIT être AVANT babysitter/{slug}
Route::middleware(['auth', 'role:babysitter'])->group(function () {
    // Route pour la page unifiée babysitting
    Route::get('babysitting', [App\Http\Controllers\BabysittingController::class, 'index'])->name('babysitting.index');
    
    Route::post('/babysitter/request-verification', [BabysitterController::class, 'requestVerification'])
        ->name('babysitter.request-verification');
    
    // Page de gestion des paiements dans la sidebar - NÉCESSITE VÉRIFICATION
    Route::get('/babysitter/paiements', [StripeController::class, 'paymentsPage'])
        ->middleware('check.babysitter.verification:payments')
        ->name('babysitter.payments');
    
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
Route::get('parent/{slug}', [ParentController::class, 'show'])->name('parent.show');
Route::get('annonce/{slug}', [AnnouncementController::class, 'show'])->name('announcements.show');

// Routes d'édition d'annonce pour les parents (avec préfixe parent pour éviter les conflits)
Route::middleware(['auth'])->prefix('parent')->group(function () {
    Route::get('/annonces/{announcement}/modifier', [AnnouncementController::class, 'edit'])->name('parent.announcements.edit');
    Route::put('/annonces/{announcement}', [AnnouncementController::class, 'update'])->name('parent.announcements.update');
});



// Routes pour l'administration
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\AdminController::class, 'dashboard'])->name('dashboard');
    
    // Modération des babysitters
    Route::get('/moderation-babysitters', [App\Http\Controllers\Admin\BabysitterModerationController::class, 'index'])->name('babysitter-moderation.index');
    Route::post('/moderation-babysitters/{babysitter}/verify', [App\Http\Controllers\Admin\BabysitterModerationController::class, 'verify'])->name('babysitter-moderation.verify');
    
    // Gestion des parents
    Route::get('/parents', [App\Http\Controllers\Admin\AdminController::class, 'parents'])->name('parents');
    
    // Gestion des babysitters
    Route::get('/babysitters', [App\Http\Controllers\Admin\AdminController::class, 'babysitters'])->name('babysitters');
    
    // Gestion des annonces
    Route::get('/annonces', [App\Http\Controllers\Admin\AdminController::class, 'announcements'])->name('announcements');
    Route::get('/admin-annonces/{id}/modifier', [App\Http\Controllers\Admin\AdminController::class, 'editAnnouncement'])->name('admin.announcements.edit');
    Route::put('/admin-annonces/{id}', [App\Http\Controllers\Admin\AdminController::class, 'updateAnnouncement'])->name('admin.announcements.update');
    Route::delete('/admin-annonces/{id}', [App\Http\Controllers\Admin\AdminController::class, 'destroyAnnouncement'])->name('admin.announcements.destroy');
    
    // Gestion des avis
    Route::get('/avis', [App\Http\Controllers\Admin\AdminController::class, 'reviews'])->name('reviews');
    Route::delete('/avis/{id}', [App\Http\Controllers\Admin\AdminController::class, 'destroyReview'])->name('reviews.destroy');
    
    // Gestion des contacts
    Route::get('/contacts', [ContactController::class, 'adminIndex'])->name('contacts.index');
    Route::get('/contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
    Route::put('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
    
    // Gestion des utilisateurs
    Route::get('/utilisateurs/creer', [App\Http\Controllers\Admin\AdminController::class, 'createUser'])->name('users.create');
    Route::post('/utilisateurs', [App\Http\Controllers\Admin\AdminController::class, 'storeUser'])->name('users.store');
    Route::get('/utilisateurs/{id}', [App\Http\Controllers\Admin\AdminController::class, 'showUser'])->name('users.show');
    Route::get('/utilisateurs/{id}/modifier', [App\Http\Controllers\Admin\AdminController::class, 'editUser'])->name('users.edit');
    Route::put('/utilisateurs/{id}', [App\Http\Controllers\Admin\AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/utilisateurs/{id}', [App\Http\Controllers\Admin\AdminController::class, 'destroyUser'])->name('users.destroy');
    
    // Routes pour la gestion des comptes Stripe Connect
    Route::get('/comptes-stripe', [App\Http\Controllers\Admin\StripeConnectController::class, 'index'])->name('stripe-connect.index');
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
    Route::get('/reservations/{reservation}/invoice', [PaymentController::class, 'downloadInvoice'])->name('reservations.download-invoice');
});

// Routes spécifiques pour les parents (compatibilité)
Route::middleware(['auth'])->prefix('parent')->group(function () {
    // Route supprimée car dupliquée - utiliser /paiements directement
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

// Route de debug pour tester l'authentification (temporaire)
Route::post('/debug-auth', function () {
    return response()->json([
        'auth_user' => auth()->user(),
        'session_id' => session()->getId(),
        'has_session' => session()->has('_token'),
        'csrf_token' => csrf_token(),
        'cookies' => request()->cookies->all(),
        'headers' => [
            'user-agent' => request()->header('user-agent'),
            'referer' => request()->header('referer'),
            'x-csrf-token' => request()->header('x-csrf-token'),
            'cookie' => request()->header('cookie') ? 'Present' : 'Missing',
        ]
    ]);
});

// Routes pour les device tokens (notifications push)
Route::middleware(['auth'])->group(function () {
    Route::post('/device-token', [App\Http\Controllers\DeviceTokenController::class, 'store'])->name('device-token.store');
    Route::delete('/device-token', [App\Http\Controllers\DeviceTokenController::class, 'destroy'])->name('device-token.destroy');
    Route::put('/device-token/preferences', [App\Http\Controllers\DeviceTokenController::class, 'updatePreferences'])->name('device-token.preferences');
    Route::post('/clear-device-token-flag', [App\Http\Controllers\DeviceTokenController::class, 'clearRegistrationFlag'])->name('device-token.clear-flag');
});

// Support pour le broadcasting (authentification WebSocket)
Broadcast::routes(['middleware' => ['auth']]);

// Route fallback pour les 404 - DOIT être en dernier
Route::fallback([App\Http\Controllers\ErrorController::class, 'notFound'])->name('errors.404');
