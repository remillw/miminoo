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
    public function index(): Response
    {
        $user = Auth::user();
        
        \Log::info('=== CHARGEMENT CONVERSATIONS ===', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_roles' => $user->roles->pluck('name')->toArray()
        ]);
        
        try {
            // Récupérer toutes les conversations actives (pas archivées)
            $conversations = Conversation::with([
                'ad', // Charger toutes les données de l'annonce pour le slug
                'parent:id,firstname,lastname,avatar',
                'babysitter:id,firstname,lastname,avatar',
                'application' => function($query) {
                    $query->with(['babysitter:id,firstname,lastname,avatar', 'ad.parent:id,firstname,lastname,avatar']);
                },
                'messages' => function($query) {
                    $query->latest()->take(1);
                }
            ])
            ->forUser($user->id)
            ->where('status', '!=', 'archived') // Exclure les conversations archivées
            ->orderByDesc('created_at')
            ->get();

            \Log::info('Conversations brutes récupérées', [
                'conversations_count' => $conversations->count(),
                'conversations_ids' => $conversations->pluck('id')->toArray(),
                'conversations_status' => $conversations->pluck('status')->toArray()
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
                    
                    $conversationData = [
                        'id' => $conversation->id,
                        'type' => $conversation->status === 'pending' ? 'application' : 'conversation',
                        'ad_title' => $conversation->ad->title ?? 'Annonce supprimée',
                        'ad_date' => $conversation->ad ? $conversation->ad->date_start->format('d/m/Y') : 'Date inconnue',
                        'other_user' => [
                            'id' => $otherUser->id,
                            'name' => $otherUser->firstname . ' ' . substr($otherUser->lastname, 0, 1) . '.',
                            'firstname' => $otherUser->firstname,
                            'lastname' => $otherUser->lastname,
                            'avatar' => $otherUser->avatar ?? '/default-avatar.svg'
                        ],
                        'last_message' => $conversation->messages->first()?->message ?? ($conversation->status === 'pending' ? 'Nouvelle candidature' : 'Conversation démarrée'),
                        'last_message_at' => $conversation->last_message_at ?? $conversation->created_at,
                        'unread_count' => 0, // TODO: implémenter le compteur
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
                    
                    \Log::info('Conversation formatée avec succès', [
                        'conversation_id' => $conversation->id,
                        'type' => $conversationData['type'],
                        'has_application_data' => $conversationData['application'] ? true : false
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
                'requestedMode' => request('mode')
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
                'requestedMode' => request('mode'),
                'error' => 'Erreur lors du chargement des conversations'
            ]);
        }
    }

    /**
     * Marque une candidature comme vue (côté parent)
     */
    public function markApplicationAsViewed(AdApplication $application)
    {
        // Vérifier que l'utilisateur peut voir cette candidature
        if ($application->ad->parent_id !== Auth::id()) {
            abort(403);
        }

        $application->markAsViewed();

        return response()->json(['success' => true]);
    }

    /**
     * Réserver une candidature (nouveau système)
     */
    public function reserveApplication(Request $request, AdApplication $application)
    {
        // Vérifier que l'utilisateur peut réserver cette candidature
        if ($application->ad->parent_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'final_rate' => 'nullable|numeric|min:0|max:999.99'
        ]);

        $conversation = $application->reserve($validated['final_rate'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Candidature réservée ! Procédez au paiement pour confirmer.',
            'conversation_id' => $conversation->id,
            'payment_required' => true
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

        return response()->json([
            'success' => true,
            'message' => 'Candidature refusée. La conversation a été archivée.'
        ]);
    }

    /**
     * Faire une contre-offre
     */
    public function counterOffer(Request $request, AdApplication $application)
    {
        // Vérifier que l'utilisateur peut faire une contre-offre
        if ($application->ad->parent_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'counter_rate' => 'required|numeric|min:0|max:999.99',
            'counter_message' => 'nullable|string|max:500'
        ]);

        $application->counterOffer(
            $validated['counter_rate'],
            $validated['counter_message']
        );

        return response()->json([
            'success' => true,
            'message' => 'Contre-offre envoyée ! La babysitter peut accepter ou continuer à négocier.'
        ]);
    }

    /**
     * Répondre à une contre-offre (côté babysitter)
     */
    public function respondToCounterOffer(Request $request, AdApplication $application)
    {
        // Vérifier que l'utilisateur peut répondre
        if ($application->babysitter_id !== Auth::id()) {
            abort(403);
        }

        if ($application->status !== 'counter_offered') {
            return response()->json(['error' => 'Aucune contre-offre en attente'], 400);
        }

        $validated = $request->validate([
            'response' => 'required|in:accept,decline,counter'
        ]);

        if ($validated['response'] === 'accept') {
            // Accepter la contre-offre = réserver
            $conversation = $application->respondToCounterOffer('accept');
            return response()->json([
                'success' => true,
                'message' => 'Contre-offre acceptée ! La candidature est réservée.',
                'conversation_id' => $conversation->id,
                'payment_required' => true
            ]);
        } else {
            // Refuser ou faire une nouvelle contre-offre = continuer la négociation
            $conversation = $application->respondToCounterOffer('decline');
            return response()->json([
                'success' => true,
                'message' => 'Vous pouvez continuer à négocier.',
                'conversation_id' => $conversation->id
            ]);
        }
    }

    /**
     * Babysitter fait une contre-offre en retour
     */
    public function babysitterCounterOffer(Request $request, AdApplication $application)
    {
        // Vérifier que l'utilisateur peut faire une contre-offre
        if ($application->babysitter_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'counter_rate' => 'required|numeric|min:0|max:999.99',
            'counter_message' => 'nullable|string|max:500'
        ]);

        $application->counterOffer(
            $validated['counter_rate'],
            $validated['counter_message']
        );

        return response()->json([
            'success' => true,
            'message' => 'Votre contre-proposition a été envoyée au parent.'
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
            // Charger la relation ad si elle n'est pas déjà chargée
            if (!$conversation->relationLoaded('ad')) {
                $conversation->load('ad');
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

            // Ajouter un message système
            $conversation->addSystemMessage('conversation_archived', [
                'archived_by' => $user->id,
                'archived_by_name' => $user->name
            ]);

            \Log::info('Conversation archivée avec succès', [
                'conversation_id' => $conversation->id,
                'archived_by' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Conversation archivée avec succès'
            ]);

        } catch (\Exception $e) {
            \Log::error('ERREUR lors de l\'archivage de la conversation', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de l\'archivage de la conversation'
            ], 500);
        }
    }
} 