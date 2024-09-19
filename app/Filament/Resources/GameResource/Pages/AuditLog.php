<?php

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\GameResource;
use Illuminate\Support\Collection;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = GameResource::class;

    protected function createFieldLabelMap(): Collection
    {
        $fieldLabelMap = parent::createFieldLabelMap();

        $fieldLabelMap['ImageIcon'] = 'Badge';

        return $fieldLabelMap;
    }
}
