<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementSetClaimResource\Pages;

use App\Filament\Resources\AchievementSetClaimResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class Index extends ListRecords
{
    protected static string $resource = AchievementSetClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
