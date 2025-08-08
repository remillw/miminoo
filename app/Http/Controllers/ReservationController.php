<?php

namespace App\Http\Controllers;

use App\Models\AdApplication;
use App\Models\Reservation;
use App\Models\Conversation;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Notifications\ReservationPaid;

class ReservationController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Créer une réservation à partir d'une candidature acceptée
     */
    public function createFromApplication(Request $request, AdApplication $application)
    {
        $user = Auth::user();

        Log::info('=== CRÉATION RÉSERVATION ===', [
            'user_id' => $user->id,
            'application_id' => $application->id,
            'user_role' => $user->roles->first()?->name
        ]);

        // Vérifier que l'utilisateur est le parent de l'annonce
        if ($application->ad->parent_id !== $user->id) {
            Log::error('Accès refusé - utilisateur non autorisé', [
                'user_id' => $user->id,
                'ad_parent_id' => $application->ad->parent_id
            ]);
            abort(403, 'Vous n\'êtes pas autorisé à réserver cette candidature');
        }

        // Vérifier que la candidature peut être réservée
        if (!in_array($application->status, ['pending', 'counter_offered', 'accepted'])) {
            Log::error('Candidature non réservable', [
                'application_id' => $application->id,
                'status' => $application->status
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Cette candidature ne peut plus être réservée'
            ], 400);
        }

        $validated = $request->validate([
            'final_rate' => 'nullable|numeric|min:0|max:999.99'
        ]);

        try {
            DB::beginTransaction();

            // Créer la réservation
            $reservation = Reservation::createFromApplication(
                $application, 
                $validated['final_rate'] ?? null
            );

            Log::info('Réservation créée', [
                'reservation_id' => $reservation->id,
                'total_deposit' => $reservation->total_deposit,
                'hourly_rate' => $reservation->hourly_rate
            ]);

            // Créer le PaymentIntent Stripe
            $paymentIntent = $this->stripeService->createPaymentIntent(
                $reservation->total_deposit * 100, // Convertir en centimes
                'eur',
                0, // Plus d'application fee - gestion différée des frais
                $reservation->babysitter, // Babysitter (pour métadonnées)
                $user // Utilisateur connecté
            );

            // Mettre à jour la réservation avec l'ID du PaymentIntent
            $reservation->update([
                'stripe_payment_intent_id' => $paymentIntent->id
            ]);

            DB::commit();

            Log::info('PaymentIntent créé', [
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount,
                'client_secret' => $paymentIntent->client_secret
            ]);

            // Rediriger vers la page de paiement avec Inertia
            return redirect()->route('reservations.payment', $reservation->id);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de la création de la réservation', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la création de la réservation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmer le paiement d'une réservation
     */
    public function confirmPayment(Request $request, Reservation $reservation)
    {
        $user = Auth::user();

        Log::info('=== CONFIRMATION PAIEMENT ===', [
            'user_id' => $user->id,
            'reservation_id' => $reservation->id,
            'current_status' => $reservation->status
        ]);

        // Vérifier que l'utilisateur est le parent
        if ($reservation->parent_id !== $user->id) {
            abort(403);
        }

        // Vérifier que la réservation est en attente de paiement
        if ($reservation->status !== 'pending_payment') {
            return response()->json([
                'success' => false,
                'error' => 'Cette réservation n\'est plus en attente de paiement'
            ], 400);
        }

        $validated = $request->validate([
            'payment_intent_id' => 'nullable|string',
            'payment_method_id' => 'nullable|string',
            'save_payment_method' => 'boolean'
        ]);

        try {
            $paymentIntent = null;

            if (isset($validated['payment_method_id']) && !empty($validated['payment_method_id'])) {
                // Paiement avec un moyen de paiement sauvegardé
                $paymentIntent = $this->stripeService->createPaymentIntentWithSavedMethod(
                    $reservation->total_deposit * 100,
                    'eur',
                    $validated['payment_method_id'],
                    $user,
                    0, // Plus d'application fee
                    $reservation->babysitter
                );

                if ($paymentIntent->status !== 'succeeded') {
                    Log::error('Paiement avec moyen sauvegardé échoué', [
                        'payment_method_id' => $validated['payment_method_id'],
                        'status' => $paymentIntent->status
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'Le paiement a échoué',
                        'payment_intent' => [
                            'id' => $paymentIntent->id,
                            'status' => $paymentIntent->status
                        ]
                    ], 400);
                }

                // Mettre à jour la réservation avec le nouveau PaymentIntent
                $reservation->update([
                    'stripe_payment_intent_id' => $paymentIntent->id
                ]);

                // Marquer comme payée
                $reservation->markAsPaid($paymentIntent->id);
            } elseif (isset($validated['payment_intent_id']) && !empty($validated['payment_intent_id'])) {
                // Paiement avec nouveau moyen de paiement (via Stripe Elements)
                // Le PaymentIntent a déjà été confirmé côté client, on le récupère juste
                $paymentIntent = $this->stripeService->retrievePaymentIntent($validated['payment_intent_id']);

                if ($paymentIntent->status !== 'succeeded') {
                    Log::error('PaymentIntent non réussi', [
                        'payment_intent_id' => $validated['payment_intent_id'],
                        'status' => $paymentIntent->status
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'Le paiement n\'a pas été confirmé'
                    ], 400);
                }

                // Vérifier que le PaymentIntent correspond bien à cette réservation
                if ($reservation->stripe_payment_intent_id !== $validated['payment_intent_id']) {
                    Log::error('PaymentIntent ne correspond pas à la réservation', [
                        'reservation_payment_intent' => $reservation->stripe_payment_intent_id,
                        'provided_payment_intent' => $validated['payment_intent_id']
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'PaymentIntent invalide pour cette réservation'
                    ], 400);
                }

                // Sauvegarder le moyen de paiement si demandé
                if (isset($validated['save_payment_method']) && $validated['save_payment_method'] && $paymentIntent->payment_method) {
                    $this->stripeService->savePaymentMethod($paymentIntent->payment_method, $user);
                }

                // Marquer la réservation comme payée
                $reservation->markAsPaid($validated['payment_intent_id']);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Aucun moyen de paiement fourni'
                ], 400);
            }

            // Ajouter un message du parent dans la conversation
            if ($reservation->conversation) {
                $reservation->conversation->messages()->create([
                    'sender_id' => $user->id, // Le parent qui vient de payer
                    'message' => "L'acompte de {$reservation->total_deposit}€ a été payé avec succès. La réservation est confirmée !",
                    'type' => 'user',
                    'read_at' => null // Non lu par la babysitter par défaut
                ]);
                
                // Mettre à jour les métadonnées de la conversation
                $reservation->conversation->update([
                    'last_message_at' => now(),
                    'last_message_by' => $user->id
                ]);
            }

            // Notifier la babysitter du paiement
            $reservation->babysitter->notify(new ReservationPaid($reservation));

            Log::info('Réservation confirmée et payée', [
                'reservation_id' => $reservation->id,
                'payment_intent_id' => $validated['payment_intent_id'],
                'babysitter_notified' => true
            ]);

            // Redirection vers la messagerie avec message de succès
            return redirect()->route('messaging.index')->with('success', 'Paiement confirmé ! La réservation est maintenant active.');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la confirmation du paiement', [
                'reservation_id' => $reservation->id,
                'payment_intent_id' => $validated['payment_intent_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la confirmation du paiement'
            ], 500);
        }
    }

    /**
     * Annuler une réservation
     */
    public function cancel(Request $request, Reservation $reservation)
    {
        $user = Auth::user();

        Log::info('=== ANNULATION RÉSERVATION ===', [
            'user_id' => $user->id,
            'reservation_id' => $reservation->id,
            'current_status' => $reservation->status
        ]);

        // Vérifier que l'utilisateur peut annuler
        if ($reservation->parent_id !== $user->id && $reservation->babysitter_id !== $user->id) {
            abort(403);
        }

        // Vérifier que la réservation peut être annulée
        if (!$reservation->can_be_cancelled) {
            return response()->json([
                'success' => false,
                'error' => 'Cette réservation ne peut plus être annulée'
            ], 400);
        }

        $validated = $request->validate([
            'reason' => 'required|in:parent_unavailable,babysitter_unavailable,emergency,other',
            'note' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $isParent = $reservation->parent_id === $user->id;
            $canCancelFree = $reservation->can_be_cancelled_free;

            // Annuler selon qui annule
            if ($isParent) {
                $reservation->cancelByParent($validated['reason'], $validated['note']);
            } else {
                $reservation->cancelByBabysitter($validated['reason'], $validated['note']);
                
                // Si la babysitter annule moins de 24h avant, elle reçoit un mauvais avis automatique
                if ($reservation->shouldReceiveBadReview()) {
                    // TODO: Implémenter le système d'avis automatique
                    Log::info('Babysitter devrait recevoir un mauvais avis', [
                        'reservation_id' => $reservation->id,
                        'babysitter_id' => $reservation->babysitter_id
                    ]);
                }
            }

            // Gérer le remboursement automatique
            $refundAmount = $reservation->getRefundAmount();
            if ($refundAmount > 0 && $reservation->stripe_payment_intent_id) {
                try {
                    $refund = $this->stripeService->createRefundWithBabysitterDeduction(
                        $reservation->stripe_payment_intent_id,
                        $reservation,
                        $validated['reason']
                    );
                    
                    Log::info('Remboursement avec déduction babysitter effectué', [
                        'reservation_id' => $reservation->id,
                        'parent_refund_amount' => $reservation->getParentRefundAmount(),
                        'babysitter_deduction' => $reservation->getBabysitterDeductionAmount(),
                        'refund_id' => $refund?->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Erreur lors du remboursement avec déduction', [
                        'reservation_id' => $reservation->id,
                        'error' => $e->getMessage()
                    ]);
                    // Ne pas faire échouer l'annulation si le remboursement échoue
                }
            }

            // Ajouter un message système dans la conversation
            if ($reservation->conversation) {
                $reservation->conversation->addSystemMessage('reservation_cancelled', [
                    'cancelled_by' => $isParent ? 'parent' : 'babysitter',
                    'cancelled_by_name' => $user->firstname . ' ' . $user->lastname,
                    'reason' => $validated['reason'],
                    'note' => $validated['note'],
                    'penalty' => $reservation->cancellation_penalty,
                    'refund_amount' => $refundAmount
                ]);
            }

            // Envoyer les notifications aux deux parties
            $otherUser = $isParent ? $reservation->babysitter : $reservation->parent;
            $cancelledBy = $isParent ? 'parent' : 'babysitter';
            
            $otherUser->notify(new \App\Notifications\ReservationCancelled(
                $reservation,
                $cancelledBy,
                $validated['reason'],
                $validated['note']
            ));

            DB::commit();

            Log::info('Réservation annulée avec succès', [
                'reservation_id' => $reservation->id,
                'cancelled_by' => $isParent ? 'parent' : 'babysitter',
                'penalty' => $reservation->cancellation_penalty,
                'refund_amount' => $refundAmount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Réservation annulée avec succès',
                'reservation' => [
                    'id' => $reservation->id,
                    'status' => $reservation->status,
                    'cancelled_at' => $reservation->cancelled_at->toISOString(),
                    'cancellation_penalty' => $reservation->cancellation_penalty,
                    'refund_amount' => $refundAmount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de l\'annulation', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de l\'annulation de la réservation'
            ], 500);
        }
    }

    /**
     * Démarrer le service (babysitter arrive)
     */
    public function startService(Reservation $reservation)
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur est la babysitter
        if ($reservation->babysitter_id !== $user->id) {
            abort(403);
        }

        if ($reservation->status !== 'paid') {
            return response()->json([
                'success' => false,
                'error' => 'La réservation doit être payée pour démarrer le service'
            ], 400);
        }

        try {
            $reservation->startService();

            // Ajouter un message système
            if ($reservation->conversation) {
                $reservation->conversation->addSystemMessage('service_started');
            }

            Log::info('Service démarré', [
                'reservation_id' => $reservation->id,
                'babysitter_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Service démarré avec succès',
                'reservation' => [
                    'id' => $reservation->id,
                    'status' => $reservation->status,
                    'service_start_at' => $reservation->service_start_at->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du démarrage du service', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du démarrage du service'
            ], 500);
        }
    }

    /**
     * Terminer le service
     */
    public function completeService(Reservation $reservation)
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur est la babysitter ou le parent
        if ($reservation->babysitter_id !== $user->id && $reservation->parent_id !== $user->id) {
            abort(403);
        }

        if ($reservation->status !== 'active') {
            return response()->json([
                'success' => false,
                'error' => 'Le service doit être actif pour être terminé'
            ], 400);
        }

        try {
            $reservation->completeService();

            // Ajouter un message système
            if ($reservation->conversation) {
                $reservation->conversation->addSystemMessage('service_completed');
            }

            Log::info('Service terminé', [
                'reservation_id' => $reservation->id,
                'completed_by' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Service terminé avec succès. Les fonds seront libérés dans 24h.',
                'reservation' => [
                    'id' => $reservation->id,
                    'status' => $reservation->status,
                    'service_end_at' => $reservation->service_end_at->toISOString(),
                    'funds_released_at' => $reservation->funds_released_at->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la finalisation du service', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la finalisation du service'
            ], 500);
        }
    }

    /**
     * Récupérer les détails d'une réservation
     */
    public function show(Reservation $reservation)
    {
        $user = Auth::user();

        // Vérifier l'accès
        if ($reservation->parent_id !== $user->id && $reservation->babysitter_id !== $user->id) {
            abort(403);
        }

        $reservation->load(['ad', 'application', 'parent', 'babysitter', 'conversation']);

        return response()->json([
            'success' => true,
            'reservation' => [
                'id' => $reservation->id,
                'status' => $reservation->status,
                'hourly_rate' => $reservation->hourly_rate,
                'deposit_amount' => $reservation->deposit_amount,
                'service_fee' => $reservation->service_fee,
                'total_deposit' => $reservation->total_deposit,
                'reserved_at' => $reservation->reserved_at?->toISOString(),
                'payment_due_at' => $reservation->payment_due_at?->toISOString(),
                'paid_at' => $reservation->paid_at?->toISOString(),
                'service_start_at' => $reservation->service_start_at?->toISOString(),
                'service_end_at' => $reservation->service_end_at?->toISOString(),
                'cancelled_at' => $reservation->cancelled_at?->toISOString(),
                'can_be_cancelled' => $reservation->can_be_cancelled,
                'can_be_cancelled_free' => $reservation->can_be_cancelled_free,
                'time_until_service' => $reservation->time_until_service,
                'can_be_reviewed' => $reservation->can_be_reviewed,
                'ad' => [
                    'id' => $reservation->ad->id,
                    'title' => $reservation->ad->title,
                    'date_start' => $reservation->ad->date_start->toISOString(),
                    'date_end' => $reservation->ad->date_end->toISOString(),
                ],
                'parent' => [
                    'id' => $reservation->parent->id,
                    'name' => $reservation->parent->firstname . ' ' . $reservation->parent->lastname,
                    'avatar' => $reservation->parent->avatar
                ],
                'babysitter' => [
                    'id' => $reservation->babysitter->id,
                    'name' => $reservation->babysitter->firstname . ' ' . $reservation->babysitter->lastname,
                    'avatar' => $reservation->babysitter->avatar
                ]
            ]
        ]);
    }

    /**
     * Afficher la page de paiement pour une application (crée la réservation si nécessaire)
     */
    public function showApplicationPaymentPage(AdApplication $application)
    {
        $user = Auth::user();

        Log::info('=== SHOW APPLICATION PAYMENT PAGE ===', [
            'application_id' => $application->id,
            'application_status' => $application->status,
            'user_id' => $user->id,
            'ad_parent_id' => $application->ad->parent_id,
            'user_is_parent' => $application->ad->parent_id === $user->id
        ]);

        // Vérifier que l'utilisateur est le parent de l'annonce
        if ($application->ad->parent_id !== $user->id) {
            abort(403, 'Vous n\'êtes pas autorisé à réserver cette candidature');
        }

        // Vérifier que la candidature peut être réservée
        if (!in_array($application->status, ['pending', 'counter_offered', 'accepted'])) {
            Log::warning('Application status not allowed for reservation', [
                'application_id' => $application->id,
                'status' => $application->status,
                'allowed_statuses' => ['pending', 'counter_offered', 'accepted']
            ]);
            return redirect()->route('messaging.index')->with('error', 'Cette candidature ne peut plus être réservée');
        }

        // Vérifier s'il existe déjà une réservation pour cette application
        $existingReservation = Reservation::where('application_id', $application->id)
            ->where('status', 'pending_payment')
            ->first();

        if ($existingReservation) {
            // Rediriger vers la page de paiement de la réservation existante
            return redirect()->route('reservations.payment', $existingReservation->id);
        }

        // Créer une nouvelle réservation
        try {
            DB::beginTransaction();

            $finalRate = $application->counter_rate ?? $application->proposed_rate;
            $reservation = Reservation::createFromApplication($application, $finalRate);

            // Créer le PaymentIntent Stripe
            $paymentIntent = $this->stripeService->createPaymentIntent(
                $reservation->total_deposit * 100, // Convertir en centimes
                'eur',
                0, // Plus d'application fee - gestion différée des frais
                $reservation->babysitter, // Babysitter (pour métadonnées)
                $user // Utilisateur connecté
            );

            // Mettre à jour la réservation avec l'ID du PaymentIntent
            $reservation->update([
                'stripe_payment_intent_id' => $paymentIntent->id
            ]);

            DB::commit();

            // Rediriger vers la page de paiement de la nouvelle réservation
            return redirect()->route('reservations.payment', $reservation->id);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de la création de la réservation depuis application', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('messaging.index')->with('error', 'Erreur lors de la création de la réservation');
        }
    }

    /**
     * Afficher la page de paiement pour une réservation
     */
    public function showPaymentPage(Reservation $reservation)
    {
        $user = Auth::user();

        // Vérifier l'accès
        if ($reservation->parent_id !== $user->id) {
            abort(403);
        }

        // Vérifier que la réservation est en attente de paiement
        if ($reservation->status !== 'pending_payment') {
            return redirect()->route('messaging.index')->with('error', 'Cette réservation n\'est plus en attente de paiement');
        }

        // Charger les relations nécessaires
        $reservation->load(['ad', 'application', 'babysitter']);

        // Récupérer les moyens de paiement sauvegardés
        $savedPaymentMethods = [];
        if ($user->stripe_customer_id) {
            try {
                $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                $paymentMethods = $stripe->paymentMethods->all([
                    'customer' => $user->stripe_customer_id,
                    'type' => 'card',
                ]);
                $savedPaymentMethods = $paymentMethods->data;
            } catch (\Exception $e) {
                Log::warning('Erreur récupération moyens de paiement', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Récupérer le client secret du PaymentIntent
        $clientSecret = null;
        if ($reservation->stripe_payment_intent_id) {
            try {
                $paymentIntent = $this->stripeService->retrievePaymentIntent($reservation->stripe_payment_intent_id);
                $clientSecret = $paymentIntent->client_secret;
            } catch (\Exception $e) {
                Log::warning('Impossible de récupérer le client secret', [
                    'reservation_id' => $reservation->id,
                    'payment_intent_id' => $reservation->stripe_payment_intent_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return Inertia::render('Reservations/Payment', [
            'reservation' => [
                'id' => $reservation->id,
                'status' => $reservation->status,
                'hourly_rate' => $reservation->hourly_rate,
                'deposit_amount' => $reservation->deposit_amount,
                'service_fee' => $reservation->service_fee,
                'stripe_fee' => $reservation->stripe_fee,
                'platform_fee' => $reservation->platform_fee,
                'babysitter_amount' => $reservation->babysitter_amount,
                'total_deposit' => $reservation->total_deposit,
                'payment_due_at' => $reservation->payment_due_at?->toISOString(),
                'stripe_payment_intent_id' => $reservation->stripe_payment_intent_id,
                'client_secret' => $clientSecret,
                'ad' => [
                    'id' => $reservation->ad->id,
                    'title' => $reservation->ad->title,
                    'date_start' => $reservation->ad->date_start->toISOString(),
                    'date_end' => $reservation->ad->date_end->toISOString(),
                ],
                'babysitter' => [
                    'id' => $reservation->babysitter->id,
                    'name' => $reservation->babysitter->firstname . ' ' . $reservation->babysitter->lastname,
                    'avatar' => $reservation->babysitter->avatar
                ]
            ],
            'savedPaymentMethods' => $savedPaymentMethods,
            'stripePublishableKey' => config('services.stripe.key')
        ]);
    }

    // Méthode getPaymentIntent supprimée - client secret passé directement via Inertia props
}
