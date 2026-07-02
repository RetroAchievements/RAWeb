<?php

declare(strict_types=1);

namespace App\Support\Alerts;

use App\Community\Enums\ClaimSetType;
use App\Enums\SetClaimChangeAction;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\User;

class SetClaimChangeAlert extends Alert
{
    public function __construct(
        public readonly Game $game,
        public readonly User $user,
        public readonly AchievementSetClaim $claim,
        public readonly SetClaimChangeAction $action,
    ) {
    }

    public static function webhookUsername(): string
    {
        return "Claim Bot";
    }

    public static function webhookAvatarUrl(): string
    {
        return media_asset('UserPic/QATeam.png');
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
            SetClaimChangeAction::Create => ':new:',
            SetClaimChangeAction::Extend => ':timer:',
            SetClaimChangeAction::Drop => ':no_entry_sign:',
            SetClaimChangeAction::Update => ':white_check_mark:',
        };

        $action = $this->action->label();

        // Put it all together!
        return "$gameLink\n" .
            "$emoji $claimType claim $action by " . $this->user->display_name;
    }
}
