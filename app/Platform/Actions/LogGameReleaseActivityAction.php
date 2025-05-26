<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameRelease;
use App\Models\User;
use App\Platform\Enums\GameReleaseRegion;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class LogGameReleaseActivityAction
{
    public function execute(
        string $operation,
        GameRelease $gameRelease,
        array $original = [],
        array $changes = []
    ): void {
        match ($operation) {
            'create' => $this->logCreate($gameRelease),
            'update' => $this->logUpdate($gameRelease, $original, $changes),
            'delete' => $this->logDelete($gameRelease),
            default => throw new InvalidArgumentException("Unknown operation: {$operation}"),
        };
    }

    private function logCreate(GameRelease $gameRelease): void
    {
        /** @var User $user */
        $user = Auth::user();

        $releaseIdentifier = $gameRelease->title;
        if ($gameRelease->region) {
            $releaseIdentifier .= ' (' . $gameRelease->region->label() . ')';
        }

        activity()
            ->causedBy($user)
            ->performedOn($gameRelease->game)
            ->withProperty('attributes', [
                'release_title' => $gameRelease->title,
                'release_region' => $gameRelease->region?->label(),
                'release_date' => (new FormatGameReleaseDateAction())->execute(
                    $gameRelease->released_at,
                    $gameRelease->released_at_granularity
                ),
                'release_is_canonical' => $gameRelease->is_canonical_game_title ? 'Yes' : 'No',
            ])
            ->withProperty('release_id', $gameRelease->id)
            ->withProperty('release_identifier', $releaseIdentifier)
            ->event('releaseCreated')
            ->log('Release added');
    }

    private function logUpdate(GameRelease $gameRelease, array $original, array $changes): void
    {
        if (empty($changes)) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        $oldData = [];
        $newData = [];

        // Track if we need to create a combined release date field.
        $wasDateChanged = false;
        $wasGranularityChanged = false;

        foreach ($changes as $key => $newValue) {
            if (in_array($key, ['title', 'region', 'released_at', 'released_at_granularity', 'is_canonical_game_title'])) {
                $releaseKey = match ($key) {
                    'title' => 'release_title',
                    'region' => 'release_region',
                    'released_at' => 'release_date',
                    'released_at_granularity' => 'release_date_granularity',
                    'is_canonical_game_title' => 'release_is_canonical',
                    default => 'release_' . $key,
                };

                $oldValue = $original[$key] ?? null;

                // Format specific fields for better readability
                if ($key === 'region') {
                    $oldData[$releaseKey] = $oldValue instanceof GameReleaseRegion
                        ? $oldValue->label()
                        : ($oldValue ? GameReleaseRegion::tryFrom($oldValue)?->label() : null);

                    $newData[$releaseKey] = $newValue instanceof GameReleaseRegion
                        ? $newValue->label()
                        : ($newValue ? GameReleaseRegion::tryFrom($newValue)?->label() : null);
                } elseif ($key === 'released_at') {
                    $wasDateChanged = true;
                    // Don't add individual date fields, we'll combine them below.
                } elseif ($key === 'released_at_granularity') {
                    $wasGranularityChanged = true;
                    // Don't add individual granularity fields, we'll combine them below.
                } elseif ($key === 'is_canonical_game_title') {
                    $oldData[$releaseKey] = $oldValue ? 'Yes' : 'No';
                    $newData[$releaseKey] = $newValue ? 'Yes' : 'No';
                } else {
                    $oldData[$releaseKey] = $oldValue;
                    $newData[$releaseKey] = $newValue;
                }
            }
        }

        // Handle combined release date formatting if either date or granularity changed.
        if ($wasDateChanged || $wasGranularityChanged) {
            $oldData['release_date'] = (new FormatGameReleaseDateAction())->execute(
                $original['released_at'] ?? null,
                $original['released_at_granularity'] ?? null
            );
            $newData['release_date'] = (new FormatGameReleaseDateAction())->execute(
                $gameRelease->released_at,
                $gameRelease->released_at_granularity
            );
        }

        $releaseIdentifier = $gameRelease->title;
        if ($gameRelease->region) {
            $releaseIdentifier .= ' (' . $gameRelease->region->label() . ')';
        }

        activity()
            ->causedBy($user)
            ->performedOn($gameRelease->game)
            ->withProperty('old', $oldData)
            ->withProperty('attributes', $newData)
            ->withProperty('release_id', $gameRelease->id)
            ->withProperty('release_identifier', $releaseIdentifier)
            ->event('releaseUpdated')
            ->log('Release updated');
    }

    private function logDelete(GameRelease $gameRelease): void
    {
        /** @var User $user */
        $user = Auth::user();

        $releaseIdentifier = $gameRelease->title;
        if ($gameRelease->region) {
            $releaseIdentifier .= ' (' . $gameRelease->region->label() . ')';
        }

        activity()
            ->causedBy($user)
            ->performedOn($gameRelease->game)
            ->withProperty('old', [
                'release_title' => $gameRelease->title,
                'release_region' => $gameRelease->region?->label(),
                'release_date' => (new FormatGameReleaseDateAction())->execute(
                    $gameRelease->released_at,
                    $gameRelease->released_at_granularity
                ),
                'release_is_canonical' => $gameRelease->is_canonical_game_title ? 'Yes' : 'No',
            ])
            ->withProperty('release_id', $gameRelease->id)
            ->withProperty('release_identifier', $releaseIdentifier)
            ->event('releaseDeleted')
            ->log('Release removed');
    }
}
