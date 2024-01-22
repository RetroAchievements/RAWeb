<?php

namespace App\Filament\Resources\SystemResource\Pages;

use App\Filament\Pages\ListAuditLog;
use App\Filament\Resources\SystemResource;
use App\Site\Models\User;

class ListSystemAuditLog extends ListAuditLog
{
    protected static string $resource = SystemResource::class;

    public function getSubheading(): ?string
    {
        /** @var User $record */
        $record = $this->getRecord();

        return '[' . $record->ID . '] ' . $record->name_full;
    }
}
