<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Page de paiements unifiée (babysitter ou parent selon le rôle)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Forcer le mode parent sauf si explicitement babysitter via URL
        $requestedMode = $request->input('mode', 'parent');
        
        // Si l'utilisateur demande le mode babysitter ET a le rôle babysitter
        if ($requestedMode === 'babysitter' && $user->hasRole('babysitter')) {
            return $this->babysitterPayments($request);
        } else {
            // Par défaut, mode parent
            return $this->parentPayments($request);
        }
    }

    /**
     * Page de paiements pour les babysitters
     */
    private function babysitterPayments(Request $request)
    {
        $user = $request->user();

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

        // Récupérer les réservations/transactions de la babysitter avec statut des fonds
        $reservationTransactions = Reservation::where('babysitter_id', $user->id)
            ->whereIn('status', ['paid', 'active', 'service_completed', 'completed'])
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
                    'amount' => $reservation->babysitter_amount ?? ($reservation->total_deposit - $reservation->service_fee ?? 2),
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

        return Inertia::render('Payments/Index', [
            'mode' => 'babysitter',
            'accountStatus' => $accountStatus,
            'accountDetails' => $accountDetails,
            'accountBalance' => $accountBalance,
            'recentTransactions' => $reservationTransactions, // Remplacer par nos transactions détaillées
            'stripeAccountId' => $user->stripe_account_id,
            'babysitterProfile' => $user->babysitterProfile
        ]);
    }

    /**
     * Page de paiements pour les parents
     */
    private function parentPayments(Request $request)
    {
        $user = $request->user();

        // Récupérer toutes les réservations du parent
        $reservations = Reservation::where('parent_id', $user->id)
            ->with(['babysitter', 'ad'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculer les statistiques
        $totalSpent = $reservations->whereIn('status', ['completed', 'service_completed', 'paid'])->sum('total_deposit');
        $totalReservations = $reservations->count();
        $pendingPayments = $reservations->where('status', 'pending_payment')->count();

        // Récupérer les transactions de remboursement du parent
        $refundTransactions = \App\Models\Transaction::where('payer_id', $user->id)
            ->where('type', 'refund')
            ->with(['reservation.babysitter', 'reservation.ad'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Créer la liste combinée des transactions (réservations + remboursements)
        $transactions = collect();

        // Ajouter les réservations
        $reservations->each(function ($reservation) use ($transactions) {
            $startDate = $reservation->service_start_at ? new \Carbon\Carbon($reservation->service_start_at) : null;
            $endDate = $reservation->service_end_at ? new \Carbon\Carbon($reservation->service_end_at) : null;
            
            // Calculer la durée en heures
            $duration = $startDate && $endDate ? $startDate->diffInHours($endDate) : 0;
            
            $transactions->push([
                'id' => $reservation->id,
                'type' => 'payment',
                'date' => $reservation->created_at,
                'babysitter_name' => $reservation->babysitter->firstname . ' ' . $reservation->babysitter->lastname,
                'amount' => $reservation->total_deposit,
                'status' => $reservation->status,
                'service_start' => $reservation->service_start_at,
                'service_end' => $reservation->service_end_at,
                'duration' => $duration,
                'ad_title' => $reservation->ad->title,
                'can_download_invoice' => in_array($reservation->status, ['completed', 'service_completed']),
            ]);
        });

        // Ajouter les remboursements
        $refundTransactions->each(function ($transaction) use ($transactions) {
            $reservation = $transaction->reservation;
            $transactions->push([
                'id' => $transaction->id,
                'type' => 'refund',
                'date' => $transaction->created_at,
                'babysitter_name' => $reservation->babysitter->firstname . ' ' . $reservation->babysitter->lastname,
                'amount' => $transaction->amount,
                'status' => 'refunded',
                'description' => $transaction->description,
                'ad_title' => $reservation->ad->title,
                'original_reservation_id' => $reservation->id,
                'metadata' => $transaction->metadata,
            ]);
        });

        // Trier par date décroissante
        $transactions = $transactions->sortByDesc('date')->values();

        return Inertia::render('Payments/Index', [
            'mode' => 'parent',
            'stats' => [
                'total_spent' => $totalSpent,
                'total_reservations' => $totalReservations,
                'pending_payments' => $pendingPayments,
            ],
            'transactions' => $transactions,
        ]);
    }

    /**
     * Télécharger la facture d'une réservation (pour les parents)
     */
    public function downloadInvoice(Request $request, Reservation $reservation)
    {
        $user = $request->user();

        // Vérifier que l'utilisateur est le parent de cette réservation
        if ($reservation->parent_id !== $user->id) {
            abort(403, 'Accès non autorisé');
        }

        // Vérifier que le service est terminé (date de fin passée)
        $now = now();
        $serviceEndTime = $reservation->service_end_at ? new \Carbon\Carbon($reservation->service_end_at) : null;
        
        if (!$serviceEndTime || $serviceEndTime->isFuture()) {
            return response()->json([
                'error' => 'La facture n\'est disponible qu\'après la fin du service de babysitting.'
            ], 400);
        }

        // Vérifier que la réservation est dans un état permettant le téléchargement
        if (!in_array($reservation->status, ['completed', 'service_completed', 'paid'])) {
            return response()->json([
                'error' => 'La facture n\'est pas encore disponible pour cette réservation.'
            ], 400);
        }

        try {
            // Générer la facture via Stripe
            $invoice = $this->generateInvoiceForReservation($reservation);

            // Si c'est une requête AJAX, retourner l'URL
            if ($request->wantsJson()) {
                return response()->json([
                    'pdf_url' => $invoice->invoice_pdf
                ]);
            }

            // Sinon rediriger vers l'URL de téléchargement Stripe
            return redirect($invoice->invoice_pdf);
        } catch (\Exception $e) {
            Log::error('Erreur génération facture parent', [
                'reservation_id' => $reservation->id,
                'parent_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'Impossible de générer la facture'
                ], 500);
            }

            return back()->with('error', 'Impossible de générer la facture');
        }
    }

    /**
     * Générer une facture pour une réservation
     */
    private function generateInvoiceForReservation(Reservation $reservation)
    {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

        // Créer un customer pour le parent s'il n'en a pas
        if (!$reservation->parent->stripe_customer_id) {
            $customer = $stripe->customers->create([
                'email' => $reservation->parent->email,
                'name' => $reservation->parent->firstname . ' ' . $reservation->parent->lastname,
                'metadata' => [
                    'user_id' => $reservation->parent->id,
                    'platform' => 'TrouveTaBabysitter'
                ]
            ]);

            $reservation->parent->update(['stripe_customer_id' => $customer->id]);
        }

        // Calculer la durée du service
        $startDate = new \Carbon\Carbon($reservation->service_start_at);
        $endDate = new \Carbon\Carbon($reservation->service_end_at);
        $duration = $startDate->diffInHours($endDate);

        // Créer la facture
        $invoice = $stripe->invoices->create([
            'customer' => $reservation->parent->stripe_customer_id,
            'collection_method' => 'send_invoice',
            'days_until_due' => 30,
            'metadata' => [
                'reservation_id' => $reservation->id,
                'parent_id' => $reservation->parent->id,
                'platform' => 'TrouveTaBabysitter'
            ]
        ]);

        // Ajouter l'élément de ligne
        $stripe->invoiceItems->create([
            'customer' => $reservation->parent->stripe_customer_id,
            'invoice' => $invoice->id,
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => 'Service de garde - ' . $startDate->format('d/m/Y'),
                    'description' => 'Garde de ' . $duration . 'h avec ' . 
                                   $reservation->babysitter->firstname . ' ' . $reservation->babysitter->lastname .
                                   ' du ' . $startDate->format('d/m/Y à H:i') . ' au ' . $endDate->format('d/m/Y à H:i'),
                ],
                'unit_amount' => round($reservation->total_deposit * 100), // Convertir en centimes
            ],
            'quantity' => 1,
        ]);

        // Finaliser la facture
        $stripe->invoices->finalizeInvoice($invoice->id);

        return $invoice;
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