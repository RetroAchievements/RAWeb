<?php

namespace App\Filament\Resources\AchievementResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\AchievementResource;
use Illuminate\Support\Collection;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = AchievementResource::class;

    protected function createFieldLabelMap(): Collection
    {
        $fieldLabelMap = parent::createFieldLabelMap();

        $fieldLabelMap['BadgeName'] = 'Badge';

        return $fieldLabelMap;
    }
}
