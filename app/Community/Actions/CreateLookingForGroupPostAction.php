<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\Game;
use App\Models\LookingForGroupPost;
use App\Models\User;
use App\Community\Enums\LookingForGroupStatus;

class CreateLookingForGroupPostAction
{
    /**
     * Create a new LFG post.
     */
    public function execute(
        User $creator,
        Game $game,
        string $title,
        ?string $note = null,
        ?int $maxPlayers = null,
        ?\DateTime $scheduledFor = null,
        ?\DateTime $expiresAt = null
    ): LookingForGroupPost {
        // Validate max players if provided
        if ($maxPlayers !== null && $maxPlayers < 1) {
            throw new \InvalidArgumentException('Max players must be at least 1.');
        }

        // Default expiration to 30 days if not set
        $expiresAt ??= now()->addDays(30);

        return LookingForGroupPost::create([
            'creator_user_id' => $creator->id,
            'game_id' => $game->id,
            'title' => $title,
            'note' => $note,
            'max_players' => $maxPlayers,
            'scheduled_for' => $scheduledFor,
            'status' => LookingForGroupStatus::Active,
            'expires_at' => $expiresAt,
        ]);
    }
}
