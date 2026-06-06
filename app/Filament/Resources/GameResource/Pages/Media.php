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
use App\Platform\Actions\RecordGameBadgeChangeAction;
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
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use League\Flysystem\UnableToCheckFileExistence;
use Throwable;

class Media extends EditRecord
{
    use HasFieldLevelAuthorization;

    /**
     * @var array<string, array{clearFlag: string, placeholderPath: string, uploadType: ImageUploadType}>
     */
    private const LEGACY_IMAGE_FIELDS = [
        'image_icon_asset_path' => [
            'clearFlag' => 'clear_badge',
            'placeholderPath' => Game::PLACEHOLDER_BADGE_PATH,
            'uploadType' => ImageUploadType::GameBadge,
        ],
        'image_box_art_asset_path' => [
            'clearFlag' => 'clear_box_art',
            'placeholderPath' => Game::PLACEHOLDER_IMAGE_PATH,
            'uploadType' => ImageUploadType::GameBoxArt,
        ],
    ];

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
            GameResource\RelationManagers\GameBadgesRelationManager::class,
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

        $badgeImageField = self::LEGACY_IMAGE_FIELDS['image_icon_asset_path'];
        $boxArtImageField = self::LEGACY_IMAGE_FIELDS['image_box_art_asset_path'];

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
                                Forms\Components\Hidden::make($badgeImageField['clearFlag'])
                                    ->default(false),

                                $this->makeLegacyGameImageUpload(
                                    field: 'image_icon_asset_path',
                                    label: 'Badge',
                                    clearFlag: $badgeImageField['clearFlag'],
                                    currentPath: $game->image_icon_asset_path,
                                    placeholderPath: $badgeImageField['placeholderPath'],
                                )->rules([
                                    'dimensions:width=96,height=96',
                                ]),
                            ])
                            ->hidden(!$user->can('updateField', [$schema->model, 'image_icon_asset_path'])),

                        Schemas\Components\Section::make('Box Art')
                            ->icon('heroicon-s-rectangle-stack')
                            ->columnSpan(1)
                            ->schema([
                                Forms\Components\Hidden::make($boxArtImageField['clearFlag'])
                                    ->default(false),

                                $this->makeLegacyGameImageUpload(
                                    field: 'image_box_art_asset_path',
                                    label: 'Box Art',
                                    clearFlag: $boxArtImageField['clearFlag'],
                                    currentPath: $game->image_box_art_asset_path,
                                    placeholderPath: $boxArtImageField['placeholderPath'],
                                ),
                            ])
                            ->hidden(
                                !$user->can('updateField', [$schema->model, 'image_box_art_asset_path'])
                                || $isMediaRestricted
                            ),
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
        /** @var Game $game */
        $game = $this->record;

        $authorizationData = $data;
        $this->markLegacyImageClearsForAuthorization($authorizationData, $game);

        $this->authorizeFields($game, $authorizationData);

        $action = new ApplyUploadedImageToDataAction();
        foreach (self::LEGACY_IMAGE_FIELDS as $field => $config) {
            $this->applyLegacyImageFieldUpdate(
                data: $data,
                field: $field,
                clearFlag: $config['clearFlag'],
                uploadType: $config['uploadType'],
                placeholderPath: $config['placeholderPath'],
                action: $action,
            );
        }

        // Banner is handled by MediaLibrary, not a database column.
        unset($data['banner']);
        foreach (self::LEGACY_IMAGE_FIELDS as $config) {
            unset($data[$config['clearFlag']]);
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Game $record */
        $originalIconPath = $record->image_icon_asset_path;
        $newIconPath = $data['image_icon_asset_path'] ?? $originalIconPath;
        $didIconPathChange = $newIconPath !== $originalIconPath;

        return DB::transaction(function () use ($record, $data, $didIconPathChange, $newIconPath) {
            /** @var Game $record */
            $record = parent::handleRecordUpdate($record, $data);

            if ($didIconPathChange) {
                (new RecordGameBadgeChangeAction())->execute(
                    game: $record,
                    imageAssetPath: $newIconPath,
                    uploadedBy: Auth::user(),
                );
            }

            return $record;
        });
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

    private function resolveLegacyImageUpload(
        Forms\Components\BaseFileUpload $component,
        string $file,
        string $placeholderPath,
    ): ?array {
        if ($file === $placeholderPath) {
            return null;
        }

        if ($this->isLegacyGameImagePath($file)) {
            return [
                'name' => basename($file),
                'size' => 0,
                'type' => null,
                'url' => media_asset($file),
            ];
        }

        $disk = $component->getDisk();

        // If the temp file has expired or is otherwise gone, treat it as missing.
        try {
            if (!$disk->exists($file)) {
                return null;
            }
        } catch (UnableToCheckFileExistence $exception) {
            return null;
        }

        $url = null;

        if ($component->getVisibility() === 'private') {
            // Some local envs can throw here when they do not provide signed temporary URLs,
            // so fall back to the normal disk URL instead of failing to render the upload state.
            try {
                $url = $disk->temporaryUrl(
                    $file,
                    now()->addMinutes(30)->endOfHour(),
                );
            } catch (Throwable $exception) {
                // this driver does not support creating temporary URLs
            }
        }

        $url ??= $disk->url($file);

        return [
            'name' => basename($file),
            'size' => $disk->size($file),
            'type' => $disk->mimeType($file),
            'url' => $url,
        ];
    }

    private function makeLegacyGameImageUpload(
        string $field,
        string $label,
        string $clearFlag,
        ?string $currentPath,
        string $placeholderPath,
    ): Forms\Components\FileUpload {
        return Forms\Components\FileUpload::make($field)
            ->label($label)
            ->disk('livewire-tmp')
            ->afterStateHydrated(function (
                Forms\Components\BaseFileUpload $component,
                string|array|null $rawState,
            ) use ($placeholderPath): void {
                $this->hydrateGameImageUploadState(
                    $component,
                    $rawState,
                    $placeholderPath,
                );
            })
            ->getUploadedFileUsing(
                fn (
                    Forms\Components\BaseFileUpload $component,
                    string $file,
                ): ?array => $this->resolveLegacyImageUpload(
                    $component,
                    $file,
                    $placeholderPath,
                ),
            )
            ->live()
            ->afterStateUpdated(function (
                Set $set,
                string|array|null $state,
                string|array|null $old,
            ) use ($clearFlag, $currentPath, $placeholderPath): void {
                $set($clearFlag, $this->shouldClearLegacyImage(
                    state: $state,
                    old: $old,
                    currentPath: $currentPath,
                    placeholderPath: $placeholderPath,
                ));
            })
            ->image()
            ->acceptedFileTypes(['image/png', 'image/jpeg'])
            ->maxSize(1024)
            ->maxFiles(1)
            ->previewable(true);
    }

    private function shouldClearLegacyImage(
        string|array|null $state,
        string|array|null $old,
        ?string $currentPath,
        string $placeholderPath,
    ): bool {
        return
            blank($state)
            && (
                $this->isLegacyGameImagePath($old)
                || ($old === null && filled($currentPath) && $currentPath !== $placeholderPath)
            );
    }

    private function hydrateGameImageUploadState(
        Forms\Components\BaseFileUpload $component,
        string|array|null $rawState,
        string $placeholderPath,
    ): void {
        $disk = $component->getDisk();

        $component->rawState(
            array_filter(
                Arr::wrap($rawState),
                function (string $file) use ($disk, $placeholderPath): bool {
                    if (blank($file) || $file === $placeholderPath) {
                        return false;
                    }

                    if ($this->isLegacyGameImagePath($file)) {
                        return true;
                    }

                    try {
                        return $disk->exists($file);
                    } catch (UnableToCheckFileExistence $exception) {
                        return false;
                    }
                },
            ),
        );
    }

    private function applyLegacyImageFieldUpdate(
        array &$data,
        string $field,
        string $clearFlag,
        ImageUploadType $uploadType,
        string $placeholderPath,
        ApplyUploadedImageToDataAction $action,
    ): void {
        $shouldClear = (bool) ($data[$clearFlag] ?? false);
        $path = $data[$field] ?? null;

        if ($this->isLegacyGameImagePath($path)) {
            unset($data[$field]);
        } else {
            $action->execute($data, $field, $uploadType);
        }

        if ($shouldClear && empty($data[$field])) {
            $data[$field] = $placeholderPath;
        }
    }

    private function markLegacyImageClearsForAuthorization(array &$data, Game $game): void
    {
        foreach (self::LEGACY_IMAGE_FIELDS as $field => $config) {
            $currentPath = $game->getAttributeValue($field);

            if (
                (bool) ($data[$config['clearFlag']] ?? false)
                && filled($currentPath)
                && $currentPath !== $config['placeholderPath']
            ) {
                $data[$field] = $config['placeholderPath'];
            }
        }
    }

    private function isLegacyGameImagePath(mixed $path): bool
    {
        return is_string($path) && str_starts_with($path, '/Images/');
    }
}
