<?php

namespace App\Filament\Resources\AchievementSetResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\AchievementSetResource;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = AchievementSetResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.achievement-sets.view', $this->record) => 'Achievement Set #' . $this->record->id,
            'Audit Log',
        ];
    }
}
