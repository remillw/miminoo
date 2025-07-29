<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Notifications\FundsReleasedNotification;
use App\Notifications\ReviewRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = config('services.stripe.webhook_secret');
        
        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Webhook payload invalide', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Webhook signature invalide', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }
        
        Log::info('Webhook Stripe reçu', [
            'type' => $event->type,
            'id' => $event->id
        ]);
        
        // Gérer les différents types d'événements
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;
                
            case 'transfer.created':
                $this->handleTransferCreated($event->data->object);
                break;
                
            case 'payout.paid':
                $this->handlePayoutPaid($event->data->object);
                break;
                
            case 'identity.verification_session.verified':
                $this->handleIdentityVerificationVerified($event->data->object);
                break;
                
            case 'identity.verification_session.requires_input':
                $this->handleIdentityVerificationRequiresInput($event->data->object);
                break;
                
            default:
                Log::info('Type d\'événement webhook non géré', ['type' => $event->type]);
        }
        
        return response('Webhook handled', 200);
    }
    
    /**
     * Gérer le succès d'un paiement
     */
    private function handlePaymentSucceeded($paymentIntent)
    {
        Log::info('Paiement réussi', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount
        ]);
        
        // Trouver la réservation correspondante
        $reservation = Reservation::where('stripe_payment_intent_id', $paymentIntent->id)->first();
        
        if (!$reservation) {
            Log::warning('Réservation non trouvée pour le payment intent', [
                'payment_intent_id' => $paymentIntent->id
            ]);
            return;
        }
        
        // Programmer la libération des fonds dans 24h
        $this->scheduleFundsRelease($reservation);
    }
    
    /**
     * Gérer la création d'un transfer
     */
    private function handleTransferCreated($transfer)
    {
        Log::info('Transfer créé', [
            'transfer_id' => $transfer->id,
            'amount' => $transfer->amount,
            'destination' => $transfer->destination
        ]);
        
        // Mettre à jour la réservation avec l'ID du transfer
        $reservation = Reservation::where('stripe_payment_intent_id', $transfer->source_transaction)->first();
        
        if ($reservation) {
            $reservation->update(['stripe_transfer_id' => $transfer->id]);
            
            // Notifier la babysitter que ses fonds ont été libérés
            $reservation->babysitter->notify(new FundsReleasedNotification($reservation));
            
            Log::info('Babysitter notifiée de la libération des fonds', [
                'reservation_id' => $reservation->id,
                'babysitter_id' => $reservation->babysitter_id,
                'amount' => $reservation->babysitter_amount
            ]);
        }
    }
    
    /**
     * Gérer un payout payé
     */
    private function handlePayoutPaid($payout)
    {
        Log::info('Payout payé', [
            'payout_id' => $payout->id,
            'amount' => $payout->amount,
            'account' => $payout->account ?? 'main'
        ]);
    }
    
    /**
     * Programmer la libération des fonds
     */
    private function scheduleFundsRelease(Reservation $reservation)
    {
        // Vérifier s'il n'y a pas de réclamation en cours
        $hasActiveDispute = $reservation->disputes()
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();
            
        if ($hasActiveDispute) {
            Log::info('Libération des fonds suspendue - réclamation en cours', [
                'reservation_id' => $reservation->id
            ]);
            return;
        }
        
        // Programmer la libération dans 24h
        dispatch(function () use ($reservation) {
            $this->releaseFunds($reservation);
        })->delay(now()->addHours(24));
        
        Log::info('Libération des fonds programmée dans 24h', [
            'reservation_id' => $reservation->id,
            'release_at' => now()->addHours(24)
        ]);
    }
    
    /**
     * Libérer les fonds vers la babysitter
     */
    private function releaseFunds(Reservation $reservation)
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            
            // Vérifier à nouveau s'il n'y a pas de réclamation
            $hasActiveDispute = $reservation->disputes()
                ->whereIn('status', ['pending', 'in_progress'])
                ->exists();
                
            if ($hasActiveDispute) {
                Log::info('Libération des fonds annulée - réclamation créée', [
                    'reservation_id' => $reservation->id
                ]);
                return;
            }
            
            // Créer le transfer vers la babysitter
            $transfer = \Stripe\Transfer::create([
                'amount' => $reservation->babysitter_amount * 100, // En centimes
                'currency' => 'eur',
                'destination' => $reservation->babysitter->stripe_account_id,
                'source_transaction' => $reservation->stripe_payment_intent_id,
                'metadata' => [
                    'reservation_id' => $reservation->id,
                    'type' => 'babysitter_payment'
                ]
            ]);
            
            // Mettre à jour la réservation
            $reservation->update([
                'stripe_transfer_id' => $transfer->id,
                'funds_released_at' => now()
            ]);
            
            // Envoyer les demandes d'avis
            $this->sendReviewRequests($reservation);
            
            Log::info('Fonds libérés avec succès', [
                'reservation_id' => $reservation->id,
                'transfer_id' => $transfer->id,
                'amount' => $reservation->babysitter_amount
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la libération des fonds', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Envoyer les demandes d'avis
     */
    private function sendReviewRequests(Reservation $reservation)
    {
        try {
            // Demander un avis au parent
            if (!$reservation->parent_reviewed) {
                $reservation->parent->notify(new ReviewRequestNotification($reservation, 'parent'));
            }
            
            // Demander un avis à la babysitter
            if (!$reservation->babysitter_reviewed) {
                $reservation->babysitter->notify(new ReviewRequestNotification($reservation, 'babysitter'));
            }
            
            Log::info('Demandes d\'avis envoyées', [
                'reservation_id' => $reservation->id,
                'parent_notified' => !$reservation->parent_reviewed,
                'babysitter_notified' => !$reservation->babysitter_reviewed
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi des demandes d\'avis', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Gérer la vérification d'identité réussie
     */
    private function handleIdentityVerificationVerified($verificationSession)
    {
        Log::info('Vérification Identity réussie', [
            'session_id' => $verificationSession->id,
            'status' => $verificationSession->status
        ]);
        
        // Trouver l'utilisateur avec cette session
        $user = \App\Models\User::where('stripe_identity_session_id', $verificationSession->id)->first();
        
        if (!$user) {
            Log::warning('Utilisateur non trouvé pour la session Identity', [
                'session_id' => $verificationSession->id
            ]);
            return;
        }
        
        try {
            // Marquer la vérification comme réussie
            $user->update([
                'identity_verified_at' => now(),
                'identity_verification_status' => 'verified'
            ]);
            
            // Optionnel : lier automatiquement au compte Connect si nécessaire
            if ($user->stripe_account_id) {
                app(\App\Services\StripeService::class)->linkIdentityToConnect($user);
            }
            
            // Envoyer une notification de confirmation
            $user->notify(new \App\Notifications\IdentityVerificationSuccessNotification());
            
            Log::info('Utilisateur marqué comme vérifié', [
                'user_id' => $user->id,
                'session_id' => $verificationSession->id
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement de la vérification réussie', [
                'user_id' => $user->id,
                'session_id' => $verificationSession->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Gérer l'échec de la vérification d'identité
     */
    private function handleIdentityVerificationRequiresInput($verificationSession)
    {
        Log::info('Vérification Identity échouée - input requis', [
            'session_id' => $verificationSession->id,
            'status' => $verificationSession->status,
            'last_error' => $verificationSession->last_error
        ]);
        
        // Trouver l'utilisateur avec cette session
        $user = \App\Models\User::where('stripe_identity_session_id', $verificationSession->id)->first();
        
        if (!$user) {
            Log::warning('Utilisateur non trouvé pour la session Identity', [
                'session_id' => $verificationSession->id
            ]);
            return;
        }
        
        try {
            // Marquer la vérification comme ayant échoué
            $user->update([
                'identity_verification_status' => 'requires_input',
                'identity_verification_error' => $verificationSession->last_error ? json_encode($verificationSession->last_error) : null
            ]);
            
            // Envoyer une notification d'échec avec instructions pour recommencer
            $user->notify(new \App\Notifications\IdentityVerificationFailedNotification($verificationSession->last_error));
            
            Log::info('Utilisateur notifié de l\'échec de vérification', [
                'user_id' => $user->id,
                'session_id' => $verificationSession->id,
                'error' => $verificationSession->last_error
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement de l\'échec de vérification', [
                'user_id' => $user->id,
                'session_id' => $verificationSession->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
