<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\GameSet;
use App\Platform\Enums\GameSetType;
use Illuminate\Database\Seeder;

class GameSetsSeeder extends Seeder
{
    // These hubs are always present in a database and,
    // at the time of writing, appear in the site navbar.
    private const STANDARD_HUBS = [
        '[Central]' => GameSet::CentralHubId,
        '[Central - Genre & Subgenre]' => GameSet::GenreSubgenreHubId,
        '[Central - Series]' => GameSet::SeriesHubId,
        '[Central - Community Events]' => GameSet::CommunityEventsHubId,
        '[Central - Developer Events]' => GameSet::DeveloperEventsHubId,
    ];

    public function run(): void
    {
        foreach (self::STANDARD_HUBS as $title => $id) {
            GameSet::unguard(); // temporarily allow filling the "id" field
            GameSet::create([
                'id' => $id,
                'title' => $title,
                'type' => GameSetType::Hub,
            ]);
            GameSet::reguard();
        }
    }
}
