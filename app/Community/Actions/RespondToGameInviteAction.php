<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\GameInvite;
use App\Models\User;
use App\Platform\Enums\GameInviteStatus;

class RespondToGameInviteAction
{
    /**
     * Respond to a game invite (accept/decline).
     */
    public function execute(GameInvite $invite, User $user, GameInviteStatus $response): GameInvite
    {
        // Only recipient can respond to pending invites
        if ($invite->recipient_user_id !== $user->id) {
            throw new \InvalidArgumentException('Only the recipient can respond to a game invite.');
        }

        if ($invite->status !== GameInviteStatus::Pending) {
            throw new \InvalidArgumentException('This invite is no longer pending.');
        }

        if ($invite->isExpired()) {
            throw new \InvalidArgumentException('This invite has expired.');
        }

        // Only accept or decline are valid responses
        if (!in_array($response, [GameInviteStatus::Accepted, GameInviteStatus::Declined])) {
            throw new \InvalidArgumentException('Invalid response. Only accept or decline are allowed.');
        }

        $invite->update([
            'status' => $response,
            'responded_at' => now(),
        ]);

        return $invite->refresh();
    }
}
