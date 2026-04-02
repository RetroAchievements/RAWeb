<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\LookingForGroupInvite;
use App\Models\User;
use App\Community\Enums\LookingForGroupInviteStatus;

class CancelLookingForGroupInviteAction
{
    /**
     * Cancel an LFG invite.
     */
    public function execute(LookingForGroupInvite $invite, User $user): LookingForGroupInvite
    {
        // Only sender can cancel pending invites
        if ($invite->sender_user_id !== $user->id) {
            throw new \InvalidArgumentException('Only the sender can cancel an LFG invite.');
        }

        if ($invite->status !== LookingForGroupInviteStatus::Pending) {
            throw new \InvalidArgumentException('This invite cannot be canceled.');
        }

        $invite->update([
            'status' => LookingForGroupInviteStatus::Canceled,
            'responded_at' => now(),
        ]);

        return $invite->refresh();
    }
}
