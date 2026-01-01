<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Actions\ApplyUploadedImageToDataAction;
use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\EventResource;
use App\Models\Event;
use App\Models\Game;
use App\Models\System;
use App\Platform\Actions\AddAchievementsToEventAction;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class Create extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // promote the uploaded image
        (new ApplyUploadedImageToDataAction())->execute($data, 'image_asset_path', ImageUploadType::GameBadge);

        // use the default image if no image given or image processing failed
        if (!isset($data['image_asset_path'])) {
            $data['image_asset_path'] = '/Images/000001.png';
        }

        // create the legacy game record
        $game = Game::create([
            'title' => 'Temporary Event Title',
            'publisher' => 'RetroAchievements',
            'system_id' => System::Events,
            'image_icon_asset_path' => $data['image_asset_path'],
        ]);
        $data['legacy_game_id'] = $game->id;

        // these fields don't actually exist on the event record. don't pass to create().
        $numberOfAchievements = (int) $data['numberOfAchievements'];
        unset($data['numberOfAchievements']);
        $user_id = (int) $data['user_id'];
        unset($data['user_id']);

        // create the event record
        /** @var Event $event */
        $event = static::getModel()::create($data);

        // create the number of requested achievements
        (new AddAchievementsToEventAction())->execute($event, $numberOfAchievements, $user_id);

        return $event;
    }
}
