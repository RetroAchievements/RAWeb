<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementResource\Pages;

use App\Filament\Actions\ApplyUploadedImageToDataAction;
use App\Filament\Actions\ViewOnSiteAction;
use App\Filament\Concerns\HasFieldLevelAuthorization;
use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\AchievementResource;
use App\Filament\Resources\AchievementResource\Concerns\HasAchievementSetNavigation;
use App\Models\Achievement;
use App\Platform\Actions\SyncEventAchievementMetadataAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class Edit extends EditRecord
{
    use HasFieldLevelAuthorization;
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
            'Edit',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewOnSiteAction::make('view-on-site'),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->authorizeFields($this->record, $data);

        (new ApplyUploadedImageToDataAction())->execute($data, 'image_name', ImageUploadType::AchievementBadge);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->fill($data);

        /** @var Achievement $achievement */
        $achievement = $record;
        (new SyncEventAchievementMetadataAction())->execute($achievement);

        $record->save();

        return $record;
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
                'pageType' => 'edit',
            ])->render()
        );
    }
}
