<?php

use Illuminate\Support\Facades\Broadcast;

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
    // Vérifier que l'utilisateur fait partie de cette conversation
    $conversation = \App\Models\Conversation::find($conversationId);
    
    if (!$conversation) {
        return false;
    }
    
    return $conversation->parent_id === $user->id || $conversation->babysitter_id === $user->id;
});

// Canal de présence pour les utilisateurs en ligne
Broadcast::channel('online-users', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->firstname . ' ' . $user->lastname,
        'avatar' => '/default-avatar.svg', // Avatar par défaut
    ];
}); 