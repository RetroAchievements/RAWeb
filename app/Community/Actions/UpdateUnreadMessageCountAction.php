<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Models\MessageThreadParticipant;
use App\Models\User;

class UpdateUnreadMessageCountAction
{
    public function execute(User $user): void
    {
        $totalUnread = MessageThreadParticipant::where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->sum('num_unread');

        $user->UnreadMessageCount = $totalUnread;
        $user->save();
    }
}
