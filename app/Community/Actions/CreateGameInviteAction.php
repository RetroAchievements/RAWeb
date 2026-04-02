<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\Game;
use App\Models\GameInvite;
use App\Models\User;
use App\Platform\Enums\GameInviteStatus;

class CreateGameInviteAction
{
    /**
     * Create a new game invite.
     */
    public function execute(User $sender, User $recipient, Game $game, ?string $message = null): GameInvite
    {
        // Check if there's already a pending invite for this combination
        $existingInvite = GameInvite::where('sender_user_id', $sender->id)
            ->where('recipient_user_id', $recipient->id)
            ->where('game_id', $game->id)
            ->where('status', GameInviteStatus::Pending)
            ->notExpired()
            ->first();

        if ($existingInvite) {
            throw new \InvalidArgumentException('A pending invite for this game already exists.');
        }

        // Cannot invite yourself
        if ($sender->id === $recipient->id) {
            throw new \InvalidArgumentException('You cannot invite yourself to a game.');
        }

        return GameInvite::create([
            'sender_user_id' => $sender->id,
            'recipient_user_id' => $recipient->id,
            'game_id' => $game->id,
            'message' => $message,
            'status' => GameInviteStatus::Pending,
            'sent_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);
    }
}
