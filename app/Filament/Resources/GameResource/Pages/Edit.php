<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Actions\ApplyUploadedImageToDataAction;
use App\Filament\Concerns\HasFieldLevelAuthorization;
use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\GameResource;
use App\Models\Game;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class Edit extends EditRecord
{
    use HasFieldLevelAuthorization;

    protected static string $resource = GameResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->authorizeFields($this->record, $data);

        $action = new ApplyUploadedImageToDataAction();

        $action->execute($data, 'ImageIcon', ImageUploadType::GameBadge);
        $action->execute($data, 'ImageTitle', ImageUploadType::GameTitle);
        $action->execute($data, 'ImageIngame', ImageUploadType::GameInGame);
        $action->execute($data, 'ImageBoxArt', ImageUploadType::GameBoxArt);

        // Handle RichPresencePatch separately to ensure trigger versioning is captured.
        if (array_key_exists('RichPresencePatch', $data)) {
            /** @var User $user */
            $user = Auth::user();
            /** @var Game $game */
            $game = $this->record;

            if ($user && $user->can('updateField', [$game, 'RichPresencePatch'])) {
                modifyGameRichPresence($user, $game->id, $data['RichPresencePatch'] ?? '');
            }

            // Remove from data array so it doesn't get saved directly by Filament.
            unset($data['RichPresencePatch']);
        }

        return $data;
    }
}
