<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Enums\GameHashCompatibility;
use App\Models\GameHash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class LogGameHashActivityAction
{
    public function execute(
        string $operation,
        GameHash $gameHash,
        array $original = [],
        array $changes = [],
    ): void {
        match ($operation) {
            'link' => $this->logLink($gameHash),
            'update' => $this->logUpdate($gameHash, $original, $changes),
            'unlink' => $this->logUnlink($gameHash),
            default => throw new InvalidArgumentException("Unknown operation: {$operation}"),
        };
    }

    private function logLink(GameHash $gameHash): void
    {
        /** @var User $user */
        $user = Auth::user();

        activity()
            ->causedBy($user)
            ->performedOn($gameHash->game)
            ->withProperty('attributes', [
                'hash_name' => $gameHash->name,
                'hash_md5' => $gameHash->md5,
                'hash_labels' => $gameHash->labels,
            ])
            ->withProperty('hash_id', $gameHash->id)
            ->withProperty('hash_identifier', $gameHash->md5)
            ->event('linkedHash')
            ->log('Hash linked');
    }

    private function logUpdate(GameHash $gameHash, array $original, array $changes): void
    {
        if (empty($changes)) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        $oldData = [];
        $newData = [];

        $trackedFields = $gameHash->getActivitylogOptions()->logAttributes;

        foreach ($changes as $key => $newValue) {
            if (in_array($key, $trackedFields)) {
                $hashKey = match ($key) {
                    'name' => 'hash_name',
                    'labels' => 'hash_labels',
                    'compatibility' => 'hash_compatibility',
                    'patch_url' => 'hash_patch_url',
                    'source' => 'hash_source',
                    default => 'hash_' . $key,
                };

                $oldValue = $original[$key] ?? null;

                // Format compatibility enum for readability.
                if ($key === 'compatibility') {
                    $oldData[$hashKey] = $oldValue instanceof GameHashCompatibility
                        ? $oldValue->label()
                        : ($oldValue ? GameHashCompatibility::tryFrom($oldValue)?->label() : null);

                    $newData[$hashKey] = $newValue instanceof GameHashCompatibility
                        ? $newValue->label()
                        : ($newValue ? GameHashCompatibility::tryFrom($newValue)?->label() : null);
                } else {
                    $oldData[$hashKey] = $oldValue;
                    $newData[$hashKey] = $newValue;
                }
            }
        }

        // If no tracked fields were changed, bail.
        if (empty($newData)) {
            return;
        }

        activity()
            ->causedBy($user)
            ->performedOn($gameHash->game)
            ->withProperty('old', $oldData)
            ->withProperty('attributes', $newData)
            ->withProperty('hash_id', $gameHash->id)
            ->withProperty('hash_identifier', $gameHash->md5)
            ->event('updatedHash')
            ->log('Hash updated');
    }

    private function logUnlink(GameHash $gameHash): void
    {
        /** @var User $user */
        $user = Auth::user();

        activity()
            ->causedBy($user)
            ->performedOn($gameHash->game)
            ->withProperty('old', [
                'hash_name' => $gameHash->name,
                'hash_md5' => $gameHash->md5,
                'hash_labels' => $gameHash->labels,
            ])
            ->withProperty('hash_id', $gameHash->id)
            ->withProperty('hash_identifier', $gameHash->md5)
            ->event('unlinkedHash')
            ->log('Hash unlinked');
    }
}
