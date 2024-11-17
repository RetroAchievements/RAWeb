<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameHash;
use App\Models\PlayerSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlayerSession>
 */
class PlayerSessionFactory extends Factory
{
    protected $model = PlayerSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::inRandomOrder()->first();
        $game = Game::inRandomOrder()->first();
        $gameHash = GameHash::inRandomOrder()->first();

        $userAgents = [
            'RetroArch/1.19.1 (Windows 8 x64 Build 9200 6.2) mupen64plus_next_libretro/2.6-Vulkan_5d2ac21',
            'HorizonXI/1.1.0 (Server)',
            'RetroArch/1.19.1 (Linux 6.1) mesen_libretro/0.9.9',
            'RetroArch/1.19.1 (Android 14.0) mgba_libretro_android/0.11-dev_3e349d5',
            'PCSX2 v2.1.100 (SteamOS 20240422.1)',
            'PCSX2 v2.1.188 (Microsoft Windows 10+)',
            'Dolphin/2409',
            'RetroArch/1.15.0 (Android 12.0) swanstation_libretro_android/1.0.0_37cd87e',
            'AetherSX2 v1.5-3460-g47010a522 (Android)',
            'PPSSPP/v1.17.1',
            '[not provided]',
            null,
        ];

        return [
            'user_id' => $user->id,
            'game_hash_set_id' => null,
            'game_hash_id' => $gameHash?->id,
            'game_id' => $game->id,
            'hardcore' => fake()->boolean(),
            'rich_presence' => fake()->sentence(),
            'rich_presence_updated_at' => fake()->optional()->dateTime(),
            'duration' => fake()->numberBetween(1, 90),
            'user_agent' => fake()->randomElement($userAgents),
            'ip_address' => fake()->ipv4(),
        ];
    }
}
