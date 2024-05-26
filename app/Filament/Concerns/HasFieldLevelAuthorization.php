<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\UnauthorizedException;

trait HasFieldLevelAuthorization
{
    public function authorizeFields(Model|int|string|null $originalFormRecord, array $formData): void
    {
        // The given resource has field-level validation in its policy.
        // Because Livewire-driven forms allow the user to actually modify
        // the data to be sent to the server client-side through the browser dev
        // tools, we need to authorize before saving to the database.

        // Determine what fields have been dirtied by the user.
        $existingData = $originalFormRecord->toArray();
        $dirtyFields = [];
        foreach ($formData as $field => $value) {
            if (array_key_exists($field, $existingData) && $existingData[$field] !== $value) {
                $dirtyFields[$field] = $value;
            }
        }

        // Now that we have the dirty fields, we can perform the authorization.
        /** @var User $user */
        $user = Auth::user();
        foreach ($dirtyFields as $field => $value) {
            if (!$user->can('updateField', [$this->record, $field])) {
                // The only way we've landed here is if there's a major logic error
                // or if the user is doing something they weren't supposed to do.
                // Explode.
                throw new UnauthorizedException("[{$user->User}:{$user->id}] does not have permission to edit {$field}.");
            }
        }
    }
}
