<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Site\Models\User;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    public function getSubheading(): ?string
    {
        /** @var User $record */
        $record = $this->getRecord();

        return '[' . $record->ID . '] ' . $record->User;
    }
}
