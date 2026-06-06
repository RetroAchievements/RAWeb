<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Pages;

use App\Connect\Actions\SubmitRichPresenceAction;
use App\Filament\Actions\ViewOnSiteAction;
use App\Filament\Concerns\HasFieldLevelAuthorization;
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

    protected function getHeaderActions(): array
    {
        return [
            ViewOnSiteAction::make('view-on-site'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->authorizeFields($this->record, $data);

        // Normalize empty strings to null to prevent spurious activity log entries.
        $nullableTextFields = ['developer', 'publisher', 'genre', 'legacy_guide_url'];
        foreach ($nullableTextFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

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

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->refresh();
        $this->refreshFormData(['sort_title']);
    }
}
