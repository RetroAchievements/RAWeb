<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Pages;

use App\Connect\Actions\SubmitRichPresenceAction;
use App\Filament\Actions\ApplyUploadedImageToDataAction;
use App\Filament\Concerns\HasFieldLevelAuthorization;
use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\GameResource;
use App\Models\Game;
use App\Models\User;
use App\Support\MediaLibrary\Actions\ExtractBannerEdgeColorsAction;
use Exception;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Edit extends EditRecord
{
    use HasFieldLevelAuthorization;

    protected static string $resource = GameResource::class;

    public function getTitle(): string|Htmlable
    {
        /** @var Game $game */
        $game = $this->getRecord();

        return "{$game->title} ({$game->system->name_short}) - Edit";
    }

    public function getBreadcrumb(): string
    {
        return 'Edit';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->authorizeFields($this->record, $data);

        $action = new ApplyUploadedImageToDataAction();

        $action->execute($data, 'image_icon_asset_path', ImageUploadType::GameBadge);
        $action->execute($data, 'image_title_asset_path', ImageUploadType::GameTitle);
        $action->execute($data, 'image_ingame_asset_path', ImageUploadType::GameInGame);
        $action->execute($data, 'image_box_art_asset_path', ImageUploadType::GameBoxArt);

        // Handle trigger_definition separately to ensure trigger versioning is captured.
        if (array_key_exists('trigger_definition', $data)) {
            /** @var User $user */
            $user = Auth::user();
            /** @var Game $game */
            $game = $this->record;

            if ($user && $user->can('updateField', [$game, 'trigger_definition'])) {
                (new SubmitRichPresenceAction())->execute($game->id, $data['trigger_definition'] ?? '', $user);
            }

            // Remove from data array so it doesn't get saved directly by Filament.
            unset($data['trigger_definition']);
        }

        // Remove banner from the data array - it's handled by MediaLibrary, not a database column.
        unset($data['banner']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->refresh();
        $this->refreshFormData(['sort_title']);

        /** @var Game $game */
        $game = $this->record;
        $banner = $game->getFirstMedia('banner');

        // Extract and store edge colors.
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
