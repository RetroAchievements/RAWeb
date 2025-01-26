<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Actions\ProcessUploadedImageAction;
use App\Filament\Enums\ImageUploadType;
use App\Filament\Resources\EventResource;
use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\System;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Jobs\UpdateGameMetricsJob;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class Create extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // promote the uploaded image
        $data['image_asset_path'] = (new ProcessUploadedImageAction())->execute(
            $data['image_asset_path'],
            ImageUploadType::GameBadge,
        );

        // create the legacy game record
        $game = Game::create([
            'Title' => 'Temporary Event Title',
            'Publisher' => 'RetroAchievements',
            'ConsoleID' => System::Events,
            'ImageIcon' => $data['image_asset_path'],
        ]);
        $data['legacy_game_id'] = $game->id;

        $numberOfAchievements = (int) $data['numberOfAchievements'];
        unset($data['numberOfAchievements']);
        $user_id = $data['user_id'];
        unset($data['user_id']);

        // create the event record
        $event = static::getModel()::create($data);

        // create the number of requested achievements
        for ($i = 0; $i < $numberOfAchievements; $i++) {
            $achievement = Achievement::create([
                'Title' => 'Placeholder',
                'Description' => 'TBD',
                'MemAddr' => '0=1',
                'Flags' => AchievementFlag::OfficialCore->value,
                'GameID' => $game->id,
                'user_id' => $user_id,
                'BadgeName' => '00000',
                'DisplayOrder' => $i + 1,
            ]);

            EventAchievement::create([
                'achievement_id' => $achievement->id,
            ]);
        }

        // update metrics and sync to game_achievement_set
        dispatch(new UpdateGameMetricsJob($game->id))->onQueue('game-metrics');

        return $event;
    }
}
