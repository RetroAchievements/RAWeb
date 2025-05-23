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

        $fieldLabelMap['release_title'] = 'Release Title';
        $fieldLabelMap['release_region'] = 'Release Region';
        $fieldLabelMap['release_date'] = 'Release Date';
        $fieldLabelMap['release_is_canonical'] = 'Is Canonical Title';

        return $fieldLabelMap;
    }
}
