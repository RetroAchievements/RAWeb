<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\LookingForGroupInvite;
use App\Models\LookingForGroupPost;
use App\Models\User;
use App\Community\Enums\LookingForGroupInviteStatus;

class CreateLookingForGroupInviteAction
{
    /**
     * Create a new LFG invite.
     */
    public function execute(
        LookingForGroupPost $post,
        User $sender,
        User $recipient,
        ?string $message = null
    ): LookingForGroupInvite {
        // Check if the post can be joined
        if (!$post->canBeJoinedBy($sender)) {
            throw new \InvalidArgumentException('You cannot join this LFG post.');
        }

        // Check if there's already a pending invite from this user
        $existingInvite = LookingForGroupInvite::where('looking_for_group_post_id', $post->id)
            ->where('sender_user_id', $sender->id)
            ->where('status', LookingForGroupInviteStatus::Pending)
            ->notExpired()
            ->first();

        if ($existingInvite) {
            throw new \InvalidArgumentException('You already have a pending invite for this LFG post.');
        }

        return LookingForGroupInvite::create([
            'looking_for_group_post_id' => $post->id,
            'sender_user_id' => $sender->id,
            'recipient_user_id' => $recipient->id,
            'message' => $message,
            'status' => LookingForGroupInviteStatus::Pending,
            'sent_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);
    }
}
