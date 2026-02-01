<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// ✅ CHAT AUTHORIZATION (Only One Definition)
Broadcast::channel('chat.{id}', function ($user, $id) {
    // Check if the user is the Sender OR the Receiver of this conversation
    return Conversation::where('id', $id)
        ->where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('maker_id', $user->id);
        })->exists();
});