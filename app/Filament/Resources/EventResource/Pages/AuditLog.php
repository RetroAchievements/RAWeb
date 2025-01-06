<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\EventResource;
use Illuminate\Support\Collection;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = EventResource::class;

    protected function createFieldLabelMap(): Collection
    {
        $fieldLabelMap = parent::createFieldLabelMap();

        $fieldLabelMap['ImageIcon'] = 'Badge';

        return $fieldLabelMap;
    }
}
