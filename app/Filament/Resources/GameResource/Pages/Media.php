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
use App\Platform\Actions\AddGameScreenshotAction;
use App\Platform\Enums\ScreenshotType;
use App\Rules\DisallowAnimatedImageRule;
use App\Rules\UploadedImageAspectRatioRule;
use App\Rules\ValidScreenshotResolutionRule;
use App\Support\MediaLibrary\Actions\ExtractBannerEdgeColorsAction;
use BackedEnum;
use Closure;
use Exception;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class Media extends EditRecord
{
    use HasFieldLevelAuthorization;

    protected static string $resource = GameResource::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-s-photo';

    public static function getNavigationLabel(): string
    {
        return 'Media';
    }

    public function getRelationManagers(): array
    {
        return [];
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

        return $schema
            ->components([
                Schemas\Components\Section::make('Badge')
                    ->icon('heroicon-s-star')
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
                    ->hidden(!$user->can('updateField', [$schema->model, 'banner'])),

                Schemas\Components\Section::make('Box Art')
                    ->icon('heroicon-s-rectangle-stack')
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
                    ->hidden(!$user->can('updateField', [$schema->model, 'image_box_art_asset_path'])),

                Schemas\Components\Section::make('Title Screenshot')
                    ->icon('heroicon-s-tv')
                    ->schema([
                        Forms\Components\FileUpload::make('image_title_asset_path')
                            ->label('Title')
                            ->disk('livewire-tmp')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->previewable(true)
                            ->rules($this->getScreenshotValidationRules())
                            ->helperText($this->getScreenshotHelperText()),
                    ])
                    ->hidden(!$user->can('updateField', [$schema->model, 'image_title_asset_path'])),

                Schemas\Components\Section::make('In-Game Screenshot')
                    ->icon('heroicon-s-camera')
                    ->schema([
                        // We intentionally use a standard FileUpload instead of SpatieMediaLibraryFileUpload
                        // here. SpatieMediaLibraryFileUpload manages the media collection directly, which
                        // would bypass AddGameScreenshotAction. SHA1 dedup, cap enforcement, legacy PNG
                        // creation, and GameScreenshot record creation would only happen in a fragile
                        // afterSave() reconciliation. By using a standard FileUpload, the action is the
                        // single entry point for all validation and side effects.
                        Forms\Components\FileUpload::make('screenshot_uploads')
                            ->label('Upload New Screenshot')
                            ->disk('livewire-tmp')
                            ->image()
                            ->maxFiles(1)
                            ->maxSize(4096)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->rules($this->getScreenshotValidationRules())
                            ->previewable(true)
                            ->helperText($this->getScreenshotHelperText()),
                    ])
                    ->hidden(!$user->can('updateField', [$schema->model, 'screenshots'])),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->authorizeFields($this->record, $data);

        $action = new ApplyUploadedImageToDataAction();
        $action->execute($data, 'image_icon_asset_path', ImageUploadType::GameBadge);
        $action->execute($data, 'image_title_asset_path', ImageUploadType::GameTitle);
        $action->execute($data, 'image_box_art_asset_path', ImageUploadType::GameBoxArt);

        // Banner is handled by MediaLibrary, not a database column.
        unset($data['banner']);

        // Screenshots are processed in afterSave() via AddGameScreenshotAction.
        unset($data['screenshot_uploads']);

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Game $game */
        $game = $this->record;

        // Process new screenshot uploads through AddGameScreenshotAction.
        $uploads = $this->data['screenshot_uploads'] ?? [];
        if (!empty($uploads)) {
            $addAction = new AddGameScreenshotAction();
            $failureMessages = [];

            foreach ($uploads as $upload) {
                $filePath = storage_path('app/livewire-tmp/' . $upload);
                if (!file_exists($filePath)) {
                    continue;
                }

                $uploadedFile = new UploadedFile($filePath, basename($filePath), test: true);

                try {
                    // Always mark as primary so each new upload replaces the current
                    // ingame screenshot, matching pre-migration behavior where every
                    // upload became "the" screenshot. The action handles demoting the
                    // old primary automatically.
                    $addAction->execute($game, $uploadedFile, ScreenshotType::Ingame, isPrimary: true);
                } catch (ValidationException $e) {
                    $failureMessages[] = collect($e->errors())->flatten()->first();
                }
            }

            if (!empty($failureMessages)) {
                Notification::make()
                    ->warning()
                    ->title('Some screenshots were not uploaded')
                    ->body(implode("\n", array_unique($failureMessages)))
                    ->send();
            }
        }

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

    private function getScreenshotValidationRules(): Closure
    {
        return fn () => array_filter([
            'dimensions:min_width=64,min_height=64,max_width=1920,max_height=1080',
            new DisallowAnimatedImageRule(),
            $this->record?->system
                ? new ValidScreenshotResolutionRule($this->record->system)
                : null,
        ]);
    }

    private function getScreenshotHelperText(): ?string
    {
        $system = $this->record?->system;
        $resolutions = $system?->screenshot_resolutions;
        if (empty($resolutions)) {
            return null;
        }

        $formatted = collect($resolutions)
            ->map(fn (array $r) => "{$r['width']}x{$r['height']}")
            ->join(', ');

        $label = count($resolutions) > 1 ? 'Accepted resolutions' : 'Expected resolution';

        $text = "{$label} for {$system->name}: {$formatted} (or 2x/3x integer multiples where dimensions permit)";

        if ($system->has_analog_tv_output) {
            $text .= '. SMPTE 601 capture resolutions (704x480, 720x480, 720x486, 704x576, 720x576) are also accepted.';
        }

        return $text;
    }
}
