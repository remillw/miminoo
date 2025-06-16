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

        // Déterminer le mode selon les rôles de l'utilisateur
        $currentMode = 'parent'; // Par défaut
        if ($user->hasRole('babysitter')) {
            $currentMode = 'babysitter';
            return $this->babysitterPayments($request);
        } else {
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

        return Inertia::render('Payments/Index', [
            'mode' => 'babysitter',
            'accountStatus' => $accountStatus,
            'accountDetails' => $accountDetails,
            'accountBalance' => $accountBalance,
            'recentTransactions' => $recentTransactions,
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

        // Récupérer les réservations du parent
        $reservations = Reservation::where('parent_id', $user->id)
            ->with(['babysitter', 'announcement'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculer les statistiques
        $totalSpent = $reservations->where('status', 'completed')->sum('total_amount');
        $totalReservations = $reservations->count();
        $pendingPayments = $reservations->whereIn('status', ['pending_payment', 'payment_failed'])->count();

        // Grouper les transactions par mois pour l'affichage
        $transactions = $reservations->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'date' => $reservation->created_at,
                'babysitter_name' => $reservation->babysitter->firstname . ' ' . $reservation->babysitter->lastname,
                'amount' => $reservation->total_amount,
                'status' => $reservation->status,
                'duration' => $reservation->duration,
                'start_date' => $reservation->start_date,
                'can_download_invoice' => in_array($reservation->status, ['completed', 'service_completed']),
            ];
        });

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

        // Vérifier que la réservation est complétée
        if (!in_array($reservation->status, ['completed', 'service_completed'])) {
            abort(400, 'La facture n\'est pas encore disponible');
        }

        try {
            // Générer la facture via Stripe
            $invoice = $this->generateInvoiceForReservation($reservation);

            // Rediriger vers l'URL de téléchargement Stripe
            return redirect($invoice->invoice_pdf);
        } catch (\Exception $e) {
            Log::error('Erreur génération facture parent', [
                'reservation_id' => $reservation->id,
                'parent_id' => $user->id,
                'error' => $e->getMessage()
            ]);

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
                    'platform' => 'miminoo'
                ]
            ]);

            $reservation->parent->update(['stripe_customer_id' => $customer->id]);
        }

        // Créer la facture
        $invoice = $stripe->invoices->create([
            'customer' => $reservation->parent->stripe_customer_id,
            'collection_method' => 'send_invoice',
            'days_until_due' => 30,
            'metadata' => [
                'reservation_id' => $reservation->id,
                'parent_id' => $reservation->parent->id,
                'platform' => 'miminoo'
            ]
        ]);

        // Ajouter l'élément de ligne
        $stripe->invoiceItems->create([
            'customer' => $reservation->parent->stripe_customer_id,
            'invoice' => $invoice->id,
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => 'Service de garde - ' . $reservation->start_date->format('d/m/Y'),
                    'description' => 'Garde de ' . $reservation->duration . 'h avec ' . 
                                   $reservation->babysitter->firstname . ' ' . $reservation->babysitter->lastname,
                ],
                'unit_amount' => $reservation->total_amount * 100, // Convertir en centimes
            ],
            'quantity' => 1,
        ]);

        // Finaliser la facture
        $stripe->invoices->finalizeInvoice($invoice->id);

        return $invoice;
    }
} 