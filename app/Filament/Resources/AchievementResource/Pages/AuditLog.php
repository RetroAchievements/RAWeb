<?php

namespace App\Filament\Resources\AchievementResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\AchievementResource;
use App\Models\Achievement;
use Illuminate\Support\Collection;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = AchievementResource::class;

    /**
     * @return Collection<string, mixed>
     */
    protected function createFieldLabelMap(): Collection
    {
        $fieldLabelMap = parent::createFieldLabelMap();

        $fieldLabelMap['BadgeName'] = 'Badge';
        $fieldLabelMap['image_name'] = 'Badge';

        $fieldLabelMap['credit_date'] = 'Date Credited';

        return $fieldLabelMap;
    }

    public function getBreadcrumbs(): array
    {
        /** @var Achievement $achievement */
        $achievement = $this->record;
        $game = $achievement->game;

        return [
            route('filament.admin.resources.achievements.index') => 'Achievements',
            route('filament.admin.resources.games.view', $game) => $game->title,
            route('filament.admin.resources.achievements.view', $achievement) => $achievement->title,
            'Audit Log',
        ];
    }
}
