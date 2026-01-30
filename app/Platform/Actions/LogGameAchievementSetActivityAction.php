<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\User;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class LogGameAchievementSetActivityAction
{
    public function execute(
        string $operation,
        Game $game,
        GameAchievementSet $gameAchievementSet,
        array $original = [],
        array $changes = [],
    ): void {
        match ($operation) {
            'update' => $this->logUpdate($game, $gameAchievementSet, $original, $changes),
            default => throw new InvalidArgumentException("Unknown operation: {$operation}"),
        };
    }

    private function logUpdate(
        Game $game,
        GameAchievementSet $gameAchievementSet,
        array $original,
        array $changes,
    ): void {
        $oldData = [];
        $newData = [];

        $wasTypeChanged = isset($changes['type']) && ($original['type'] ?? null) !== $changes['type'];
        if ($wasTypeChanged) {
            $oldType = AchievementSetType::tryFrom($original['type'] ?? '');
            $newType = AchievementSetType::tryFrom($changes['type'] ?? '');

            $oldData['set_type'] = $oldType?->label();
            $newData['set_type'] = $newType?->label();
        }

        $wasTitleChanged = isset($changes['title']) && ($original['title'] ?? null) !== $changes['title'];
        if ($wasTitleChanged) {
            $oldData['set_title'] = $original['title'];
            $newData['set_title'] = $changes['title'];
        }

        if (empty($oldData)) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        activity()
            ->causedBy($user)
            ->performedOn($game)
            ->withProperty('old', $oldData)
            ->withProperty('attributes', $newData)
            ->withProperty('set_id', $gameAchievementSet->achievement_set_id)
            ->withProperty('set_identifier', $changes['title'] ?? $gameAchievementSet->title ?? 'Base Set')
            ->event('achievementSetUpdated')
            ->log('Achievement set updated');
    }
}
