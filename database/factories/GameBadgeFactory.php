<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameBadge;
use App\Platform\Enums\GameBadgeAttribution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameBadge>
 */
class GameBadgeFactory extends Factory
{
    protected $model = GameBadge::class;

    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'image_asset_path' => '/Images/' . str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT) . '.png',
            'sha1' => sha1(fake()->uuid()),
            'attribution_source' => GameBadgeAttribution::Live,
            'uploaded_by_user_id' => null,
            'became_current_at' => now(),
            'replaced_at' => null,
        ];
    }
}
