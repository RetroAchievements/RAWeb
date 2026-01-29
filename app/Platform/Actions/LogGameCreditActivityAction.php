<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\AchievementSetAuthor;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class LogGameCreditActivityAction
{
    public function execute(
        string $operation,
        AchievementSetAuthor $credit,
        array $original = [],
        array $changes = [],
    ): void {
        match ($operation) {
            'create' => $this->logCreate($credit),
            'update' => $this->logUpdate($credit, $original, $changes),
            'delete' => $this->logDelete($credit),
            default => throw new InvalidArgumentException("Unknown operation: {$operation}"),
        };
    }

    private function logCreate(AchievementSetAuthor $credit): void
    {
        $this->logActivity($credit, 'creditCreated', 'Credit added', attributes: [
            'credit_date' => $credit->created_at?->format('M j, Y'),
        ]);
    }

    private function logUpdate(AchievementSetAuthor $credit, array $original, array $changes): void
    {
        if (empty($changes) || !isset($changes['created_at'])) {
            return;
        }

        $oldValue = $original['created_at'] ?? null;
        $newValue = $changes['created_at'];

        $this->logActivity($credit, 'creditUpdated', 'Credit updated',
            old: [
                'credit_date' => $oldValue ? Carbon::parse($oldValue)->format('M j, Y') : null,
            ],
            attributes: [
                'credit_date' => $newValue ? Carbon::parse($newValue)->format('M j, Y') : null,
            ]
        );
    }

    private function logDelete(AchievementSetAuthor $credit): void
    {
        $this->logActivity($credit, 'creditDeleted', 'Credit removed', old: [
            'credit_date' => $credit->created_at?->format('M j, Y'),
        ]);
    }

    private function logActivity(
        AchievementSetAuthor $credit,
        string $event,
        string $description,
        array $old = [],
        array $attributes = [],
    ): void {
        $game = $this->getGameFromCredit($credit);
        if (!$game) {
            return;
        }

        /** @var ?User $user */
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $credit->loadMissing('user');

        $activity = activity()
            ->causedBy($user)
            ->performedOn($game)
            ->withProperty('credit_id', $credit->id)
            ->withProperty('credit_identifier', $this->getCreditIdentifier($credit))
            ->event($event);

        if (!empty($old)) {
            $activity->withProperty('old', $old);
        }
        if (!empty($attributes)) {
            $activity->withProperty('attributes', $attributes);
        }

        $activity->log($description);
    }

    private function getGameFromCredit(AchievementSetAuthor $credit): ?Game
    {
        if (!$credit->achievement_set_id) {
            return null;
        }

        $gameAchievementSet = GameAchievementSet::where('achievement_set_id', $credit->achievement_set_id)->first();

        return $gameAchievementSet?->game;
    }

    private function getCreditIdentifier(AchievementSetAuthor $credit): string
    {
        $userName = $credit->user?->display_name ?? 'Unknown User';
        $taskLabel = $credit->task?->label() ?? 'Unknown';

        return "{$userName} ({$taskLabel})";
    }
}
