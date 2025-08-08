<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class SettingsController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }
    /**
     * Page des paramètres
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $requestedMode = $request->get('mode');
        
        // Déterminer les rôles de l'utilisateur
        $hasParentRole = $user->hasRole('parent');
        $hasBabysitterRole = $user->hasRole('babysitter');

        // Déterminer le mode actuel selon la demande et les rôles
        $currentMode = 'parent'; // Par défaut
        
        if ($requestedMode === 'babysitter' && $hasBabysitterRole) {
            $currentMode = 'babysitter';
        } elseif ($requestedMode === 'parent' && $hasParentRole) {
            $currentMode = 'parent';
        } elseif ($hasBabysitterRole && !$hasParentRole) {
            $currentMode = 'babysitter';
        } elseif ($hasParentRole && !$hasBabysitterRole) {
            $currentMode = 'parent';
        } elseif ($hasBabysitterRole) {
            // Si les deux rôles et pas de mode spécifique, babysitter par défaut
            $currentMode = 'babysitter';
        }

        // Récupérer les préférences de notifications
        $notificationSettings = [
            'email_notifications' => $user->email_notifications ?? true,
            'push_notifications' => $user->push_notifications ?? true,
            'sms_notifications' => $user->sms_notifications ?? false,
        ];

        // Vérifier s'il y a des réservations en cours pour la suppression de compte
        $hasActiveReservations = false;
        if ($user->hasRole('babysitter')) {
            $hasActiveReservations = Reservation::where('babysitter_id', $user->id)
                ->whereIn('status', ['confirmed', 'in_progress', 'pending_payment'])
                ->exists();
        } elseif ($user->hasRole('parent')) {
            $hasActiveReservations = Reservation::where('parent_id', $user->id)
                ->whereIn('status', ['confirmed', 'in_progress', 'pending_payment'])
                ->exists();
        }

        return Inertia::render('Settings/Index', [
            'user' => array_merge($user->toArray(), [
                'profile_photo_url' => $user->getAvatarUrl(),
                'password' => $user->password ? true : false, // Ne pas exposer le hash
                'roles' => $user->roles->toArray(),
            ]),
            'current_mode' => $currentMode,
            'has_parent_role' => $hasParentRole,
            'has_babysitter_role' => $hasBabysitterRole,
            'notification_settings' => $notificationSettings,
            'has_active_reservations' => $hasActiveReservations,
        ]);
    }

    /**
     * Mettre à jour les préférences de notifications
     */
    public function updateNotifications(Request $request)
    {
        // Debug les données reçues
        Log::info('Données notifications reçues:', $request->all());

        $user = $request->user();

        try {
            // Convertir explicitement en booléens
            $emailNotifications = filter_var($request->input('email_notifications', false), FILTER_VALIDATE_BOOLEAN);
            $pushNotifications = filter_var($request->input('push_notifications', false), FILTER_VALIDATE_BOOLEAN);
            $smsNotifications = filter_var($request->input('sms_notifications', false), FILTER_VALIDATE_BOOLEAN);

            $user->update([
                'email_notifications' => $emailNotifications,
                'push_notifications' => $pushNotifications,
                'sms_notifications' => $smsNotifications,
            ]);

            Log::info('Préférences de notifications mises à jour', [
                'user_id' => $user->id,
                'email' => $emailNotifications,
                'push' => $pushNotifications,
                'sms' => $smsNotifications,
            ]);

            return back()->with('success', 'Préférences de notifications mises à jour avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour notifications', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Erreur lors de la mise à jour des préférences');
        }
    }

    /**
     * Changer le mot de passe
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        // Validation conditionnelle selon si l'utilisateur a déjà un mot de passe
        if ($user->password) {
            $request->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', Rules\Password::defaults(), 'confirmed'],
            ]);
        } else {
            $request->validate([
                'password' => ['required', Rules\Password::defaults(), 'confirmed'],
            ]);
        }

        try {
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            Log::info('Mot de passe mis à jour', [
                'user_id' => $user->id,
                'had_password' => $user->password ? true : false
            ]);

            return back()->with('success', 'Mot de passe mis à jour avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour mot de passe', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Erreur lors de la mise à jour du mot de passe');
        }
    }


    /**
     * Supprimer le compte utilisateur
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'confirmation' => 'required|string|in:SUPPRIMER',
        ]);
        
        // Vérifier que c'est bien une requête de suppression (DELETE ou POST avec _method=DELETE)
        if (!$request->isMethod('DELETE') && $request->get('_method') !== 'DELETE') {
            return back()->with('error', 'Méthode non autorisée pour cette action.');
        }

        $user = $request->user();

        // Vérifier s'il y a des réservations actives
        $hasActiveReservations = false;
        if ($user->hasRole('babysitter')) {
            $hasActiveReservations = Reservation::where('babysitter_id', $user->id)
                ->whereIn('status', ['confirmed', 'in_progress', 'pending_payment'])
                ->exists();
        } elseif ($user->hasRole('parent')) {
            $hasActiveReservations = Reservation::where('parent_id', $user->id)
                ->whereIn('status', ['confirmed', 'in_progress', 'pending_payment'])
                ->exists();
        }

        if ($hasActiveReservations) {
            return back()->with('error', 'Impossible de supprimer le compte : vous avez des réservations en cours.');
        }

        // Vérifier le solde Stripe si l'utilisateur a un compte Connect
        $stripeBalance = null;
        if ($user->stripe_account_id) {
            try {
                $stripeBalance = $this->stripeService->getAccountBalance($user);
                
                // Vérifier s'il y a un solde positif (EUR par défaut)
                if ($stripeBalance && isset($stripeBalance['available'])) {
                    $availableBalances = $stripeBalance['available'];
                    
                    // Chercher le solde en EUR ou prendre la première devise disponible
                    $eurBalance = 0;
                    foreach ($availableBalances as $balance) {
                        if (isset($balance['currency']) && $balance['currency'] === 'eur') {
                            $eurBalance = $balance['amount'];
                            break;
                        }
                    }
                    
                    // Si pas de solde EUR, prendre le premier disponible
                    if ($eurBalance === 0 && !empty($availableBalances)) {
                        $eurBalance = $availableBalances[0]['amount'] ?? 0;
                    }
                    
                    if ($eurBalance > 0) {
                        $balanceAmount = $eurBalance / 100; // Convertir les centimes en euros
                        Log::warning('Tentative de suppression de compte avec solde Stripe', [
                            'user_id' => $user->id,
                            'stripe_account_id' => $user->stripe_account_id,
                            'balance_available' => $balanceAmount,
                            'raw_balance' => $availableBalances
                        ]);
                        
                        return back()->with('error', 
                            "Impossible de supprimer le compte : vous avez un solde de {$balanceAmount}€ sur votre compte Stripe. " .
                            "Veuillez d'abord retirer vos fonds ou contacter le support."
                        );
                    }
                }
            } catch (\Exception $e) {
                Log::error('Erreur lors de la vérification du solde Stripe', [
                    'user_id' => $user->id,
                    'stripe_account_id' => $user->stripe_account_id,
                    'error' => $e->getMessage()
                ]);
                
                // On continue la suppression même si on ne peut pas vérifier le solde
                // mais on log l'erreur pour investigation
            }
        }

        try {
            DB::beginTransaction();
            
            // Log de la suppression
            Log::warning('Suppression de compte utilisateur', [
                'user_id' => $user->id,
                'email' => $user->email,
                'roles' => $user->roles()->pluck('name')->toArray(),
                'stripe_account_id' => $user->stripe_account_id,
                'stripe_balance' => $stripeBalance
            ]);

            // Gérer le compte Stripe Connect si présent
            if ($user->stripe_account_id) {
                try {
                    // Archiver ou désactiver le compte Stripe Connect
                    // Note: Stripe ne permet pas de supprimer complètement un compte Connect
                    // mais nous pouvons le marquer comme fermé dans nos logs
                    Log::info('Compte Stripe Connect associé au compte supprimé', [
                        'user_id' => $user->id,
                        'stripe_account_id' => $user->stripe_account_id
                    ]);
                    
                    // Note: Si il reste de l'argent sur le compte Stripe, 
                    // Stripe s'occupera automatiquement du transfert selon ses règles
                    // Nous avons déjà vérifié plus haut s'il y avait des fonds
                } catch (\Exception $e) {
                    Log::error('Erreur lors de la gestion du compte Stripe', [
                        'user_id' => $user->id,
                        'stripe_account_id' => $user->stripe_account_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Déconnecter l'utilisateur
            auth()->logout();

            // Supprimer l'utilisateur (les relations seront supprimées en cascade)
            // Cela inclut : profils, messages, annonces, candidatures, etc.
            $user->delete();

            // Valider la transaction
            DB::commit();

            // Invalider la session
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('home')->with('success', 'Votre compte a été supprimé avec succès.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur suppression compte', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Erreur lors de la suppression du compte : ' . $e->getMessage());
        }
    }
} 