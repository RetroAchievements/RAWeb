<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Actions\ApplyUploadedImageToDataAction;
use App\Filament\Actions\ViewOnSiteAction;
use App\Filament\Concerns\HasFieldLevelAuthorization;
use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\GameResource;
use App\Models\Game;
use App\Models\User;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use App\Rules\DisallowAnimatedImageRule;
use App\Rules\UploadedImageAspectRatioRule;
use App\Support\MediaLibrary\Actions\ExtractBannerEdgeColorsAction;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class Media extends EditRecord
{
    use HasFieldLevelAuthorization;

    protected static string $resource = GameResource::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-s-photo';

    public static function getNavigationLabel(): string
    {
        return 'Media';
    }

    /**
     * @param  array<string, mixed>  $urlParameters
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(array $urlParameters = []): array
    {
        $items = parent::getNavigationItems($urlParameters);

        if (!isset($urlParameters['record'])) {
            return $items;
        }

        $record = $urlParameters['record'];
        $game = $record instanceof Game
            ? $record->loadMissing(['gameScreenshots', 'media'])
            : Game::with(['gameScreenshots', 'media'])->find($record);

        if (!$game) {
            return $items;
        }

        $missing = static::getMissingMedia($game);

        foreach ($items as $item) {
            if (empty($missing)) {
                $item
                    ->badge('✓', color: 'success')
                    ->badgeTooltip('All media is present');
            } else {
                $item
                    ->badge('!', color: 'danger')
                    ->badgeTooltip('Missing: ' . implode(', ', $missing));
            }
        }

        return $items;
    }

    /**
     * @return array<string>
     */
    protected static function getMissingMedia(Game $game): array
    {
        $missing = [];

        if (!$game->image_icon_asset_path || $game->image_icon_asset_path === Game::PLACEHOLDER_BADGE_PATH) {
            $missing[] = 'Badge';
        }

        if (!$game->image_box_art_asset_path || $game->image_box_art_asset_path === Game::PLACEHOLDER_IMAGE_PATH) {
            $missing[] = 'Box art';
        }

        if ($game->getMedia('banner')->isEmpty()) {
            $missing[] = 'Banner';
        }

        $primaryTypes = $game->gameScreenshots
            ->where('is_primary', true)
            ->where('status', GameScreenshotStatus::Approved)
            ->pluck('type.value')
            ->toArray();

        foreach (ScreenshotType::cases() as $type) {
            if (!in_array($type->value, $primaryTypes)) {
                $missing[] = $type->label() . ' screenshot';
            }
        }

        return $missing;
    }

    public function getRelationManagers(): array
    {
        /** @var Game $game */
        $game = $this->getRecord();

        if ($game->is_media_restricted) {
            return [];
        }

        return [
            GameResource\RelationManagers\GameScreenshotsRelationManager::class,
        ];
    }

    public function getTitle(): string|Htmlable
    {
        /** @var Game $game */
        $game = $this->getRecord();

        return "{$game->title} ({$game->system->name_short}) - Media";
    }

    public function getBreadcrumb(): string
    {
        return 'Media';
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Game $game */
        $game = $this->getRecord();

        $hasFormFields =
            $user->can('updateField', [$game, 'image_icon_asset_path'])
            || $user->can('updateField', [$game, 'image_box_art_asset_path'])
            || $user->can('updateField', [$game, 'banner']);

        if (!$hasFormFields) {
            return [];
        }

        return parent::getFormActions();
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewOnSiteAction::make('view-on-site'),
        ];
    }

    public function form(Schema $schema): Schema
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Game $game */
        $game = $this->getRecord();
        $isMediaRestricted = $game->is_media_restricted;

        return $schema
            ->components([
                Schemas\Components\Section::make('Media Restriction Active')
                    ->icon('heroicon-s-exclamation-triangle')
                    ->schema([
                        Forms\Components\Placeholder::make('media_restriction_notice')
                            ->hiddenLabel()
                            ->content('This game has a media restriction in effect. Most visual media (screenshots, box art, and banner) are hidden from public view.'),
                    ])
                    ->hidden(!$isMediaRestricted),

                Schemas\Components\Grid::make()
                    ->columns(['lg' => 2])
                    ->schema([
                        Schemas\Components\Section::make('Badge')
                            ->icon('heroicon-s-star')
                            ->columnSpan(1)
                            ->schema([
                                Forms\Components\FileUpload::make('image_icon_asset_path')
                                    ->label('Badge')
                                    ->disk('livewire-tmp')
                                    ->image()
                                    ->rules([
                                        'dimensions:width=96,height=96',
                                    ])
                                    ->acceptedFileTypes(['image/png', 'image/jpeg'])
                                    ->maxSize(1024)
                                    ->maxFiles(1)
                                    ->previewable(true),
                            ])
                            ->hidden(!$user->can('updateField', [$schema->model, 'image_icon_asset_path'])),

                        Schemas\Components\Section::make('Box Art')
                            ->icon('heroicon-s-rectangle-stack')
                            ->columnSpan(1)
                            ->schema([
                                Forms\Components\FileUpload::make('image_box_art_asset_path')
                                    ->label('Box Art')
                                    ->disk('livewire-tmp')
                                    ->image()
                                    ->acceptedFileTypes(['image/png', 'image/jpeg'])
                                    ->maxSize(1024)
                                    ->maxFiles(1)
                                    ->previewable(true),
                            ])
                            ->hidden(!$user->can('updateField', [$schema->model, 'image_box_art_asset_path']) || $isMediaRestricted),
                    ]),

                Schemas\Components\Section::make('Banner Image')
                    ->icon('heroicon-s-photo')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('banner')
                            ->label('Banner Image')
                            ->collection('banner')
                            ->conversion('desktop-xl-webp')
                            ->disk('s3')
                            ->visibility('public')
                            ->image()
                            ->rules([
                                'dimensions:min_width=1920,min_height=540',
                                new UploadedImageAspectRatioRule(32 / 9, 0.15),
                                new DisallowAnimatedImageRule(),
                            ])
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->maxSize(5120)
                            ->maxFiles(1)
                            ->customProperties(['is_current' => true])
                            ->filterMediaUsing(fn ($media) => $media->where('custom_properties.is_current', true))
                            ->helperText(new HtmlString('Read: <a href="https://docs.retroachievements.org/guidelines/content/badge-and-icon-guidelines.html#game-page-banners" target="_blank" class="underline">banner rules and guidelines</a>. Upload a high-quality 32:9 ultra-wide banner image (minimum: 1920x540, recommended: 3200x900). The image must be approximately 32:9 aspect ratio (±15% tolerance). The image will be processed to multiple sizes for mobile and desktop. Your image should not include text of any kind.'))
                            ->previewable(true)
                            ->downloadable(false),
                    ])
                    ->hidden(!$user->can('updateField', [$schema->model, 'banner']) || $isMediaRestricted),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->authorizeFields($this->record, $data);

        $action = new ApplyUploadedImageToDataAction();
        $action->execute($data, 'image_icon_asset_path', ImageUploadType::GameBadge);
        $action->execute($data, 'image_box_art_asset_path', ImageUploadType::GameBoxArt);

        // Banner is handled by MediaLibrary, not a database column.
        unset($data['banner']);

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Game $game */
        $game = $this->record;

        // Extract and store edge colors for newly uploaded banners.
        $banner = $game->current_banner_media;
        if ($banner && !$banner->getCustomProperty('left_edge_color')) {
            try {
                $action = new ExtractBannerEdgeColorsAction();

                $fileContents = Storage::disk('s3')->get($banner->getPath());
                $extension = $banner->extension;
                $tempPath = tempnam(sys_get_temp_dir(), 'banner-') . '.' . $extension;
                file_put_contents($tempPath, $fileContents);

                $colors = $action->execute($tempPath);

                unlink($tempPath);

                $banner->setCustomProperty('left_edge_color', $colors['left_edge_color']);
                $banner->setCustomProperty('right_edge_color', $colors['right_edge_color']);
                $banner->save();
            } catch (Exception $e) {
                // Silently fail if color extraction fails - this isn't critical.
            }
        }
    }
}
