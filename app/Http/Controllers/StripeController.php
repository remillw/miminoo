<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Reservation;

class StripeController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Affiche la page d'onboarding Stripe Connect
     */
    public function connect(Request $request)
    {
        $user = $request->user();
        
        // Vérifier que l'utilisateur est un babysitter vérifié
        if (!$user->hasRole('babysitter') || !$user->babysitterProfile || $user->babysitterProfile->verification_status !== 'verified') {
            return redirect()->route('dashboard')->with('error', 'Vous devez être un babysitter vérifié pour accéder à cette page.');
        }

        // Le compte Stripe Connect sera créé uniquement via l'onboarding dédié
        Log::info('🏦 Accès à la page paiements', [
            'user_id' => $user->id, 
            'has_stripe_account' => !is_null($user->stripe_account_id),
            'stripe_account_id' => $user->stripe_account_id
        ]);

        // Récupérer le statut et les détails du compte
        $accountStatus = null;
        $accountDetails = null;
        $accountBalance = null;
        $recentTransactions = [];
        
        if ($user->stripe_account_id) {
            try {
                $accountStatus = $this->stripeService->getAccountStatus($user);
                $accountDetails = $this->stripeService->getAccountDetails($user);

                // Si le compte est actif, récupérer le solde et les transactions
                if ($accountStatus === 'active') {
                    $accountBalance = $this->stripeService->getAccountBalance($user);
                    $recentTransactions = $this->stripeService->getRecentTransactions($user, 5);
                }
            } catch (\Exception $e) {
                Log::warning('Erreur récupération données Stripe - compte peut-être en cours de création', [
                    'user_id' => $user->id,
                    'stripe_account_id' => $user->stripe_account_id,
                    'error' => $e->getMessage()
                ]);
                
                // Si on a un stripe_account_id mais qu'on ne peut pas le récupérer,
                // c'est probablement que le compte vient d'être créé
                $accountStatus = 'pending';
            }
        } else {
            // Pas de compte Stripe Connect configuré
            $accountStatus = null;
        }

        return Inertia::render('Babysitter/StripeOnboarding', [
            'accountStatus' => $accountStatus,
            'accountDetails' => $accountDetails,
            'accountBalance' => $accountBalance,
            'recentTransactions' => $recentTransactions,
            'stripeAccountId' => $user->stripe_account_id
        ]);
    }

    /**
     * Créer un lien d'onboarding Stripe Connect pré-rempli
     */
    public function createOnboardingLink(Request $request)
    {
        $user = $request->user();

        try {
            // Vérifier que l'utilisateur est babysitter
            if (!$user->hasRole('babysitter')) {
                if ($request->wantsJson() || $request->header('X-Inertia')) {
                    return back()->withErrors(['error' => 'Seuls les babysitters peuvent créer un compte de paiement']);
                }
                return response()->json(['error' => 'Seuls les babysitters peuvent créer un compte de paiement'], 403);
            }

            // Vérifier l'âge minimum
            if ($user->date_of_birth) {
                $age = \Carbon\Carbon::parse($user->date_of_birth)->age;
                if ($age < 16) {
                    if ($request->wantsJson() || $request->header('X-Inertia')) {
                        return back()->withErrors(['error' => 'Vous devez avoir au moins 16 ans pour créer un compte de paiement']);
                    }
                    return response()->json(['error' => 'Vous devez avoir au moins 16 ans pour créer un compte de paiement'], 400);
                }
            } else {
                if ($request->wantsJson() || $request->header('X-Inertia')) {
                    return back()->withErrors(['error' => 'Veuillez renseigner votre date de naissance dans votre profil']);
                }
                return response()->json(['error' => 'Veuillez renseigner votre date de naissance dans votre profil'], 400);
            }

            // Si c'est un onboarding interne, traiter différemment
            if ($request->has('internal_onboarding') && $request->internal_onboarding) {
                return $this->internalOnboarding($request);
            }

            $accountLink = $this->stripeService->createOnboardingLink($user);
            
            // Pour les requêtes Inertia, rediriger ou retourner les props
            if ($request->header('X-Inertia')) {
                return redirect($accountLink->url);
            }
            
            return response()->json([
                'onboarding_url' => $accountLink->url,
                'expires_at' => $accountLink->expires_at,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur création lien onboarding', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            if ($request->wantsJson() || $request->header('X-Inertia')) {
                return back()->withErrors(['error' => 'Erreur lors de la création du lien d\'onboarding : ' . $e->getMessage()]);
            }
            
            return response()->json([
                'error' => 'Erreur lors de la création du lien d\'onboarding : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Page de succès après onboarding
     */
    public function onboardingSuccess(Request $request)
    {
        $user = $request->user();
        
        // Mettre à jour le statut du compte
        try {
            $this->stripeService->getAccountStatus($user);
        } catch (\Exception $e) {
            Log::warning('Impossible de mettre à jour le statut après onboarding', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return redirect()->route('babysitter.payments', ['verification' => 'completed'])->with('success', 
            'Configuration terminée ! Votre compte est en cours de vérification.'
        );
    }

    /**
     * Page de rafraîchissement en cas d'expiration du lien
     */
    public function onboardingRefresh(Request $request)
    {
        return redirect()->route('babysitter.payments')->with('info', 
            'Le lien de configuration a expiré. Veuillez en créer un nouveau.'
        );
    }


    /**
     * Créer un lien de vérification Connect (pour finaliser avec documents)
     */
    public function createVerificationLink(Request $request)
    {
        $user = $request->user();

        try {
            $accountLink = $this->stripeService->createVerificationLink($user);
            
            return response()->json([
                'success' => true,
                'verification_url' => $accountLink->url,
                'message' => 'Lien de vérification créé avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du lien de vérification', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la création du lien de vérification'
            ], 500);
        }
    }

    /**
     * Récupérer le statut d'onboarding intelligent
     */
    public function getOnboardingStatus(Request $request)
    {
        $user = $request->user();

        try {
            $status = $this->stripeService->getOnboardingStatus($user);
            
            return response()->json([
                'success' => true,
                'status' => $status
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du statut d\'onboarding', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération du statut'
            ], 500);
        }
    }

    /**
     * Page de refresh/retry si l'onboarding n'est pas terminé
     */
    public function refresh(Request $request)
    {
        return $this->connect($request);
    }

    /**
     * API pour récupérer le statut du compte
     */
    public function getAccountStatus(Request $request)
    {
        $user = $request->user();

        if (!$user->stripe_account_id) {
            return response()->json(['status' => 'no_account']);
        }

        try {
            $status = $this->stripeService->getAccountStatus($user);
            return response()->json(['status' => $status]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            Log::error('Invalid payload in Stripe webhook', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Invalid signature in Stripe webhook', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        // Gérer les événements
        switch ($event['type']) {
            case 'account.updated':
                $this->handleAccountUpdated($event['data']['object']);
                break;
            
            case 'identity.verification_session.verified':
                $this->handleIdentityVerified($event['data']['object']);
                break;
                
            case 'identity.verification_session.requires_input':
                $this->handleIdentityRequiresInput($event['data']['object']);
                break;
                
            default:
                Log::info('Unhandled Stripe webhook event', ['type' => $event['type']]);
        }

        return response('Webhook handled', 200);
    }

    private function handleAccountUpdated($account)
    {
        $user = User::where('stripe_account_id', $account['id'])->first();
        
        if ($user) {
            // Récupérer les détails mis à jour du compte
            $accountDetails = $this->stripeService->getAccountStatus($user);
            
            // Log des changements importants
            Log::info('Compte Connect mis à jour via webhook', [
                'user_id' => $user->id,
                'account_id' => $account['id'],
                'charges_enabled' => $account['charges_enabled'] ?? false,
                'details_submitted' => $account['details_submitted'] ?? false,
                'requirements_currently_due' => $account['requirements']['currently_due'] ?? [],
                'requirements_eventually_due' => $account['requirements']['eventually_due'] ?? [],
                'payouts_enabled' => $account['payouts_enabled'] ?? false
            ]);
            
            // Si les requirements sont maintenant résolus et que l'utilisateur avait une session Identity
            if (empty($account['requirements']['currently_due']) && 
                empty($account['requirements']['eventually_due']) && 
                $user->stripe_identity_session_id) {
                
                Log::info('Requirements automatiquement résolus après vérification Identity', [
                    'user_id' => $user->id,
                    'account_id' => $account['id'],
                    'identity_session_id' => $user->stripe_identity_session_id
                ]);
                
                // Marquer la vérification comme complètement résolue
                $user->update([
                    'identity_verified_at' => now()
                ]);
            }
        }
    }

    private function handleIdentityVerified($verificationSession)
    {
        try {
            // Trouver l'utilisateur via les métadonnées
            $userId = $verificationSession['metadata']['user_id'] ?? null;
            
            if (!$userId) {
                Log::warning('No user_id in verification session metadata', [
                    'session_id' => $verificationSession['id']
                ]);
                return;
            }
            
            $user = User::find($userId);
            if (!$user) {
                Log::warning('User not found for verification session', [
                    'user_id' => $userId,
                    'session_id' => $verificationSession['id']
                ]);
                return;
            }
            
            // Lier la vérification Identity au compte Connect
            $this->stripeService->linkIdentityToConnect($user, $verificationSession['id']);
            
            // Automatiquement résoudre les requirements eventually_due
            try {
                $this->stripeService->resolveEventuallyDueIdentityDocument($user);
                Log::info('Automatically resolved eventually_due requirements after Identity verification', [
                    'user_id' => $user->id,
                    'session_id' => $verificationSession['id']
                ]);
            } catch (\Exception $e) {
                Log::warning('Could not automatically resolve eventually_due requirements', [
                    'user_id' => $user->id,
                    'session_id' => $verificationSession['id'],
                    'error' => $e->getMessage()
                ]);
            }
            
            Log::info('Identity verification completed and linked to Connect account', [
                'user_id' => $user->id,
                'session_id' => $verificationSession['id']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error handling identity verification webhook', [
                'session_id' => $verificationSession['id'],
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function handleIdentityRequiresInput($verificationSession)
    {
        try {
            $userId = $verificationSession['metadata']['user_id'] ?? null;
            
            if ($userId) {
                Log::info('Identity verification requires input', [
                    'user_id' => $userId,
                    'session_id' => $verificationSession['id'],
                    'last_error' => $verificationSession['last_error'] ?? null
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error handling identity requires input webhook', [
                'session_id' => $verificationSession['id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Page de gestion des paiements pour la sidebar babysitter
     */
    public function paymentsPage(Request $request)
    {
        $user = $request->user();
        
        // Vérifier que l'utilisateur est un babysitter
        if (!$user->hasRole('babysitter')) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        // Le compte Stripe Connect sera créé uniquement via l'onboarding dédié
        Log::info('🔗 Accès à la page Stripe Connect', [
            'user_id' => $user->id, 
            'has_stripe_account' => !is_null($user->stripe_account_id),
            'stripe_account_id' => $user->stripe_account_id
        ]);

        // Récupérer les informations du compte
        try {
            $accountStatus = $this->stripeService->getAccountStatus($user);
            $accountDetails = $this->stripeService->getAccountDetails($user);
            $accountBalance = null;
            $recentTransactions = [];

            Log::info('📊 Statut récupéré dans le contrôleur', [
                'user_id' => $user->id,
                'accountStatus' => $accountStatus,
                'stripe_account_id' => $user->stripe_account_id
            ]);

            // Si le compte est actif, récupérer le solde et les transactions
            if ($accountStatus === 'active') {
                $accountBalance = $this->stripeService->getAccountBalance($user);
                $recentTransactions = $this->stripeService->getRecentTransactions($user, 10);
            }
        } catch (\Exception $e) {
            Log::error('❌ Exception dans paymentsPage', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $accountStatus = 'pending';
            $accountDetails = null;
            $accountBalance = null;
            $recentTransactions = [];
        }

        // Récupérer les réservations/transactions de la babysitter avec statut des fonds
        // Seulement celles qui ne sont PAS encore virées (pas de funds_status = 'released')
        $reservationTransactions = Reservation::where('babysitter_id', $user->id)
            ->whereIn('status', ['paid', 'active', 'service_completed', 'completed'])
            ->where(function($query) {
                $query->whereNull('funds_status')
                      ->orWhere('funds_status', '!=', 'released');
            })
            ->with(['parent', 'ad'])
            ->orderBy('service_start_at', 'desc')
            ->get()
            ->map(function ($reservation) {
                // Calculer le statut des fonds
                $fundsStatus = $this->getFundsStatusForReservation($reservation);
                $fundsMessage = $this->getFundsMessageForReservation($reservation, $fundsStatus);
                $releaseDate = $this->getFundsReleaseDateForReservation($reservation);

                return [
                    'id' => $reservation->id,
                    'type' => 'payment',
                    'created' => $reservation->service_start_at ?? $reservation->created_at,
                    'amount' => $reservation->babysitter_amount ?? ($reservation->total_deposit - ($reservation->service_fee ?? 2)),
                    'status' => 'succeeded', // Le paiement a réussi
                    'description' => 'Service de garde - ' . $reservation->ad->title,
                    'parent_name' => $reservation->parent->firstname . ' ' . $reservation->parent->lastname,
                    'service_date' => $reservation->service_start_at,
                    'service_end' => $reservation->service_end_at,
                    'reservation_status' => $reservation->status,
                    'funds_status' => $fundsStatus,
                    'funds_message' => $fundsMessage,
                    'funds_release_date' => $releaseDate,
                    'reservation_id' => $reservation->id,
                ];
            });

        // Récupérer l'historique des virements Stripe pour cette babysitter
        $payoutHistory = [];
        if ($accountStatus === 'active') {
            try {
                $payoutHistory = $this->stripeService->getPayoutHistory($user, 20);
            } catch (\Exception $e) {
                Log::error('Erreur récupération historique virements', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Récupérer les transactions de déduction de la babysitter
        $deductionTransactions = \App\Models\Transaction::where('babysitter_id', $user->id)
            ->where('type', 'deduction')
            ->with(['reservation.parent', 'reservation.ad'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($transaction) {
                $reservation = $transaction->reservation;
                return [
                    'id' => $transaction->id,
                    'type' => 'deduction',
                    'date' => $transaction->created_at,
                    'parent_name' => $reservation->parent->firstname . ' ' . $reservation->parent->lastname,
                    'amount' => $transaction->amount, // Négatif
                    'description' => $transaction->description,
                    'ad_title' => $reservation->ad->title,
                    'reservation_id' => $reservation->id,
                    'metadata' => $transaction->metadata,
                ];
            });

        $viewData = [
            'mode' => 'babysitter',
            'accountStatus' => $accountStatus,
            'accountDetails' => $accountDetails,
            'accountBalance' => $accountBalance,
            'recentTransactions' => $reservationTransactions, // Utiliser nos nouvelles transactions détaillées
            'payoutHistory' => $payoutHistory, // Ajouter l'historique des virements
            'deductionTransactions' => $deductionTransactions,
            'stripeAccountId' => $user->stripe_account_id,
            'babysitterProfile' => $user->babysitterProfile,
            'user' => [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'phone' => $user->phone,
                'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
                'address' => $user->address ? [
                    'address' => $user->address->address,
                    'postal_code' => $user->address->postal_code,
                    'city' => $user->address->city ?? '',
                ] : null,
            ],
            'googlePlacesApiKey' => config('services.google.places_api_key')
        ];

        Log::info('📤 Données envoyées à la vue', [
            'user_id' => $user->id,
            'viewData' => $viewData
        ]);

        return Inertia::render('Babysitter/Payments', $viewData);
    }

    /**
     * Récupérer la configuration Stripe (clé publique)
     */
    public function getConfig()
    {
        return response()->json([
            'publishable_key' => config('services.stripe.key')
        ]);
    }

    /**
     * Récupérer les moyens de paiement sauvegardés de l'utilisateur
     */
    public function getPaymentMethods(Request $request)
    {
        $user = $request->user();

        try {
            // Vérifier que l'utilisateur a un customer ID Stripe
            if (!$user->stripe_customer_id) {
                return response()->json(['payment_methods' => []]);
            }

            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            
            $paymentMethods = $stripe->paymentMethods->all([
                'customer' => $user->stripe_customer_id,
                'type' => 'card',
            ]);

            return response()->json([
                'payment_methods' => $paymentMethods->data
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur récupération moyens de paiement', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['payment_methods' => []], 500);
        }
    }

    /**
     * Configurer la fréquence des virements
     */
    public function configurePayoutSchedule(Request $request)
    {
        $request->validate([
            'interval' => 'required|in:daily,weekly,monthly,manual',
            'weekly_anchor' => 'nullable|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'monthly_anchor' => 'nullable|integer|min:1|max:31',
            'minimum_amount' => 'nullable|integer|min:2500', // 25€ minimum
        ]);

        $user = $request->user();

        try {
            if ($request->interval === 'manual') {
                $this->stripeService->disableAutomaticPayouts($user);
            } else {
                $this->stripeService->updatePayoutSchedule(
                    $user,
                    $request->interval,
                    $request->weekly_anchor ?? 'friday'
                );
            }

            if ($request->minimum_amount) {
                $this->stripeService->updateMinimumPayoutAmount($user, $request->minimum_amount);
            }

            return response()->json([
                'success' => true,
                'message' => 'Configuration des virements mise à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Créer un virement manuel
     */
    public function createManualPayout(Request $request)
    {
        $request->validate([
            'amount' => 'nullable|integer|min:2500', // 25€ minimum
        ]);

        $user = $request->user();

        try {
            $payout = $this->stripeService->createManualPayout(
                $user,
                $request->amount
            );

            return redirect()->back()->with('success', 'Virement créé avec succès ! Montant: ' . ($payout->amount / 100) . '€');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la création du virement: ' . $e->getMessage());
        }
    }

    /**
     * Récupérer l'historique des virements
     */
    public function getPayoutHistory(Request $request)
    {
        $user = $request->user();

        try {
            $payouts = $this->stripeService->getPayoutHistory($user, 20);

            return response()->json([
                'success' => true,
                'payouts' => $payouts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Générer une facture pour la babysitter
     */
    public function generateInvoice(Request $request)
    {
        $request->validate([
            'period' => 'required|string',
            'reservation_ids' => 'required|array',
            'reservation_ids.*' => 'exists:reservations,id',
        ]);

        $user = $request->user();

        try {
            // Récupérer les réservations
            $reservations = \App\Models\Reservation::whereIn('id', $request->reservation_ids)
                ->where('babysitter_id', $user->id)
                ->where('status', 'completed')
                ->with(['parent'])
                ->get();

            if ($reservations->isEmpty()) {
                throw new \Exception('Aucune réservation valide trouvée');
            }

            $invoice = $this->stripeService->generateInvoiceForBabysitter(
                $user,
                $reservations,
                $request->period
            );

            return response()->json([
                'success' => true,
                'message' => 'Facture générée avec succès',
                'invoice' => [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'pdf' => $invoice->invoice_pdf,
                    'hosted_invoice_url' => $invoice->hosted_invoice_url
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Calculer le statut des fonds pour une réservation
     */
    private function getFundsStatusForReservation(Reservation $reservation)
    {
        // Vérifier d'abord le champ funds_status s'il existe
        if ($reservation->funds_status) {
            return $reservation->funds_status;
        }

        // Vérifier d'abord si la réservation a été annulée
        if (in_array($reservation->status, ['cancelled_by_parent', 'cancelled_by_babysitter'])) {
            // Si annulé, vérifier s'il y a eu un remboursement
            if ($reservation->stripe_refund_id) {
                return 'refunded'; // Parent remboursé = babysitter ne touche rien
            }
            
            // Distinction selon qui a annulé :
            if ($reservation->status === 'cancelled_by_babysitter') {
                // Babysitter annule = elle ne touche rien (même sans remboursement parent)
                return 'cancelled';
            } else {
                // Parent annule sans remboursement = situation particulière
                return 'cancelled';
            }
        }

        // Vérifier s'il y a une dispute active
        if ($reservation->disputes()->where('status', 'active')->exists()) {
            return 'disputed';
        }

        // Calculer le statut basé sur les dates et le statut de la réservation
        $now = now();
        $serviceEnd = $reservation->service_end_at ? new \Carbon\Carbon($reservation->service_end_at) : null;

        if ($reservation->status === 'paid') {
            // Service pas encore commencé
            return 'pending_service';
        } elseif ($reservation->status === 'active') {
            // Service en cours
            return 'pending_service';
        } elseif (in_array($reservation->status, ['service_completed', 'completed'])) {
            if (!$serviceEnd) {
                return 'held_for_validation';
            }

            $releaseDate = $serviceEnd->copy()->addHours(24);
            if ($now->gte($releaseDate)) {
                return 'released';
            } else {
                return 'held_for_validation';
            }
        }

        return 'pending_service';
    }

    /**
     * Obtenir le message d'état des fonds
     */
    private function getFundsMessageForReservation(Reservation $reservation, $fundsStatus)
    {
        switch ($fundsStatus) {
            case 'pending_service':
                return 'En attente du début du service';
            case 'held_for_validation':
                $releaseDate = $this->getFundsReleaseDateForReservation($reservation);
                if ($releaseDate) {
                    return 'Fonds libérés le ' . $releaseDate->format('d/m/Y à H:i');
                }
                return 'En attente de libération (24h après la fin du service)';
            case 'released':
                return 'Fonds libérés sur votre compte';
            case 'disputed':
                return 'Fonds bloqués - réclamation en cours';
            case 'cancelled':
                if ($reservation->status === 'cancelled_by_babysitter') {
                    return 'Vous avez annulé - aucun paiement';
                }
                return 'Service annulé - aucun paiement';
            case 'refunded':
                if ($reservation->status === 'cancelled_by_babysitter') {
                    return 'Vous avez annulé - parent remboursé';
                }
                return 'Service remboursé - aucun paiement';
            default:
                return 'Statut inconnu';
        }
    }

    /**
     * Calculer la date de libération des fonds
     */
    private function getFundsReleaseDateForReservation(Reservation $reservation)
    {
        if ($reservation->funds_released_at) {
            return new \Carbon\Carbon($reservation->funds_released_at);
        }

        if ($reservation->funds_hold_until) {
            return new \Carbon\Carbon($reservation->funds_hold_until);
        }

        if ($reservation->service_end_at) {
            return (new \Carbon\Carbon($reservation->service_end_at))->addHours(24);
        }

        return null;
    }

    /**
     * Onboarding interne sécurisé - collecte les données via notre interface
     */
    public function internalOnboarding(Request $request)
    {
        Log::info('🚀 Route internalOnboarding atteinte', [
            'method' => $request->method(),
            'url' => $request->url(),
            'headers' => $request->headers->all()
        ]);

        $user = $request->user();

        if (!$user) {
            Log::error('❌ Utilisateur non authentifié');
            return back()->withErrors(['error' => 'Utilisateur non authentifié']);
        }

        Log::info('🔍 Début internalOnboarding', [
            'user_id' => $user->id,
            'request_data' => $request->all()
        ]);

        // Nettoyer l'IBAN avant validation
        $request->merge([
            'iban' => strtoupper(str_replace(' ', '', $request->input('iban', '')))
        ]);

        // Validation des données - adaptation selon si on utilise un token ou non
        $hasToken = $request->has('account_token') && !empty($request->account_token);
        
        $validationRules = [
            'account_token' => 'nullable|string', // Token Stripe créé côté client
            'internal_onboarding' => 'nullable|in:true,false,1,0',
        ];

        if ($hasToken) {
            // Avec token, seules les données bancaires sont requises
            $validationRules = array_merge($validationRules, [
                'iban' => 'required|string|regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4}[0-9]{7}([A-Z0-9]?){0,16}$/',
                'account_holder_name' => 'required|string|max:255',
                'business_description' => 'nullable|string|max:500',
                'mcc' => 'nullable|string',
            ]);
        } else {
            // Sans token, toutes les données sont requises (ancien système)
            $validationRules = array_merge($validationRules, [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'nullable|string|max:20',
                'dob_day' => 'required|integer|min:1|max:31',
                'dob_month' => 'required|integer|min:1|max:12',
                'dob_year' => 'required|integer|min:1900|max:' . (date('Y') - 16),
                'address_line1' => 'required|string|max:255',
                'address_city' => 'required|string|max:255',
                'address_postal_code' => 'required|string|regex:/^[0-9]{5}$/',
                'address_country' => 'required|string|size:2',
                'account_holder_name' => 'required|string|max:255',
                'iban' => 'required|string|regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4}[0-9]{7}([A-Z0-9]?){0,16}$/',
                'business_description' => 'required|string|max:500',
                'mcc' => 'nullable|string',
                'tos_acceptance' => 'required|accepted',
            ]);
        }

        $validated = $request->validate($validationRules);

        // Ajouter les valeurs par défaut si elles ne sont pas présentes
        $validated['mcc'] = $validated['mcc'] ?? '8299'; // Code MCC pour services de garde d'enfants

        try {
            // Vérifier que l'utilisateur est babysitter
            if (!$user->hasRole('babysitter')) {
                return response()->json(['error' => 'Seuls les babysitters peuvent créer un compte de paiement'], 403);
            }

            // Vérifier l'âge minimum (seulement si on n'utilise pas de token car l'âge est déjà vérifié dans le token)
            if (!$hasToken) {
                $birthDate = sprintf('%04d-%02d-%02d', $validated['dob_year'], $validated['dob_month'], $validated['dob_day']);
                $age = \Carbon\Carbon::parse($birthDate)->age;
                if ($age < 16) {
                    return back()->withErrors(['error' => 'Vous devez avoir au moins 16 ans pour créer un compte de paiement']);
                }
            }

            // Créer ou mettre à jour le compte Stripe Connect avec les données fournies
            $result = $this->stripeService->createOrUpdateConnectAccountInternal($user, $validated);
            
            // Recharger l'utilisateur pour s'assurer que les données Stripe sont bien persistées
            $user->refresh();
            
            Log::info('✅ Compte configuré - vérification de la persistance', [
                'user_id' => $user->id,
                'stripe_account_id' => $user->stripe_account_id,
                'stripe_account_status' => $user->stripe_account_status,
                'result_account_id' => $result->id ?? 'N/A'
            ]);
            
            // Si c'est une requête AJAX (fetch), retourner JSON
            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => 'Compte configuré avec succès !',
                    'redirect' => route('babysitter.payments')
                ]);
            }
            
            return redirect()->route('babysitter.payments')->with('success', 'Compte configuré avec succès !');

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'onboarding interne', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Gestion spécifique pour l'erreur de comptes français
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'Connect platforms based in FR must create accounts via account tokens') !== false) {
                // L'erreur ne devrait plus se produire avec notre nouvelle implémentation des account tokens
                Log::warning('🇫🇷 Erreur token française détectée malgré l\'implémentation des account tokens', [
                    'user_id' => $user->id,
                    'error' => $errorMessage
                ]);
                
                $errorMessage = 'Configuration avec account tokens en cours. Veuillez réessayer ou utiliser la configuration externe.';
                
                // Rediriger vers l'onboarding externe si disponible
                return back()->withErrors(['error' => $errorMessage])->with('suggest_external', true);
            }
            
            return back()->withErrors(['error' => 'Erreur lors de la configuration du compte : ' . $errorMessage])
                        ->withInput($request->except(['tos_acceptance'])); // Préserver les données sauf la case à cocher
        }
    }

} 