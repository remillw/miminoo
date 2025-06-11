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
        
        // Récupérer toutes les conversations actives (pas archivées)
        // Les candidatures sont maintenant des conversations avec status='pending'
        $conversations = Conversation::with([
            'ad:id,title,date_start', 
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
        ->orderByDesc('created_at') // Les plus récentes en premier
        ->get()
        ->map(function ($conversation) use ($user) {
            try {
                $otherUser = $conversation->getOtherUser($user->id);
                $application = $conversation->application;
                
                $conversationData = [
                    'id' => $conversation->id,
                    'type' => $conversation->status === 'pending' ? 'application' : 'conversation',
                    'ad_title' => $conversation->ad->title ?? 'Annonce supprimée',
                    'ad_date' => $conversation->ad ? $conversation->ad->date_start->format('d/m/Y') : 'Date inconnue',
                    'other_user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->firstname . ' ' . substr($otherUser->lastname, 0, 1) . '.',
                        'avatar' => $otherUser->avatar
                    ],
                    'last_message' => $conversation->messages->first()?->message ?? ($conversation->status === 'pending' ? 'Nouvelle candidature' : 'Conversation démarrée'),
                    'last_message_at' => $conversation->last_message_at ?? $conversation->created_at,
                    'unread_count' => 0, // TODO: implémenter le compteur
                    'status' => $conversation->status
                ];

                // Ajouter les données de candidature si elle existe
                if ($application) {
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
                            'avatar' => $application->babysitter->avatar
                        ];
                    } elseif ($user->hasRole('babysitter') && $application->ad && $application->ad->parent) {
                        $conversationData['application']['parent'] = [
                            'id' => $application->ad->parent->id,
                            'name' => $application->ad->parent->firstname . ' ' . substr($application->ad->parent->lastname, 0, 1) . '.',
                            'avatar' => $application->ad->parent->avatar
                        ];
                    }
                } else {
                    $conversationData['application'] = null;
                }
                
                return $conversationData;
            } catch (\Exception $e) {
                // En cas d'erreur, ignorer cette conversation ou retourner des données minimales
                \Log::error('Erreur lors du chargement de la conversation ' . $conversation->id . ': ' . $e->getMessage());
                return null;
            }
        })
        ->filter() // Enlever les éléments null
        ->values(); // Réindexer le tableau

        return Inertia::render('Messaging/Index', [
            'conversations' => $conversations,
            'userRole' => $user->roles->first()->name ?? 'user',
            'hasParentRole' => $user->hasRole('parent'),
            'hasBabysitterRole' => $user->hasRole('babysitter'),
            'requestedMode' => request('mode')
        ]);
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
        
        // Vérifier que l'utilisateur fait partie de cette conversation
        if ($conversation->parent_id !== $user->id && $conversation->babysitter_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        // Créer le message
        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'message' => $validated['message'],
            'type' => 'user'
        ]);

        // Mettre à jour la conversation
        $conversation->update([
            'last_message_at' => now(),
            'last_message_by' => $user->id
        ]);

        // Charger les relations nécessaires
        $message->load('sender');

        // Diffuser l'événement en temps réel
        broadcast(new MessageSent($message, $user))->toOthers();

        return response()->json([
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
                    'name' => $user->name,
                    'avatar' => $user->google_avatar_url ?? '/default-avatar.png',
                ],
            ]
        ]);
    }

    /**
     * Récupérer les messages d'une conversation
     */
    public function getMessages(Conversation $conversation)
    {
        $user = Auth::user();
        
        // Vérifier que l'utilisateur fait partie de cette conversation
        if ($conversation->parent_id !== $user->id && $conversation->babysitter_id !== $user->id) {
            abort(403);
        }

        $messages = $conversation->messages()
            ->with('sender:id,name,google_avatar_url')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
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
                        'name' => $message->sender->name,
                        'avatar' => $message->sender->google_avatar_url ?? '/default-avatar.png',
                    ] : null,
                ];
            });

        // Marquer les messages comme lus
        $conversation->markMessagesAsRead($user->id);

        return response()->json([
            'messages' => $messages,
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'ad_title' => $conversation->ad->title ?? 'Annonce supprimée',
                'other_user' => [
                    'id' => $conversation->getOtherUser($user->id)->id,
                    'name' => $conversation->getOtherUser($user->id)->name,
                    'avatar' => $conversation->getOtherUser($user->id)->google_avatar_url ?? '/default-avatar.png',
                ]
            ]
        ]);
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
} 