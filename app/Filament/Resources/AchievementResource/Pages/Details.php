<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementResource\Pages;

use App\Filament\Actions\ViewOnSiteAction;
use App\Filament\Resources\AchievementResource;
use App\Filament\Resources\AchievementResource\Concerns\HasAchievementSetNavigation;
use App\Models\Achievement;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class Details extends ViewRecord
{
    use HasAchievementSetNavigation;

    protected static string $resource = AchievementResource::class;

    public function getBreadcrumbs(): array
    {
        /** @var Achievement $achievement */
        $achievement = $this->record;
        $game = $achievement->game;

        return [
            route('filament.admin.resources.achievements.index') => 'Achievements',
            route('filament.admin.resources.games.view', $game) => $game->title,
            route('filament.admin.resources.achievements.view', $achievement) => $achievement->title,
            'View',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewOnSiteAction::make('view-on-site'),
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        $navData = $this->getAchievementSetNavigationData();
        if (!$navData) {
            return null;
        }

        return new HtmlString(
            view('filament.resources.achievement-resource.partials.achievement-navigator', [
                'navData' => $navData,
                'pageType' => 'view',
            ])->render()
        );
    }
}
