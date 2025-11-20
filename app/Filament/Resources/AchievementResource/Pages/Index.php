<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementResource\Pages;

use App\Filament\Resources\AchievementResource;
use App\Platform\Enums\AchievementFlag;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas;
use Illuminate\Database\Eloquent\Builder;

class Index extends ListRecords
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
            'all' => Schemas\Components\Tabs\Tab::make(),
            'published' => Schemas\Components\Tabs\Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('Flags', AchievementFlag::OfficialCore->value)),
            'unpublished' => Schemas\Components\Tabs\Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('Flags', AchievementFlag::Unofficial->value)),
        ];
    }
}
