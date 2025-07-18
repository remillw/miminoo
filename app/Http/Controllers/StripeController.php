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

        // Si pas de compte Stripe, en créer un
        if (!$user->stripe_account_id) {
            try {
                $this->stripeService->createConnectAccount($user);
                $user->refresh(); // Recharger l'utilisateur avec les nouvelles données
            } catch (\Exception $e) {
                Log::error('Erreur création compte Stripe Connect', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                return redirect()->route('dashboard')->with('error', 'Erreur lors de la création du compte de paiement.');
            }
        }

        // Récupérer le statut et les détails du compte
        try {
            $accountStatus = $this->stripeService->getAccountStatus($user);
            $accountDetails = $this->stripeService->getAccountDetails($user);
            $accountBalance = null;
            $recentTransactions = [];

            // Si le compte est actif, récupérer le solde et les transactions
            if ($accountStatus === 'active') {
                $accountBalance = $this->stripeService->getAccountBalance($user);
                $recentTransactions = $this->stripeService->getRecentTransactions($user, 5);
            }
        } catch (\Exception $e) {
            $accountStatus = 'pending';
            $accountDetails = null;
            $accountBalance = null;
            $recentTransactions = [];
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
                return response()->json(['error' => 'Seuls les babysitters peuvent créer un compte de paiement'], 403);
            }

            // Vérifier l'âge minimum
            if ($user->date_of_birth) {
                $age = \Carbon\Carbon::parse($user->date_of_birth)->age;
                if ($age < 16) {
                    return response()->json(['error' => 'Vous devez avoir au moins 16 ans pour créer un compte de paiement'], 400);
                }
            } else {
                return response()->json(['error' => 'Veuillez renseigner votre date de naissance dans votre profil'], 400);
            }

            $accountLink = $this->stripeService->createOnboardingLink($user);
            
            return response()->json([
                'onboarding_url' => $accountLink->url,
                'expires_at' => $accountLink->expires_at,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur création lien onboarding', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
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
     * Créer une session de vérification d'identité Stripe Identity
     */
    public function createIdentityVerificationSession(Request $request)
    {
        $user = $request->user();

        try {
            $verificationSession = $this->stripeService->createIdentityVerificationSession($user);
            
            return response()->json([
                'success' => true,
                'session' => [
                    'id' => $verificationSession->id,
                    'client_secret' => $verificationSession->client_secret,
                    'status' => $verificationSession->status,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur création session Identity', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la création de la session de vérification'
            ], 500);
        }
    }

    /**
     * Vérifier et lier la session Identity au compte Connect
     */
    public function verifyAndLinkIdentity(Request $request)
    {
        $user = $request->user();

        try {
            if (!$user->stripe_identity_session_id) {
                throw new \Exception('Aucune session de vérification trouvée');
            }

            // Vérifier le statut de la session
            $session = $this->stripeService->getIdentityVerificationSession($user->stripe_identity_session_id);

            if ($session->status === 'verified') {
                // Lier les données vérifiées au compte Connect
                $this->stripeService->linkIdentityToConnect($user);
                
                return response()->json([
                    'success' => true,
                    'status' => 'verified',
                    'message' => 'Vérification complétée et liée au compte Connect'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'status' => $session->status,
                    'last_error' => $session->last_error,
                    'message' => 'Vérification non complétée'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification et liaison', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la vérification'
            ], 500);
        }
    }

    /**
     * Récupérer le statut de vérification Identity
     */
    public function getIdentityStatus(Request $request)
    {
        $user = $request->user();

        try {
            $status = $this->stripeService->getIdentityVerificationStatus($user);
            
            return response()->json([
                'success' => true,
                'status' => $status['status'],
                'method' => $status['method'],
                'requires_identity' => $status['requires_identity'] ?? false,
                'can_use_identity' => $status['can_use_identity'] ?? false,
                'verified_at' => $status['verified_at'] ?? null,
                'session_id' => $user->stripe_identity_session_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du statut Identity', [
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
     * Résoudre les exigences eventually_due avec Identity
     */
    public function resolveEventuallyDue(Request $request)
    {
        $user = $request->user();

        try {
            $result = $this->stripeService->resolveEventuallyDueIdentityDocument($user);
            
            return response()->json([
                'success' => true,
                'message' => 'Résolution des exigences tentée avec succès',
                'result' => $result,
                'account_link_url' => $result['account_link']->url ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la résolution eventually_due', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la résolution des exigences'
            ], 500);
        }
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
            $this->stripeService->getAccountStatus($user);
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

        // Si pas de compte Stripe, en créer un
        if (!$user->stripe_account_id) {
            try {
                $this->stripeService->createConnectAccount($user);
                $user->refresh();
            } catch (\Exception $e) {
                Log::error('Erreur création compte Stripe Connect', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

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
            'babysitterProfile' => $user->babysitterProfile
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
} 