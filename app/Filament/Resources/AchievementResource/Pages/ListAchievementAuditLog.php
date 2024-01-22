<?php

namespace App\Filament\Resources\AchievementResource\Pages;

use App\Filament\Pages\ListAuditLog;
use App\Filament\Resources\AchievementResource;
use App\Platform\Models\Achievement;

class ListAchievementAuditLog extends ListAuditLog
{
    protected static string $resource = AchievementResource::class;

    public function getSubheading(): ?string
    {
        /** @var Achievement $record */
        $record = $this->getRecord();

        return '[' . $record->ID . '] ' . $record->Title;
    }
}
