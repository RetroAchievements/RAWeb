<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Pages\ListAuditLog;
use App\Filament\Resources\UserResource;
use App\Site\Models\User;

class ListUserAuditLog extends ListAuditLog
{
    protected static string $resource = UserResource::class;

    public function getSubheading(): ?string
    {
        /** @var User $record */
        $record = $this->getRecord();

        return '[' . $record->ID . '] ' . $record->User;
    }
}
