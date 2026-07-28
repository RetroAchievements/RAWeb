<?php

declare(strict_types=1);

namespace App\Support\Alerts;

use App\Models\Achievement;
use App\Models\ConnectWarning;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\User;

class SuspiciousConnectWarningAlert extends Alert
{
    public function __construct(
        public readonly ConnectWarning $warning,
    ) {
    }

    public function toDiscordMessage(): string
    {
        $user = User::whereName($this->warning->username)->firstOrFail();

        $gameId = match ($this->warning->method) {
            'submitlbentry' => Leaderboard::find($this->warning->related_id)?->game_id,
            default => Achievement::find($this->warning->related_id)?->game_id,
        };

        if ($gameId) {
            // repeated_validation takes precedence
            if (str_contains($this->warning->smells, 'repeated_validation')) {
                return $this->repeatedValidationDiscordMessage($user, $gameId);
            }

            if (str_contains($this->warning->smells, 'wrong_client')) {
                return $this->wrongClientDiscordMessage($user, $gameId);
            }
        }

        return "";
    }

    /**
     * "[Scott](<https://retroachievements.org/user/Scott>) unlocked [Sonic the Hedgehog](<https://retroachievements.org/game/1>) achievements using PPSSPP - [Activity](<https://retroachievements.org/user/Scott/game/1/activity>)"
     */
    private function wrongClientDiscordMessage(User $user, int $gameId): string
    {
        $playerUrl = route('user.show', ['user' => $user]);

        $game = Game::with('system')->findOrFail($gameId);
        $gameUrl = route('game.show', ['game' => $game]);

        $activityUrl = route('user.game.activity.show', ['user' => $user, 'game' => $game]);

        return sprintf(
            match ($this->warning->method) {
                'submitlbentry' => '[%s](<%s>) submitted [%s](<%s>) (%s) leaderboard entries using %s - [Activity](<%s>)',
                default => '[%s](<%s>) unlocked [%s](<%s>) (%s) achievements using %s - [Activity](<%s>)',
            },
            $user->display_name,
            $playerUrl,
            $game->title,
            $gameUrl,
            $game->system->name,
            $this->warning->user_agent,
            $activityUrl,
        );
    }

    /**
     * "[Scott](<https://retroachievements.org/user/Scott>) used the same incorrect validation hash for multiple unlocks - [Activity](<https://retroachievements.org/user/Scott/game/1/activity>)"
     */
    private function repeatedValidationDiscordMessage(User $user, int $gameId): string
    {
        $playerUrl = route('user.show', ['user' => $user]);
        $activityUrl = route('user.game.activity.show', ['user' => $user, 'game' => $gameId]);

        return sprintf(
            '[%s](<%s>) used the same incorrect validation hash for multiple unlocks - [Activity](<%s>)',
            $user->display_name,
            $playerUrl,
            $activityUrl,
        );
    }
}
