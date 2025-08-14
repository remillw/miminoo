<?php

namespace App\Http\Controllers;

use App\Models\AdApplication;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use App\Events\UserTyping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MessagingController extends Controller
{
    /**
     * Affiche la liste des conversations (qui incluent maintenant les candidatures)
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        
        // Déterminer le mode par défaut selon les rôles de l'utilisateur
        $defaultMode = $user->hasRole('parent') ? 'parent' : 'babysitter';
        $requestedMode = $request->get('mode', $defaultMode);
        
        // Valider que l'utilisateur a bien le rôle demandé
        if ($requestedMode === 'parent' && !$user->hasRole('parent')) {
            $requestedMode = 'babysitter';
        } elseif ($requestedMode === 'babysitter' && !$user->hasRole('babysitter')) {
            $requestedMode = 'parent';
        }
        
        \Log::info('=== CHARGEMENT CONVERSATIONS ===', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'requested_mode' => $requestedMode
        ]);
        
        try {
            // Démarrer la requête de base
            $conversationsQuery = Conversation::with([
                'ad', // Charger toutes les données de l'annonce pour le slug
                'parent:id,firstname,lastname,avatar',
                'babysitter:id,firstname,lastname,avatar',
                'application' => function($query) {
                    $query->with(['babysitter:id,firstname,lastname,avatar', 'ad.parent:id,firstname,lastname,avatar']);
                },
                'reservation' => function($query) {
                    $query->select('id', 'conversation_id', 'ad_id', 'status', 'service_start_at', 'service_end_at', 'total_deposit', 'deposit_amount')
                          ->with('ad:id,title,date_start,date_end');
                }
            ]);

            // Filtrer selon le mode demandé
            if ($requestedMode === 'parent') {
                // En mode parent : voir uniquement les candidatures à MES annonces
                $conversationsQuery->where('parent_id', $user->id);
            } elseif ($requestedMode === 'babysitter') {
                // En mode babysitter : voir uniquement les candidatures que J'AI faites
                $conversationsQuery->where('babysitter_id', $user->id);
            } else {
                // Mode par défaut : toutes les conversations de l'utilisateur
                $conversationsQuery->forUser($user->id);
            }
            
            $conversations = $conversationsQuery
                ->where('status', '!=', 'archived') // Excluer uniquement les conversations archivées
                ->where(function($query) {
                    // Inclure toutes les conversations avec applications (même annulées) pour pouvoir les archiver
                    $query->whereDoesntHave('application')
                          ->orWhereHas('application'); // Pas de restriction sur le status de l'application
                })
                ->orderByDesc('last_message_at')
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->get();

            \Log::info('Conversations brutes récupérées', [
                'conversations_count' => $conversations->count(),
                'conversations_ids' => $conversations->pluck('id')->toArray(),
                'conversations_status' => $conversations->pluck('status')->toArray(),
                'filtered_by_mode' => $requestedMode
            ]);

            $conversationsFormatted = $conversations->map(function ($conversation) use ($user) {
                try {
                    \Log::info('Formatage conversation', [
                        'conversation_id' => $conversation->id,
                        'status' => $conversation->status,
                        'parent_id' => $conversation->parent_id,
                        'babysitter_id' => $conversation->babysitter_id
                    ]);

                    $otherUser = $conversation->getOtherUser($user->id);
                    $application = $conversation->application;
                    
                    \Log::info('Données utilisateur et application', [
                        'other_user_id' => $otherUser->id,
                        'other_user_name' => $otherUser->firstname . ' ' . substr($otherUser->lastname, 0, 1) . '.',
                        'has_application' => $application ? true : false,
                        'application_id' => $application?->id
                    ]);
                    
                    // Récupérer le dernier message directement avec une requête simple
                    $lastMessage = \DB::table('messages')
                        ->where('conversation_id', $conversation->id)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    \Log::info('Dernier message récupéré', [
                        'conversation_id' => $conversation->id,
                        'has_last_message' => $lastMessage ? true : false,
                        'last_message_id' => $lastMessage?->id ?? 'N/A',
                        'last_message_preview' => $lastMessage ? substr($lastMessage->message, 0, 50) . '...' : 'Aucun message'
                    ]);
                    
                    $conversationData = [
                        'id' => $conversation->id,
                        'type' => $conversation->status === 'pending' ? 'application' : 'conversation',
                        'ad_title' => $conversation->ad->title ?? 'Annonce supprimée',
                        'ad_date' => $conversation->ad ? $conversation->ad->date_start->format('d/m/Y') : 'Date inconnue',
                        'ad' => $conversation->ad ? [
                            'id' => $conversation->ad->id,
                            'title' => $conversation->ad->title,
                            'date_start' => $conversation->ad->date_start,
                            'date_end' => $conversation->ad->date_end,
                        ] : null,
                        'other_user' => [
                            'id' => $otherUser->id,
                            'name' => $otherUser->firstname . ' ' . substr($otherUser->lastname, 0, 1) . '.',
                            'firstname' => $otherUser->firstname,
                            'lastname' => $otherUser->lastname,
                            'avatar' => $otherUser->avatar ?? '/default-avatar.svg'
                        ],
                        'last_message' => $lastMessage?->message ?? 'La conversation a commencé !',
                        'last_message_at' => $conversation->last_message_at ?? $conversation->created_at,
                        'unread_count' => $conversation->unread_count, // Utiliser l'attribut du modèle
                        'status' => $conversation->status,
                        'can_chat' => true // Maintenant on peut toujours chatter
                    ];

                    // Ajouter les données de candidature si elle existe
                    if ($application) {
                        \Log::info('Ajout des données de candidature', [
                            'application_status' => $application->status,
                            'proposed_rate' => $application->proposed_rate,
                            'counter_rate' => $application->counter_rate
                        ]);

                        $conversationData['application'] = [
                            'id' => $application->id,
                            'motivation_note' => $application->motivation_note,
                            'proposed_rate' => $application->proposed_rate,
                            'counter_rate' => $application->counter_rate,
                            'status' => $application->status,
                            'viewed_at' => $application->viewed_at,
                            'created_at' => $application->created_at,
                            'conversation' => [
                                'id' => $conversation->id,
                                'status' => $conversation->status
                            ],
                            'ad' => $conversation->ad ? [
                                'id' => $conversation->ad->id,
                                'title' => $conversation->ad->title,
                                'date_start' => $conversation->ad->date_start,
                                'date_end' => $conversation->ad->date_end,
                            ] : null,
                        ];

                        // Ajouter les infos user selon le rôle
                        if ($user->hasRole('parent') && $application->babysitter) {
                            $conversationData['application']['babysitter'] = [
                                'id' => $application->babysitter->id,
                                'name' => $application->babysitter->firstname . ' ' . substr($application->babysitter->lastname, 0, 1) . '.',
                                'avatar' => $application->babysitter->avatar ?? '/default-avatar.svg'
                            ];
                        } elseif ($user->hasRole('babysitter') && $application->ad && $application->ad->parent) {
                            $conversationData['application']['parent'] = [
                                'id' => $application->ad->parent->id,
                                'name' => $application->ad->parent->firstname . ' ' . substr($application->ad->parent->lastname, 0, 1) . '.',
                                'avatar' => $application->ad->parent->avatar ?? '/default-avatar.svg'
                            ];
                        }
                    } else {
                        $conversationData['application'] = null;
                    }

                    // Ajouter les données de réservation si elle existe
                    if ($conversation->reservation) {
                        $conversationData['reservation'] = [
                            'id' => $conversation->reservation->id,
                            'status' => $conversation->reservation->status,
                            'service_start_at' => $conversation->reservation->service_start_at,
                            'service_end_at' => $conversation->reservation->service_end_at,
                            'total_deposit' => $conversation->reservation->total_deposit,
                            'deposit_amount' => $conversation->reservation->deposit_amount,
                            'can_be_cancelled' => $conversation->reservation->can_be_cancelled,
                            'can_be_cancelled_free' => $conversation->reservation->can_be_cancelled_free,
                            'ad' => $conversation->reservation->ad ? [
                                'id' => $conversation->reservation->ad->id,
                                'title' => $conversation->reservation->ad->title,
                                'date_start' => $conversation->reservation->ad->date_start,
                                'date_end' => $conversation->reservation->ad->date_end,
                            ] : null
                        ];
                    } else {
                        $conversationData['reservation'] = null;
                    }
                    
                    \Log::info('Conversation formatée avec succès', [
                        'conversation_id' => $conversation->id,
                        'type' => $conversationData['type'],
                        'has_application_data' => $conversationData['application'] ? true : false,
                        'has_reservation_data' => $conversationData['reservation'] ? true : false
                    ]);
                    
                    return $conversationData;
                } catch (\Exception $e) {
                    // En cas d'erreur, ignorer cette conversation ou retourner des données minimales
                    \Log::error('Erreur lors du formatage de la conversation', [
                        'conversation_id' => $conversation->id,
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]);
                    return null;
                }
            })
            ->filter() // Enlever les éléments null
            ->values(); // Réindexer le tableau

            \Log::info('Conversations formatées', [
                'formatted_count' => $conversationsFormatted->count(),
                'formatted_types' => $conversationsFormatted->pluck('type')->toArray()
            ]);

            $responseData = [
                'conversations' => $conversationsFormatted,
                'userRole' => $user->roles->first()->name ?? 'user',
                'hasParentRole' => $user->hasRole('parent'),
                'hasBabysitterRole' => $user->hasRole('babysitter'),
                'requestedMode' => $requestedMode,
                'currentMode' => $requestedMode
            ];

            \Log::info('Réponse index préparée', [
                'conversations_count' => count($responseData['conversations']),
                'user_role' => $responseData['userRole'],
                'has_parent_role' => $responseData['hasParentRole'],
                'has_babysitter_role' => $responseData['hasBabysitterRole']
            ]);

            return Inertia::render('Messaging/Index', $responseData);

        } catch (\Exception $e) {
            \Log::error('ERREUR CRITIQUE lors du chargement des conversations', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Retourner une réponse d'erreur gracieuse
            return Inertia::render('Messaging/Index', [
                'conversations' => [],
                'userRole' => $user->roles->first()->name ?? 'user',
                'hasParentRole' => $user->hasRole('parent'),
                'hasBabysitterRole' => $user->hasRole('babysitter'),
                'requestedMode' => $requestedMode,
                'currentMode' => $requestedMode,
                'error' => 'Erreur lors du chargement des conversations'
            ]);
        }
    }

    /**
     * Marque une candidature comme vue (côté parent)
     */
    public function markApplicationAsViewed(AdApplication $application)
    {
        $user = Auth::user();
        
        // Charger la relation ad si elle n'est pas déjà chargée
        $application->load('ad');
        
        \Log::info('=== MARQUAGE CANDIDATURE COMME VUE ===', [
            'user_id' => $user->id,
            'application_id' => $application->id,
            'application_ad_id' => $application->ad?->id,
            'application_ad_parent_id' => $application->ad?->parent_id,
            'application_status' => $application->status,
            'viewed_at' => $application->viewed_at,
            'user_has_parent_role' => $user->hasRole('parent'),
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'ad_loaded' => isset($application->ad)
        ]);

        // Vérifier que l'annonce existe
        if (!$application->ad) {
            \Log::error('APPLICATION SANS ANNONCE', [
                'application_id' => $application->id
            ]);
            abort(404, 'Annonce introuvable pour cette candidature');
        }

        // Vérifier que l'utilisateur peut voir cette candidature
        if ($application->ad->parent_id !== $user->id) {
            \Log::error('ACCESS DENIED - Utilisateur non autorisé pour marquer cette candidature', [
                'user_id' => $user->id,
                'application_id' => $application->id,
                'expected_parent_id' => $application->ad->parent_id,
                'actual_user_id' => $user->id
            ]);
            abort(403, 'Vous n\'êtes pas autorisé à voir cette candidature');
        }

        // Vérifier si la candidature n'est pas déjà marquée comme vue
        if ($application->viewed_at) {
            \Log::info('Candidature déjà marquée comme vue', [
                'application_id' => $application->id,
                'viewed_at' => $application->viewed_at
            ]);
            return back()->with([
                'success' => true, 
                'message' => 'Déjà marquée comme vue'
            ]);
        }

        \Log::info('Marquage autorisé, traitement en cours...');

        $application->markAsViewed();

        \Log::info('Candidature marquée comme vue avec succès', [
            'application_id' => $application->id,
            'viewed_at' => $application->fresh()->viewed_at
        ]);

        return back()->with([
            'success' => true,
            'message' => 'Candidature marquée comme vue'
        ]);
    }

    /**
     * Réserver une candidature (nouveau système)
     */
    public function reserveApplication(Request $request, AdApplication $application)
    {
        // Vérifier que l'utilisateur peut accepter cette candidature
        if ($application->ad->parent_id !== Auth::id()) {
            abort(403);
        }

        if ($application->status !== 'pending') {
            return back()->withErrors(['error' => 'Cette candidature ne peut plus être acceptée']);
        }

        // Accepter définitivement au prix proposé par la babysitter - pas de négociation
        $conversation = $application->accept();

        return back()->with([
            'success' => true,
            'message' => 'Candidature acceptée au prix de ' . $application->proposed_rate . '€/h ! Procédez au paiement pour confirmer.'
        ]);
    }

    /**
     * Refuser une candidature (archive la conversation)
     */
    public function declineApplication(AdApplication $application)
    {
        // Vérifier que l'utilisateur peut refuser cette candidature
        if ($application->ad->parent_id !== Auth::id()) {
            abort(403);
        }

        $application->decline();

        return back()->with([
            'success' => true,
            'message' => 'Candidature refusée. La conversation a été archivée.'
        ]);
    }

    // Méthode counterOffer supprimée - plus de contre-offres

    // Méthode respondToCounterOffer supprimée - plus de contre-offres

    // Méthode babysitterCounterOffer supprimée - plus de contre-offres

    /**
     * Annuler une candidature (côté babysitter)
     */
    public function cancelApplication(AdApplication $application)
    {
        // Vérifier que l'utilisateur peut annuler cette candidature
        if ($application->babysitter_id !== Auth::id()) {
            abort(403);
        }

        // Vérifier que la candidature peut être annulée
        $allowedStatuses = ['pending', 'counter_offered', 'accepted'];
        if (!in_array($application->status, $allowedStatuses)) {
            return back()->withErrors(['error' => 'Cette candidature ne peut plus être annulée']);
        }

        $conversation = $application->conversation;
        $isPaid = $conversation && $conversation->status === 'active';
        $message = 'Votre candidature a été annulée avec succès.';

        if ($isPaid) {
            // Candidature payée - la babysitter a déjà reçu les fonds via Stripe Connect
            // Mais elle va les perdre et ils seront renvoyés au parent
            $reservation = $conversation->reservation;
            
            if ($reservation && $reservation->service_start_at) {
                $hoursBefore = now()->diffInHours($reservation->service_start_at, false);
                
                if ($hoursBefore < 48) {
                    // Annulation dans les 48h - avis négatif + babysitter perd les fonds
                    $this->generateAutomaticBadReview($application);
                    $this->refundParentFromBabysitterFunds($reservation);
                    
                    $message = 'Candidature annulée. Un avis négatif a été généré automatiquement car l\'annulation est dans les 48h. Vous perdez les fonds qui seront renvoyés intégralement au parent. La plateforme couvre les frais de remboursement.';
                } else {
                    // Annulation avec plus de 48h d'avance - babysitter perd quand même les fonds
                    $this->refundParentFromBabysitterFunds($reservation);
                    $message = 'Candidature annulée avec succès. Les fonds seront renvoyés intégralement au parent. La plateforme couvre les frais de remboursement.';
                }
            }
        } else {
            // Candidature non payée - annulation sans frais
            $message = 'Candidature annulée sans frais.';
        }

        $application->update(['status' => 'cancelled']);
        
        // Archiver la conversation
        if ($conversation) {
            $conversation->update(['status' => 'archived']);
        }

        return back()->with([
            'success' => true,
            'message' => $message
        ]);
    }

    /**
     * Générer un avis négatif automatique pour annulation tardive
     */
    private function generateAutomaticBadReview(AdApplication $application)
    {
        try {
            $reservation = $application->conversation?->reservation;
            
            if (!$reservation) {
                \Log::warning('Impossible de générer un avis automatique - pas de réservation', [
                    'application_id' => $application->id
                ]);
                return;
            }

            // Créer un avis automatique négatif (1 étoile)
            \App\Models\Review::create([
                'reviewer_id' => $application->ad->parent_id, // Le parent laisse l'avis
                'reviewed_id' => $application->babysitter_id, // La babysitter est évaluée
                'reservation_id' => $reservation->id,
                'role' => 'parent', // L'avis vient du parent
                'rating' => 1, // Note minimale
                'comment' => '⚠️ Avis automatique : Cette babysitter a annulé sa candidature moins de 48h avant le service. Ceci peut causer des désagréments importants aux familles.',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Marquer la réservation comme ayant reçu un avis du parent
            $reservation->update(['parent_reviewed' => true]);

            \Log::info('Avis négatif automatique généré avec succès', [
                'application_id' => $application->id,
                'babysitter_id' => $application->babysitter_id,
                'reservation_id' => $reservation->id,
                'reason' => 'Annulation dans les 48h'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la génération d\'avis automatique', [
                'application_id' => $application->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Rembourser le parent en récupérant les fonds chez la babysitter
     * (Utilisé quand la babysitter annule et doit perdre les fonds)
     */
    private function refundParentFromBabysitterFunds($reservation)
    {
        try {
            if (!$reservation->stripe_payment_intent_id) {
                \Log::warning('Impossible de rembourser le parent - pas de payment_intent_id', [
                    'reservation_id' => $reservation->id
                ]);
                return;
            }

            // Utiliser le service Stripe pour créer le remboursement
            // La PLATEFORME couvre les frais de remboursement quand la babysitter annule
            $stripeService = app(\App\Services\StripeService::class);
            
            // Créer un remboursement complet via Stripe (PLATEFORME couvre les frais)
            $refund = $stripeService->createRefundPlatformCoversfees(
                $reservation->stripe_payment_intent_id,
                'Annulation babysitter - Plateforme couvre les frais de remboursement'
            );

            // Mettre à jour la réservation
            $reservation->update([
                'status' => 'refunded_babysitter_penalty',
                'refund_stripe_id' => $refund->id,
                'refunded_at' => now()
            ]);

            \Log::info('Remboursement parent avec plateforme couvrant les frais', [
                'reservation_id' => $reservation->id,
                'refund_id' => $refund->id,
                'amount' => $reservation->total_deposit,
                'payment_intent_id' => $reservation->stripe_payment_intent_id,
                'note' => 'Plateforme couvre les frais de remboursement - Annulation babysitter'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors du remboursement avec plateforme couvrant les frais', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
            
            // En cas d'erreur Stripe, au moins marquer comme à rembourser manuellement
            $reservation->update([
                'status' => 'babysitter_refund_pending',
                'refund_error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Annuler une réservation (côté parent)
     */
    public function cancelReservationByParent(AdApplication $application)
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur peut annuler cette réservation
        if ($application->ad->parent_id !== $user->id) {
            abort(403);
        }

        // Vérifier que la candidature est dans un état annulable
        $allowedStatuses = ['accepted'];
        if (!in_array($application->status, $allowedStatuses)) {
            return back()->withErrors(['error' => 'Cette réservation ne peut plus être annulée']);
        }

        $conversation = $application->conversation;
        $reservation = $conversation?->reservation;
        $message = 'Réservation annulée avec succès.';

        if ($reservation && $reservation->service_start_at) {
            $hoursBefore = now()->diffInHours($reservation->service_start_at, false);
            
            if ($hoursBefore < 24) {
                // Annulation dans les 24h - AUCUN remboursement, les fonds restent chez la babysitter
                $message = 'Réservation annulée. Votre acompte est définitivement perdu car l\'annulation est dans les 24h. Les fonds restent chez la babysitter.';
                
                // Marquer comme annulé mais pas de remboursement
                $reservation->update([
                    'status' => 'cancelled_by_parent_late',
                    'cancelled_at' => now()
                ]);
                
            } else {
                // Annulation avec plus de 24h d'avance - remboursement avec frais à la charge du parent
                $this->refundParentWithFees($reservation);
                $platformFee = 2.00;
                $estimatedRefund = max(0, $reservation->total_deposit - $platformFee);
                $message = "Réservation annulée avec succès. Vous serez remboursé {$estimatedRefund}€ (montant moins 2€ de frais de service). Les frais de remboursement Stripe restent à votre charge.";
            }
        } else {
            // Pas de réservation créée encore - annulation sans frais
            $message = 'Réservation annulée sans frais.';
        }

        $application->update(['status' => 'cancelled']);
        
        // Archiver la conversation
        if ($conversation) {
            $conversation->update(['status' => 'archived']);
        }

        return back()->with([
            'success' => true,
            'message' => $message
        ]);
    }

    /**
     * Envoyer un message dans une conversation
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $user = Auth::user();
        
        \Log::info('=== ENVOI MESSAGE ===', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'conversation_id' => $conversation->id,
            'conversation_status' => $conversation->status,
            'request_data' => $request->all()
        ]);
        
        // Vérifier que l'utilisateur fait partie de cette conversation
        if ($conversation->parent_id !== $user->id && $conversation->babysitter_id !== $user->id) {
            \Log::error('ACCESS DENIED - Utilisateur non autorisé pour cette conversation', [
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
                'parent_id' => $conversation->parent_id,
                'babysitter_id' => $conversation->babysitter_id
            ]);
            abort(403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        \Log::info('Validation réussie', [
            'message_length' => strlen($validated['message']),
            'message_preview' => substr($validated['message'], 0, 50) . '...'
        ]);

        try {
            // Créer le message
            $message = $conversation->messages()->create([
                'sender_id' => $user->id,
                'message' => $validated['message'],
                'type' => 'user'
            ]);

            \Log::info('Message créé en base', [
                'message_id' => $message->id,
                'sender_id' => $message->sender_id,
                'conversation_id' => $message->conversation_id,
                'message_type' => $message->type
            ]);

            // Mettre à jour la conversation
            $conversation->update([
                'last_message_at' => now(),
                'last_message_by' => $user->id
            ]);

            \Log::info('Conversation mise à jour', [
                'conversation_id' => $conversation->id,
                'last_message_at' => $conversation->last_message_at,
                'last_message_by' => $conversation->last_message_by
            ]);

            // Charger les relations nécessaires
            $message->load('sender');

            \Log::info('Relations chargées', [
                'sender_loaded' => $message->sender ? true : false,
                'sender_name' => $message->sender ? $message->sender->name : 'N/A'
            ]);

            // Diffuser l'événement en temps réel
            try {
                $event = new MessageSent($message, $user);
                \Log::info('Création événement MessageSent', [
                    'event_class' => get_class($event),
                    'message_id' => $message->id,
                    'sender_id' => $user->id,
                    'conversation_id' => $message->conversation_id,
                    'broadcast_on' => $event->broadcastOn(),
                    'broadcast_as' => $event->broadcastAs(),
                    'broadcast_data' => $event->broadcastWith()
                ]);
                
                broadcast($event)->toOthers();
                \Log::info('Événement broadcast envoyé avec succès via Reverb');
            } catch (\Exception $e) {
                \Log::error('Erreur lors du broadcast', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            $responseData = [
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'type' => $message->type,
                    'sender_id' => $message->sender_id,
                    'conversation_id' => $message->conversation_id,
                    'created_at' => $message->created_at->toISOString(),
                    'sender' => [
                        'id' => $user->id,
                        'name' => $user->firstname . ' ' . $user->lastname,
                        'avatar' => '/default-avatar.svg', // Avatar par défaut
                    ],
                ]
            ];

            \Log::info('Réponse préparée', [
                'response_structure' => array_keys($responseData),
                'message_data_keys' => array_keys($responseData['message'])
            ]);

            return response()->json($responseData);

        } catch (\Exception $e) {
            \Log::error('ERREUR lors de la création du message', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de l\'envoi du message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les messages d'une conversation
     */
    public function getMessages(Conversation $conversation)
    {
        $user = Auth::user();
        
        \Log::info('=== RÉCUPÉRATION MESSAGES ===', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'conversation_id' => $conversation->id,
            'conversation_status' => $conversation->status
        ]);
        
        // Vérifier que l'utilisateur fait partie de cette conversation
        if ($conversation->parent_id !== $user->id && $conversation->babysitter_id !== $user->id) {
            \Log::error('ACCESS DENIED - Utilisateur non autorisé pour récupérer les messages', [
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
                'parent_id' => $conversation->parent_id,
                'babysitter_id' => $conversation->babysitter_id
            ]);
            abort(403);
        }

        try {
            // Charger les relations nécessaires si elles ne sont pas déjà chargées
            if (!$conversation->relationLoaded('ad')) {
                $conversation->load('ad');
            }
            if (!$conversation->relationLoaded('reservation')) {
                $conversation->load(['reservation.ad']);
            }
            
            $messages = $conversation->messages()
                ->with('sender:id,firstname,lastname,avatar')
                ->orderBy('created_at', 'asc')
                ->get();

            \Log::info('Messages récupérés de la base', [
                'messages_count' => $messages->count(),
                'first_message_id' => $messages->first()?->id,
                'last_message_id' => $messages->last()?->id
            ]);

            $messagesFormatted = $messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'type' => $message->type,
                    'sender_id' => $message->sender_id,
                    'conversation_id' => $message->conversation_id,
                    'created_at' => $message->created_at->toISOString(),
                    'read_at' => $message->read_at,
                    'sender' => $message->sender ? [
                        'id' => $message->sender->id,
                        'name' => $message->sender->firstname . ' ' . $message->sender->lastname,
                        'avatar' => $message->sender->avatar ?? '/default-avatar.svg',
                    ] : null,
                ];
            });

            \Log::info('Messages formatés', [
                'formatted_count' => $messagesFormatted->count()
            ]);
            
            // Marquer les messages comme lus
            $unreadCount = $conversation->messages()
                ->where('sender_id', '!=', $user->id)
                ->whereNull('read_at')
                ->count();

            \Log::info('Messages non lus avant marquage', [
                'unread_count' => $unreadCount
            ]);

            $conversation->markMessagesAsRead($user->id);

            \Log::info('Messages marqués comme lus');

            // Récupérer l'autre utilisateur de la conversation
            try {
                $otherUser = $conversation->getOtherUser($user->id);
                \Log::info('Autre utilisateur récupéré', [
                    'other_user_id' => $otherUser->id,
                    'other_user_name' => $otherUser->name
                ]);
            } catch (\Exception $e) {
                \Log::error('Erreur lors de la récupération de l\'autre utilisateur', [
                    'error' => $e->getMessage()
                ]);
                $otherUser = null;
            }

            $responseData = [
                'messages' => $messagesFormatted,
                'conversation' => [
                    'id' => $conversation->id,
                    'status' => $conversation->status,
                    'ad_title' => $conversation->ad->title ?? 'Annonce supprimée',
                    'ad' => $conversation->ad ? [
                        'id' => $conversation->ad->id,
                        'title' => $conversation->ad->title,
                        'date_start' => $conversation->ad->date_start,
                        'date_end' => $conversation->ad->date_end,
                    ] : null,
                    'other_user' => $otherUser ? [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'firstname' => $otherUser->firstname,
                        'lastname' => $otherUser->lastname,
                        'avatar' => $otherUser->avatar ?? '/default-avatar.svg',
                    ] : null,
                    'reservation' => $conversation->reservation ? [
                        'id' => $conversation->reservation->id,
                        'status' => $conversation->reservation->status,
                        'service_start_at' => $conversation->reservation->service_start_at,
                        'service_end_at' => $conversation->reservation->service_end_at,
                        'total_deposit' => $conversation->reservation->total_deposit,
                        'deposit_amount' => $conversation->reservation->deposit_amount,
                        'can_be_cancelled' => $conversation->reservation->can_be_cancelled,
                        'can_be_cancelled_free' => $conversation->reservation->can_be_cancelled_free,
                        'ad' => $conversation->reservation->ad ? [
                            'id' => $conversation->reservation->ad->id,
                            'title' => $conversation->reservation->ad->title,
                            'date_start' => $conversation->reservation->ad->date_start,
                            'date_end' => $conversation->reservation->ad->date_end,
                        ] : null
                    ] : null
                ]
            ];

            \Log::info('Réponse messages préparée', [
                'messages_count' => count($responseData['messages']),
                'conversation_data_keys' => array_keys($responseData['conversation'])
            ]);

            return response()->json($responseData);

        } catch (\Exception $e) {
            \Log::error('ERREUR lors de la récupération des messages', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Erreur lors de la récupération des messages: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Indicateur de frappe en cours
     */
    public function userTyping(Request $request, Conversation $conversation)
    {
        $user = Auth::user();
        
        // Vérifier que l'utilisateur fait partie de cette conversation
        if ($conversation->parent_id !== $user->id && $conversation->babysitter_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'is_typing' => 'required|boolean'
        ]);

        // Diffuser l'événement de frappe
        broadcast(new UserTyping($user, $conversation->id, $validated['is_typing']))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Marquer un message spécifique comme lu
     */
    public function markMessageAsRead(Conversation $conversation, Message $message)
    {
        $user = Auth::user();
        
        \Log::info('=== MARQUAGE MESSAGE SPÉCIFIQUE COMME LU ===', [
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'message_sender_id' => $message->sender_id
        ]);
        
        // Vérifier que l'utilisateur fait partie de cette conversation
        if ($conversation->parent_id !== $user->id && $conversation->babysitter_id !== $user->id) {
            \Log::error('ACCESS DENIED - Utilisateur non autorisé pour marquer ce message comme lu', [
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
                'message_id' => $message->id
            ]);
            abort(403);
        }
        
        // Vérifier que le message appartient à cette conversation
        if ($message->conversation_id !== $conversation->id) {
            \Log::error('MESSAGE NOT IN CONVERSATION', [
                'conversation_id' => $conversation->id,
                'message_conversation_id' => $message->conversation_id,
                'message_id' => $message->id
            ]);
            abort(404);
        }
        
        // Ne marquer comme lu que si ce n'est pas notre propre message et qu'il n'est pas déjà lu
        if ($message->sender_id !== $user->id && !$message->read_at) {
            $message->update(['read_at' => now()]);
            
            \Log::info('Message marqué comme lu', [
                'message_id' => $message->id,
                'read_by' => $user->id
            ]);
            
            // Émettre l'événement de lecture
            event(new \App\Events\MessageRead($conversation, $user));
            
            return response()->json([
                'success' => true,
                'message' => 'Message marqué comme lu'
            ]);
        }
        
        \Log::info('Message non marqué comme lu', [
            'reason' => $message->sender_id === $user->id ? 'own_message' : 'already_read',
            'message_id' => $message->id,
            'sender_id' => $message->sender_id,
            'read_at' => $message->read_at
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Aucune action nécessaire'
        ]);
    }

    /**
     * Marquer tous les messages d'une conversation comme lus
     */
    public function markAllMessagesAsRead(Conversation $conversation)
    {
        $user = Auth::user();
        
        \Log::info('=== MARQUAGE TOUS LES MESSAGES COMME LUS ===', [
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
        ]);

        // Vérifier que l'utilisateur peut accéder à cette conversation
        if ($conversation->parent_id !== $user->id && $conversation->babysitter_id !== $user->id) {
            \Log::error('ACCESS DENIED - Utilisateur non autorisé', [
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
            ]);
            return back()->withErrors(['error' => 'Non autorisé']);
        }

        // Marquer tous les messages non lus comme lus
        $conversation->markMessagesAsRead($user->id);
        
        return back()->with('success', 'Messages marqués comme lus');
    }

    /**
     * Archiver une conversation (refuser une candidature)
     */
    public function archiveConversation(Conversation $conversation)
    {
        $user = Auth::user();
        
        \Log::info('=== ARCHIVAGE CONVERSATION ===', [
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'conversation_status' => $conversation->status
        ]);

        // Vérifier que l'utilisateur peut archiver cette conversation
        if ($conversation->parent_id !== $user->id && $conversation->babysitter_id !== $user->id) {
            \Log::error('ACCESS DENIED - Utilisateur non autorisé pour archiver cette conversation', [
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
                'parent_id' => $conversation->parent_id,
                'babysitter_id' => $conversation->babysitter_id
            ]);
            abort(403);
        }

        try {
            // Archiver la conversation
            $conversation->update([
                'status' => 'archived'
            ]);

            \Log::info('Conversation archivée avec succès', [
                'conversation_id' => $conversation->id,
                'archived_by' => $user->id,
                'previous_status' => $conversation->getOriginal('status')
            ]);

            return back()->with('success', 'Conversation archivée avec succès');

        } catch (\Exception $e) {
            \Log::error('ERREUR lors de l\'archivage de la conversation', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'error' => 'Erreur lors de l\'archivage de la conversation'
            ]);
        }
    }

    /**
     * Rembourser le parent avec déduction des frais de service et frais de remboursement
     * (Utilisé pour les annulations parent >24h avant le service)
     */
    private function refundParentWithFees($reservation)
    {
        try {
            if (!$reservation->stripe_payment_intent_id) {
                \Log::warning('Impossible de rembourser le parent - pas de payment_intent_id', [
                    'reservation_id' => $reservation->id
                ]);
                return;
            }

            // Calculer le montant à rembourser (montant - 2€ frais de service)
            $platformFee = 2.00; // Frais de service de la plateforme
            $refundAmount = $reservation->total_deposit - $platformFee;
            
            // S'assurer qu'on ne rembourse pas un montant négatif
            if ($refundAmount <= 0) {
                \Log::warning('Montant de remboursement négatif ou nul', [
                    'reservation_id' => $reservation->id,
                    'original_amount' => $reservation->total_deposit,
                    'platform_fee' => $platformFee
                ]);
                
                $reservation->update([
                    'status' => 'cancelled_by_parent_late',
                    'cancelled_at' => now()
                ]);
                return;
            }

            // Utiliser le service Stripe pour créer le remboursement partiel
            // Frais de remboursement Stripe à la charge du parent
            $stripeService = app(\App\Services\StripeService::class);
            
            // Créer un remboursement partiel via Stripe (frais à la charge du parent)
            $refund = $stripeService->createRefund(
                $reservation->stripe_payment_intent_id,
                $refundAmount * 100, // Montant en centimes (moins les 2€ de frais)
                'Remboursement parent -2€ frais service - Frais remboursement à sa charge'
            );

            // Mettre à jour la réservation
            $reservation->update([
                'status' => 'refunded_minus_service_fees',
                'refund_stripe_id' => $refund->id,
                'refunded_at' => now(),
                'platform_fee_retained' => $platformFee
            ]);

            \Log::info('Remboursement parent avec déduction frais de service', [
                'reservation_id' => $reservation->id,
                'refund_id' => $refund->id,
                'original_amount' => $reservation->total_deposit,
                'platform_fee_deducted' => $platformFee,
                'refunded_amount' => $refundAmount,
                'payment_intent_id' => $reservation->stripe_payment_intent_id,
                'note' => 'Frais de service (2€) déduits + frais de remboursement à charge du parent'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors du remboursement parent avec frais de service', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
            
            // En cas d'erreur Stripe, au moins marquer comme à rembourser manuellement
            $reservation->update([
                'status' => 'parent_refund_pending',
                'refund_error' => $e->getMessage()
            ]);
        }
    }
} 