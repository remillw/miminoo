<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class StripeVerificationController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Affiche la page de vérification d'identité Stripe Connect
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Vérifier le statut de vérification Connect
        $verificationStatus = $this->stripeService->checkIdentityVerificationStatus($user);
        
        // Récupérer les détails du compte pour plus d'informations
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
        
        return Inertia::render('Babysitter/StripeVerification', [
            'verificationStatus' => $verificationStatus,
            'needsVerification' => !in_array($verificationStatus, ['verified']),
            'accountDetails' => $accountDetails,
        ]);
    }

    /**
     * Crée un lien de vérification d'identité Stripe Connect
     */
    public function createVerificationLink(Request $request)
    {
        $user = $request->user();

        try {
            // Créer un AccountLink pour la vérification Connect
            $accountLink = $this->stripeService->createVerificationLink($user);
            
            return response()->json([
                'url' => $accountLink->url,
                'expires_at' => $accountLink->expires_at,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur création lien vérification Connect', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Erreur lors de la création du lien de vérification'
            ], 500);
        }
    }

    /**
     * Upload de document d'identité (alternative à Stripe)
     */
    public function uploadDocument(Request $request)
    {
        $request->validate([
            'document_type' => 'required|in:identity_document,additional_document',
            'document_front' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'document_back' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $user = $request->user();

        if (!$user->stripe_account_id) {
            return response()->json(['error' => 'Aucun compte Stripe Connect trouvé'], 400);
        }

        try {
            // Upload du document vers Stripe
            $frontFile = $request->file('document_front');
            $backFile = $request->file('document_back');

            $result = $this->stripeService->uploadVerificationDocument(
                $user,
                $request->document_type,
                $frontFile,
                $backFile
            );

            return response()->json([
                'success' => true,
                'message' => 'Document uploadé avec succès. Vérification en cours.',
                'file_id' => $result['file_id'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur upload document', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['error' => 'Erreur lors de l\'upload du document'], 500);
        }
    }

    /**
     * Vérifie le statut de vérification Connect
     */
    public function checkVerificationStatus(Request $request)
    {
        $user = $request->user();

        if (!$user->stripe_account_id) {
            return response()->json(['status' => 'no_account']);
        }

        try {
            $verificationStatus = $this->stripeService->checkIdentityVerificationStatus($user);
            $accountDetails = $this->stripeService->getAccountDetails($user);
            
            return response()->json([
                'verification_status' => $verificationStatus,
                'account_details' => $accountDetails,
                'charges_enabled' => $accountDetails['charges_enabled'] ?? false,
                'payouts_enabled' => $accountDetails['payouts_enabled'] ?? false,
                'requirements' => $accountDetails['requirements'] ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Page de succès après vérification
     */
    public function success(Request $request)
    {
        $user = $request->user();
        
        // Vérifier le nouveau statut après la vérification
        $verificationStatus = $this->stripeService->checkIdentityVerificationStatus($user);
        
        if ($verificationStatus === 'verified') {
            // Marquer la vérification comme complète dans notre base de données
            $user->update([
                'identity_verified_at' => now(),
            ]);
        }
        
        return redirect()->route('babysitter.verification-stripe')->with('success', 
            'Vérification d\'identité terminée. Statut: ' . $verificationStatus
        );
    }

    /**
     * Page de rafraîchissement en cas d'expiration du lien
     */
    public function refresh(Request $request)
    {
        return redirect()->route('babysitter.verification-stripe')->with('info', 
            'Le lien de vérification a expiré. Veuillez en créer un nouveau.'
        );
    }
} 