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
     * Affiche la page de vérification d'identité
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('babysitter') || !$user->stripe_account_id) {
            return redirect()->route('babysitter.payments')->with('error', 'Compte Stripe requis.');
        }

        try {
            $accountDetails = $this->stripeService->getAccountDetails($user);
            $requirements = $accountDetails['requirements'] ?? [];
            
            return Inertia::render('Babysitter/StripeVerification', [
                'accountDetails' => $accountDetails,
                'requirements' => $requirements,
                'stripeAccountId' => $user->stripe_account_id
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur récupération détails compte Stripe', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('babysitter.payments')->with('error', 'Erreur lors de la récupération des informations.');
        }
    }

    /**
     * Crée un lien de vérification d'identité Stripe
     */
    public function createVerificationLink(Request $request)
    {
        $user = $request->user();

        if (!$user->stripe_account_id) {
            return response()->json(['error' => 'Aucun compte Stripe Connect trouvé'], 400);
        }

        try {
            // Créer un lien d'account link spécifiquement pour la vérification
            $accountLink = $this->stripeService->createAccountLink($user, 'custom_account_verification');
            
            return response()->json([
                'verification_url' => $accountLink->url
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur création lien vérification', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['error' => 'Erreur lors de la création du lien de vérification'], 500);
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
     * Vérifie le statut de vérification
     */
    public function checkVerificationStatus(Request $request)
    {
        $user = $request->user();

        if (!$user->stripe_account_id) {
            return response()->json(['status' => 'no_account']);
        }

        try {
            $accountDetails = $this->stripeService->getAccountDetails($user);
            $verificationStatus = $accountDetails['individual']['verification']['status'] ?? 'unverified';
            $documentStatus = $accountDetails['individual']['verification']['document'] ?? 'unverified';
            
            return response()->json([
                'verification_status' => $verificationStatus,
                'document_status' => $documentStatus,
                'requirements' => $accountDetails['requirements'] ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
} 