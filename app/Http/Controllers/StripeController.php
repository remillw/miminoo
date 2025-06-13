<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\User;

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
     * Crée un lien d'onboarding Stripe Connect
     */
    public function createOnboardingLink(Request $request)
    {
        $user = $request->user();

        if (!$user->stripe_account_id) {
            return response()->json(['error' => 'Aucun compte Stripe Connect trouvé'], 400);
        }

        try {
            $accountLink = $this->stripeService->createAccountLink($user);
            
            return response()->json([
                'onboarding_url' => $accountLink->url
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur création lien onboarding', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['error' => 'Erreur lors de la création du lien d\'onboarding'], 500);
        }
    }

    /**
     * Page de succès après onboarding
     */
    public function success(Request $request)
    {
        $user = $request->user();
        
        // Mettre à jour le statut du compte
        try {
            $status = $this->stripeService->getAccountStatus($user);
        } catch (\Exception $e) {
            $status = 'pending';
        }

        return Inertia::render('Babysitter/StripeSuccess', [
            'accountStatus' => $status
        ]);
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

            // Si le compte est actif, récupérer le solde et les transactions
            if ($accountStatus === 'active') {
                $accountBalance = $this->stripeService->getAccountBalance($user);
                $recentTransactions = $this->stripeService->getRecentTransactions($user, 10);
            }
        } catch (\Exception $e) {
            $accountStatus = 'pending';
            $accountDetails = null;
            $accountBalance = null;
            $recentTransactions = [];
        }

        return Inertia::render('Babysitter/Payments', [
            'accountStatus' => $accountStatus,
            'accountDetails' => $accountDetails,
            'accountBalance' => $accountBalance,
            'recentTransactions' => $recentTransactions,
            'stripeAccountId' => $user->stripe_account_id,
            'babysitterProfile' => $user->babysitterProfile
        ]);
    }
} 