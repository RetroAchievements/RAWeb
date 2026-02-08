<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\AchievementGroup;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class LogAchievementGroupActivityAction
{
    public function execute(
        string $operation,
        Game $game,
        ?AchievementGroup $group = null,
        array $original = [],
        array $changes = [],
        array $context = [],
    ): void {
        match ($operation) {
            'create' => $this->logCreate($game, $group),
            'update' => $this->logUpdate($game, $group, $original, $changes),
            'delete' => $this->logDelete($game, $original),
            'assignAchievements' => $this->logAssignAchievements($game, $context),
            default => throw new InvalidArgumentException("Unknown operation: {$operation}"),
        };
    }

    private function logCreate(Game $game, AchievementGroup $group): void
    {
        /** @var User $user */
        $user = Auth::user();

        activity()
            ->causedBy($user)
            ->performedOn($game)
            ->withProperty('attributes', [
                'group_label' => $group->label,
            ])
            ->withProperty('group_id', $group->id)
            ->event('achievementGroupCreated')
            ->log('Achievement group created');
    }

    private function logUpdate(Game $game, AchievementGroup $group, array $original, array $changes): void
    {
        $labelChanged = isset($changes['label']) && $changes['label'] !== ($original['label'] ?? null);
        $orderChanged = isset($changes['order_column']) && $changes['order_column'] !== ($original['order_column'] ?? null);

        if (!$labelChanged && !$orderChanged) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        $oldData = [];
        $newData = [];

        if ($labelChanged) {
            $oldData['group_label'] = $original['label'] ?? null;
            $newData['group_label'] = $changes['label'];
        }

        if ($orderChanged) {
            // Include the group label so it's clear which group was reordered.
            if (!$labelChanged) {
                $oldData['group_label'] = $original['label'] ?? null;
                $newData['group_label'] = $original['label'] ?? null;
            }

            $oldData['group_order'] = ($original['order_column'] ?? 0) + 1;
            $newData['group_order'] = $changes['order_column'] + 1;
        }

        activity()
            ->causedBy($user)
            ->performedOn($game)
            ->withProperty('old', $oldData)
            ->withProperty('attributes', $newData)
            ->withProperty('group_id', $group->id)
            ->event('achievementGroupUpdated')
            ->log('Achievement group updated');
    }

    private function logDelete(Game $game, array $original): void
    {
        /** @var User $user */
        $user = Auth::user();

        activity()
            ->causedBy($user)
            ->performedOn($game)
            ->withProperty('old', [
                'group_label' => $original['label'],
            ])
            ->withProperty('group_id', $original['id'])
            ->event('achievementGroupDeleted')
            ->log('Achievement group deleted');
    }

    private function logAssignAchievements(Game $game, array $context): void
    {
        /** @var User $user */
        $user = Auth::user();

        $groupLabel = $context['group_label'] ?? '(No Group)';
        $achievementIds = $context['achievement_ids'] ?? [];

        activity()
            ->causedBy($user)
            ->performedOn($game)
            ->withProperty('attributes', [
                'group_label' => $groupLabel,
                'achievement_ids' => implode(', ', $achievementIds),
            ])
            ->event('achievementsAssignedToGroup')
            ->log('Achievements assigned to group');
    }
}
