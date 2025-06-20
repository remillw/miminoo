<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal pour les conversations privées
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    Log::info('🔐 Tentative d\'authentification canal', [
        'user_id' => $user->id,
        'conversation_id' => $conversationId
    ]);
    
    // Vérifier que l'utilisateur fait partie de cette conversation
    $conversation = \App\Models\Conversation::find($conversationId);
    
    if (!$conversation) {
        Log::warning('❌ Conversation non trouvée', ['conversation_id' => $conversationId]);
        return false;
    }
    
    $authorized = $conversation->parent_id === $user->id || $conversation->babysitter_id === $user->id;
    
    Log::info('🔐 Résultat authentification', [
        'authorized' => $authorized,
        'parent_id' => $conversation->parent_id,
        'babysitter_id' => $conversation->babysitter_id,
        'user_id' => $user->id
    ]);
    
    return $authorized;
});

// Canal de présence pour les utilisateurs en ligne
Broadcast::channel('online-users', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->firstname . ' ' . $user->lastname,
        'avatar' => '/default-avatar.svg', // Avatar par défaut
    ];
}); 