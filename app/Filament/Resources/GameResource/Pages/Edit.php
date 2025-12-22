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
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

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
                (new SubmitRichPresenceAction())->execute($game->id, $data['RichPresencePatch'] ?? '', $user);
            }

            // Remove from data array so it doesn't get saved directly by Filament.
            unset($data['RichPresencePatch']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->refresh();
        $this->refreshFormData(['sort_title']);
    }
}
