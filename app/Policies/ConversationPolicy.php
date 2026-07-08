<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConversationPolicy
{
    /**
     * Determine whether the user can view their business's conversations.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentBusiness !== null;
    }

    /**
     * Determine whether the user can view the conversation. Denied as "not
     * found" so a merchant can't probe for conversations outside their business.
     */
    public function view(User $user, Conversation $conversation): Response
    {
        return $conversation->business !== null && $user->belongsToBusiness($conversation->business)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
