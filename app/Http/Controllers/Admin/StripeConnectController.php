<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class StripeConnectController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Afficher la liste de tous les comptes Stripe Connect
     */
    public function index()
    {
        try {
            $accounts = $this->stripeService->getAllStripeAccounts();
            
            return Inertia::render('Admin/StripeConnect', [
                'accounts' => $accounts,
                'stats' => [
                    'total_accounts' => count($accounts),
                    'active_accounts' => count(array_filter($accounts, function($account) {
                        return ($account['stripe_account']['charges_enabled'] ?? false) && 
                               ($account['stripe_account']['payouts_enabled'] ?? false);
                    })),
                    'pending_accounts' => count(array_filter($accounts, function($account) {
                        return $account['status'] === 'pending';
                    })),
                    'rejected_accounts' => count(array_filter($accounts, function($account) {
                        return $account['status'] === 'rejected';
                    })),
                    'deletable_accounts' => count(array_filter($accounts, function($account) {
                        return $account['can_be_deleted'];
                    })),
                    'linked_accounts' => count(array_filter($accounts, function($account) {
                        return $account['is_linked_to_user'];
                    })),
                    'unlinked_accounts' => count(array_filter($accounts, function($account) {
                        return !$account['is_linked_to_user'];
                    }))
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du chargement de la page admin Stripe Connect', [
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Erreur lors du chargement des comptes Stripe Connect : ' . $e->getMessage());
        }
    }

    /**
     * Supprimer un compte Stripe Connect
     */
    public function delete(Request $request, User $user)
    {
        try {
            $this->stripeService->deleteConnectAccount($user);
            
            Log::info('Compte Stripe Connect supprimé par admin', [
                'admin_id' => Auth::id(),
                'admin_email' => Auth::user()?->email,
                'deleted_user_id' => $user->id,
                'deleted_user_email' => $user->email
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Compte Stripe Connect supprimé avec succès.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du compte Stripe Connect par admin', [
                'admin_id' => Auth::id(),
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Rejeter un compte Stripe Connect
     */
    public function reject(Request $request, User $user)
    {
        $request->validate([
            'reason' => 'required|in:fraud,terms_of_service,other',
            'admin_note' => 'nullable|string|max:1000'
        ]);

        try {
            $this->stripeService->rejectConnectAccount($user, $request->reason);
            
            Log::info('Compte Stripe Connect rejeté par admin', [
                'admin_id' => Auth::id(),
                'admin_email' => Auth::user()?->email,
                'rejected_user_id' => $user->id,
                'rejected_user_email' => $user->email,
                'reason' => $request->reason,
                'admin_note' => $request->admin_note
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Compte Stripe Connect rejeté avec succès.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du rejet du compte Stripe Connect par admin', [
                'admin_id' => Auth::id(),
                'user_id' => $user->id,
                'reason' => $request->reason,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet : ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Récupérer les détails d'un compte Stripe Connect
     */
    public function show(User $user)
    {
        try {
            if (!$user->stripe_account_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet utilisateur n\'a pas de compte Stripe Connect'
                ], 404);
            }

            $accountDetails = $this->stripeService->getAccountDetails($user);
            $accountBalance = null;
            
            try {
                $accountBalance = $this->stripeService->getAccountBalance($user);
            } catch (\Exception $e) {
                // Si on ne peut pas récupérer le solde, on continue
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'firstname' => $user->firstname,
                        'lastname' => $user->lastname,
                        'email' => $user->email,
                        'created_at' => $user->created_at,
                        'stripe_account_status' => $user->stripe_account_status
                    ],
                    'account_details' => $accountDetails,
                    'account_balance' => $accountBalance
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des détails du compte Stripe Connect', [
                'admin_id' => Auth::id(),
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un compte Stripe Connect par ID de compte
     */
    public function deleteByAccountId(Request $request, $stripeAccountId)
    {
        try {
            // Chercher si ce compte est lié à un utilisateur
            $user = User::where('stripe_account_id', $stripeAccountId)->first();
            
            if ($user) {
                // Si lié à un utilisateur, utiliser la méthode existante
                $this->stripeService->deleteConnectAccount($user);
                $userName = $user->firstname . ' ' . $user->lastname;
            } else {
                // Si pas lié, supprimer directement par ID
                $this->stripeService->deleteConnectAccountById($stripeAccountId);
                $userName = "Compte " . $stripeAccountId;
            }
            
            Log::info('Compte Stripe Connect supprimé par admin (par ID)', [
                'admin_id' => Auth::id(),
                'admin_email' => Auth::user()?->email,
                'stripe_account_id' => $stripeAccountId,
                'linked_user_id' => $user?->id,
                'linked_user_email' => $user?->email
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Compte Stripe Connect supprimé avec succès.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du compte Stripe Connect par ID', [
                'admin_id' => Auth::id(),
                'stripe_account_id' => $stripeAccountId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Rejeter un compte Stripe Connect par ID de compte
     */
    public function rejectByAccountId(Request $request, $stripeAccountId)
    {
        $request->validate([
            'reason' => 'required|in:fraud,terms_of_service,other',
            'admin_note' => 'nullable|string|max:1000'
        ]);

        try {
            // Chercher si ce compte est lié à un utilisateur
            $user = User::where('stripe_account_id', $stripeAccountId)->first();
            
            if ($user) {
                // Si lié à un utilisateur, utiliser la méthode existante
                $this->stripeService->rejectConnectAccount($user, $request->reason);
                $userName = $user->firstname . ' ' . $user->lastname;
            } else {
                // Si pas lié, rejeter directement par ID
                $this->stripeService->rejectConnectAccountById($stripeAccountId, $request->reason);
                $userName = "Compte " . $stripeAccountId;
            }
            
            Log::info('Compte Stripe Connect rejeté par admin (par ID)', [
                'admin_id' => Auth::id(),
                'admin_email' => Auth::user()?->email,
                'stripe_account_id' => $stripeAccountId,
                'linked_user_id' => $user?->id,
                'linked_user_email' => $user?->email,
                'reason' => $request->reason,
                'admin_note' => $request->admin_note
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Compte Stripe Connect rejeté avec succès.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du rejet du compte Stripe Connect par ID', [
                'admin_id' => Auth::id(),
                'stripe_account_id' => $stripeAccountId,
                'reason' => $request->reason,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet : ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Rafraîchir les données des comptes
     */
    public function refresh()
    {
        try {
            $accounts = $this->stripeService->getAllStripeAccounts();
            
            return response()->json([
                'success' => true,
                'data' => $accounts
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du rafraîchissement des comptes Stripe Connect', [
                'admin_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rafraîchissement : ' . $e->getMessage()
            ], 500);
        }
    }
} 