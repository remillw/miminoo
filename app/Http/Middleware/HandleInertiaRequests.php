<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();
        
        // Récupérer les notifications pour toutes les pages
        $unreadNotifications = [];
        $unreadNotificationsCount = 0;
        $unreadMessagesCount = 0;
        
        if ($user) {
            $unreadNotifications = $user->unreadNotifications()
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $this->getNotificationType($notification->type),
                        'title' => $notification->data['title'] ?? $notification->data['message'] ?? 'Notification',
                        'message' => $notification->data['message'] ?? '',
                        'created_at' => $notification->created_at,
                        'read_at' => $notification->read_at,
                        // Inclure les données spécifiques pour créer les liens
                        'data' => [
                            'ad_id' => $notification->data['ad_id'] ?? null,
                            'ad_slug' => $notification->data['ad_slug'] ?? null,
                            'conversation_id' => $notification->data['conversation_id'] ?? null,
                            'application_id' => $notification->data['application_id'] ?? null,
                        ]
                    ];
                });
            $unreadNotificationsCount = $user->unreadNotifications()->count();
            
            // Compter les messages non lus pour la sidebar avec logs détaillés
            $unreadMessagesQuery = \App\Models\Message::join('conversations', 'messages.conversation_id', '=', 'conversations.id')
                ->where(function ($query) use ($user) {
                    $query->where('conversations.parent_id', $user->id)
                          ->orWhere('conversations.babysitter_id', $user->id);
                })
                ->where('messages.sender_id', '!=', $user->id)
                ->whereNull('messages.read_at')
                ->where('conversations.status', '!=', 'archived') // Exclure explicitement les conversations archivées
                ->whereNotIn('conversations.status', ['cancelled', 'declined']); // Exclure aussi les conversations annulées/refusées
            
            $unreadMessagesCount = $unreadMessagesQuery->count();
            
            // Log détaillé des conversations avec messages non lus
            if ($unreadMessagesCount > 0) {
                $unreadConversations = \App\Models\Message::select([
                    'messages.id as message_id',
                    'messages.message',
                    'messages.created_at as message_created_at',
                    'conversations.id as conversation_id',
                    'conversations.status as conversation_status',
                    'conversations.parent_id',
                    'conversations.babysitter_id'
                ])
                ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
                ->where(function ($query) use ($user) {
                    $query->where('conversations.parent_id', $user->id)
                          ->orWhere('conversations.babysitter_id', $user->id);
                })
                ->where('messages.sender_id', '!=', $user->id)
                ->whereNull('messages.read_at')
                ->where('conversations.status', '!=', 'archived')
                ->whereNotIn('conversations.status', ['cancelled', 'declined'])
                ->orderBy('messages.created_at', 'desc')
                ->get();
                
                \Log::info('=== MESSAGES NON LUS DÉTECTÉS DANS SIDEBAR ===', [
                    'user_id' => $user->id,
                    'total_unread_count' => $unreadMessagesCount,
                    'conversations_with_unread' => $unreadConversations->map(function($item) {
                        return [
                            'conversation_id' => $item->conversation_id,
                            'conversation_status' => $item->conversation_status,
                            'message_id' => $item->message_id,
                            'message_preview' => substr($item->message, 0, 50) . '...',
                            'message_date' => $item->message_created_at,
                            'parent_id' => $item->parent_id,
                            'babysitter_id' => $item->babysitter_id
                        ];
                    })->toArray()
                ]);
            }
        }
        
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'roles' => $user->roles()->get(['name', 'label'])->toArray(),
                ] : null,
            ],
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            // Notifications globales disponibles sur toutes les pages
            'unreadNotifications' => $unreadNotifications,
            'unreadNotificationsCount' => $unreadNotificationsCount,
            // Messages non lus pour la sidebar
            'unreadMessagesCount' => $unreadMessagesCount,
            // Device token registration flag pour mobile
            'triggerDeviceTokenRegistration' => $request->session()->get('trigger_device_token_registration', false),
        ];
    }

    /**
     * Mapper les types de notifications
     */
    private function getNotificationType($notificationType)
    {
        $typeMap = [
            'App\\Notifications\\ReviewRequestNotification' => 'review_request',
            'App\\Notifications\\FundsReleasedNotification' => 'funds_released',
            'App\\Notifications\\DisputeCreatedNotification' => 'dispute_created',
            'App\\Notifications\\NewApplication' => 'new_application',
            'App\\Notifications\\NewMessage' => 'new_message',
            'App\\Notifications\\NewAnnouncementInRadius' => 'new_announcement',
            // Ajoutez d'autres types selon vos besoins
        ];
        
        return $typeMap[$notificationType] ?? 'general';
    }
}
