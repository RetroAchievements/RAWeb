<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\GameResource;
use App\Models\Game;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = GameResource::class;

    public function getTitle(): string|Htmlable
    {
        /** @var Game $game */
        $game = $this->getRecord();

        return "{$game->title} ({$game->system->name_short}) - Audit Log";
    }

    public function getBreadcrumb(): string
    {
        return 'Audit Log';
    }

    /**
     * @return Collection<string, mixed>
     */
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
