<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\Pages;

use App\Connect\Actions\SubmitGameTitleAction;
use App\Filament\Resources\GameResource;
use Filament\Resources\Pages\CreateRecord;

class Create extends CreateRecord
{
    protected static string $resource = GameResource::class;

    protected function afterCreate(): void
    {
        $game = $this->record;

        SubmitGameTitleAction::finalizeNewGame($game);
    }
}
