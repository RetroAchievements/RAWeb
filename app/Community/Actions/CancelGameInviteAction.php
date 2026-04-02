<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\GameInvite;
use App\Models\User;
use App\Platform\Enums\GameInviteStatus;

class CancelGameInviteAction
{
    /**
     * Cancel a game invite.
     */
    public function execute(GameInvite $invite, User $user): GameInvite
    {
        // Only sender can cancel pending invites
        if ($invite->sender_user_id !== $user->id) {
            throw new \InvalidArgumentException('Only the sender can cancel a game invite.');
        }

        if ($invite->status !== GameInviteStatus::Pending) {
            throw new \InvalidArgumentException('This invite cannot be canceled.');
        }

        $invite->update([
            'status' => GameInviteStatus::Canceled,
            'responded_at' => now(),
        ]);

        return $invite->refresh();
    }
}
