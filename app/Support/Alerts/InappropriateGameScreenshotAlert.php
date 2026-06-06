<?php

declare(strict_types=1);

namespace App\Support\Alerts;

use App\Models\GameScreenshot;
use App\Models\User;

class InappropriateGameScreenshotAlert extends Alert
{
    public function __construct(
        public readonly GameScreenshot $screenshot,
        public readonly User $reviewer,
    ) {
    }

    /**
     * "[SomeMod](<https://retroachievements.org/user/SomeMod>) rejected a title screenshot submission for [Sonic the Hedgehog](<https://retroachievements.org/game/1>) as inappropriate content from [PlayerOne](<https://retroachievements.org/user/PlayerOne>) - Note: Contains explicit content."
     */
    public function toDiscordMessage(): string
    {
        $game = $this->screenshot->game;
        $submitter = $this->screenshot->capturedBy;
        $reviewerUrl = route('user.show', ['user' => $this->reviewer]);
        $gameUrl = route('game.show', ['game' => $game]);

        $message = sprintf(
            '[%s](<%s>) rejected a %s screenshot submission for [%s](<%s>) as inappropriate content',
            $this->reviewer->display_name,
            $reviewerUrl,
            strtolower($this->screenshot->type->label()),
            $game->title,
            $gameUrl,
        );

        if ($submitter) {
            $submitterUrl = route('user.show', ['user' => $submitter]);
            $message .= sprintf(' from [%s](<%s>)', $submitter->display_name, $submitterUrl);
        }

        if ($this->screenshot->rejection_notes) {
            $message .= sprintf(' - Note: %s', $this->screenshot->rejection_notes);
        }

        return $message;
    }
}
