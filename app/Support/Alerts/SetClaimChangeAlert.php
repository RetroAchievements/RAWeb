<?php

namespace App\Support\Alerts;

use App\Community\Enums\ClaimSetType;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\User;

class SetClaimChangeAlert extends Alert
{
    public function __construct(
        public readonly Game $game,
        public readonly User $user,
        public readonly string $action,
        public readonly AchievementSetClaim $claim,
    ) {
    }

    /**
     * "[gameLink]
     * :new: Primary claim created by [userLink]"
     */
    public function toDiscordMessage(): string
    {
        $gameLink = route('game.show', $this->game);

        $isRevision = $this->claim->set_type === ClaimSetType::Revision;
        $claimType = $this->claim->claim_type->label() . ($isRevision ? ' revision' : '');

        $emoji = match ($this->action) {
            'create' => ':new:',
            'extend' => ':timer:',
            'drop' => ':no_entry_sign:',
            'update' => ':white_check_mark:',
            default => '',
        };

        $action = match ($this->action) {
            'create' => 'created',
            'extend' => 'extended',
            'drop' => 'dropped',
            'update' => 'completed',
            default => '',
        };

        // Put it all together!
        return "$gameLink\n" .
            "$emoji $claimType claim $action by " . $this->user->display_name;
    }
}
