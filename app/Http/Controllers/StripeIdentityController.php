<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class StripeIdentityController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Affiche la page de vérification d'identité Stripe Identity
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Vérifier le statut de vérification
        $verificationStatus = $this->stripeService->getIdentityVerificationStatus($user);
        
        // Récupérer les détails du compte Connect si disponible
        $accountDetails = null;
        if ($user->stripe_account_id) {
            try {
                $accountDetails = $this->stripeService->getAccountDetails($user);
            } catch (\Exception $e) {
                Log::warning('Could not retrieve account details', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Récupérer la session Identity existante si disponible
        $identitySession = null;
        if ($user->stripe_identity_session_id) {
            try {
                $identitySession = $this->stripeService->getIdentityVerificationSession($user->stripe_identity_session_id);
            } catch (\Exception $e) {
                Log::warning('Could not retrieve identity session', [
                    'user_id' => $user->id,
                    'session_id' => $user->stripe_identity_session_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return Inertia::render('Babysitter/IdentityVerification', [
            'verificationStatus' => $verificationStatus,
            'accountDetails' => $accountDetails,
            'identitySession' => $identitySession ? [
                'id' => $identitySession->id,
                'status' => $identitySession->status,
                'client_secret' => $identitySession->client_secret,
                'last_error' => $identitySession->last_error,
                'verified_outputs' => $identitySession->verified_outputs,
            ] : null,
            'stripePublishableKey' => config('services.stripe.key'),
        ]);
    }

    /**
     * Crée une nouvelle session de vérification Identity
     */
    public function createSession(Request $request)
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
     * Vérifie le statut de la session et met à jour le compte Connect
     */
    public function verifyAndLink(Request $request)
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
                
                // Marquer l'utilisateur comme ayant complété la vérification Identity
                $user->update([
                    'identity_verified_at' => now(),
                ]);
                
                return response()->json([
                    'success' => true,
                    'status' => 'verified',
                    'message' => 'Vérification d\'identité complétée ! Votre compte de paiement est maintenant configuré.',
                    'identity_verified' => true
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
     * Récupère le statut actuel de la vérification
     */
    public function getStatus(Request $request)
    {
        $user = $request->user();

        try {
            $verificationStatus = $this->stripeService->getIdentityVerificationStatus($user);
            
            // Récupérer aussi les détails du compte Connect
            $accountDetails = null;
            if ($user->stripe_account_id) {
                $accountDetails = $this->stripeService->getAccountDetails($user);
            }

            return response()->json([
                'success' => true,
                'verification_status' => $verificationStatus,
                'account_details' => $accountDetails,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération du statut'
            ], 500);
        }
    }

    /**
     * Page de succès après vérification Identity
     */
    public function success(Request $request)
    {
        $user = $request->user();
        
        // Tenter de lier automatiquement la vérification au compte Connect
        try {
            if ($user->stripe_identity_session_id) {
                $session = $this->stripeService->getIdentityVerificationSession($user->stripe_identity_session_id);
                if ($session->status === 'verified' && !$user->identity_verified_at) {
                    $this->stripeService->linkIdentityToConnect($user);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Impossible de lier automatiquement la vérification', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return redirect()->route('babysitter.identity-verification')->with('success', 
            'Vérification d\'identité complétée avec succès !'
        );
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
     * Page en cas d'échec de vérification
     */
    public function failure(Request $request)
    {
        return redirect()->route('babysitter.identity-verification')->with('error', 
            'La vérification d\'identité a échoué. Veuillez réessayer.'
        );
    }
} 