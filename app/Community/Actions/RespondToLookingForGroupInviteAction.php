<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\LookingForGroupInvite;
use App\Models\User;
use App\Community\Enums\LookingForGroupInviteStatus;
use App\Community\Enums\LookingForGroupStatus;

class RespondToLookingForGroupInviteAction
{
    /**
     * Respond to an LFG invite (accept/decline).
     */
    public function execute(LookingForGroupInvite $invite, User $user, LookingForGroupInviteStatus $response): LookingForGroupInvite
    {
        // Only recipient can respond to pending invites
        if ($invite->recipient_user_id !== $user->id) {
            throw new \InvalidArgumentException('Only the recipient can respond to an LFG invite.');
        }

        if ($invite->status !== LookingForGroupInviteStatus::Pending) {
            throw new \InvalidArgumentException('This invite is no longer pending.');
        }

        if ($invite->isExpired()) {
            throw new \InvalidArgumentException('This invite has expired.');
        }

        // Only accept or decline are valid responses
        if (!in_array($response, [LookingForGroupInviteStatus::Accepted, LookingForGroupInviteStatus::Declined])) {
            throw new \InvalidArgumentException('Invalid response. Only accept or decline are allowed.');
        }

        $invite->update([
            'status' => $response,
            'responded_at' => now(),
        ]);

        // If accepted, check if the post is now full
        if ($response === LookingForGroupInviteStatus::Accepted) {
            $post = $invite->lookingForGroupPost;
            if ($post->isFull() && $post->status === LookingForGroupStatus::Active) {
                $post->update(['status' => LookingForGroupStatus::Filled]);
            }
        }

        return $invite->refresh();
    }
}
