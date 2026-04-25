<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Actions\ViewOnSiteAction;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\MuteForm;
use App\Models\User;
use Carbon\Carbon;
use Filament\Resources\Pages\EditRecord;

class Edit extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewOnSiteAction::make('view-on-site'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User $record */
        $record = $this->record;

        $wasUnranked = $record->unranked_at !== null;
        $isNowUnranked = $data['is_unranked'] ?? false;

        if ($wasUnranked !== $isNowUnranked) {
            $data['unranked_at'] = $isNowUnranked ? Carbon::now() : null;
        }

        $data['muted_until'] = MuteForm::resolveMutedUntil(
            $record,
            $data['mute_action'] ?? null,
            $data['custom_muted_until'] ?? null,
        );

        // Remove the Filament form's virtual fields before saving.
        unset(
            $data['custom_muted_until'],
            $data['is_unranked'],
            $data['mute_action']
        );

        return $data;
    }
}
