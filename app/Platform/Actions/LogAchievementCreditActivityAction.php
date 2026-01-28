<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\AchievementAuthor;
use App\Models\User;
use App\Platform\Enums\AchievementAuthorTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class LogAchievementCreditActivityAction
{
    public function execute(
        string $operation,
        AchievementAuthor $credit,
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

    private function logCreate(AchievementAuthor $credit): void
    {
        $this->logActivity($credit, 'creditCreated', 'Credit added', attributes: [
            'credit_date' => $credit->created_at?->format('M j, Y'),
        ]);
    }

    private function logUpdate(AchievementAuthor $credit, array $original, array $changes): void
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

    private function logDelete(AchievementAuthor $credit): void
    {
        $this->logActivity($credit, 'creditDeleted', 'Credit removed', old: [
            'credit_date' => $credit->created_at?->format('M j, Y'),
        ]);
    }

    private function logActivity(
        AchievementAuthor $credit,
        string $event,
        string $description,
        array $old = [],
        array $attributes = [],
    ): void {
        $achievement = $this->getAchievementFromCredit($credit);
        if (!$achievement) {
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
            ->performedOn($achievement)
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

    private function getAchievementFromCredit(AchievementAuthor $credit): ?Achievement
    {
        if (!$credit->achievement_id) {
            return null;
        }

        return Achievement::find($credit->achievement_id);
    }

    private function getCreditIdentifier(AchievementAuthor $credit): string
    {
        $userName = $credit->user?->display_name ?? 'Unknown User';
        $taskLabel = AchievementAuthorTask::tryFrom($credit->task)?->label() ?? 'Unknown';

        return "{$userName} ({$taskLabel})";
    }
}
