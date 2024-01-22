<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementResource\Pages;

use App\Filament\Resources\AchievementResource;
use App\Platform\Enums\AchievementFlag;
use Filament\Actions;
use Filament\Resources\Components;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAchievements extends ListRecords
{
    protected static string $resource = AchievementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Components\Tab::make(),
            'published' => Components\Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('Flags', AchievementFlag::OfficialCore)),
            'unpublished' => Components\Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('Flags', AchievementFlag::Unofficial)),
        ];
    }
}
