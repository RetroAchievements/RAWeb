<?php

declare(strict_types=1);

namespace Database\Factories\Legacy;

use Illuminate\Database\Eloquent\Factories\Factory;
use LegacyApp\Community\Enums\AwardType;
use LegacyApp\Platform\Models\PlayerBadge;
use LegacyApp\Support\Database\Eloquent\FakesUsername;

/**
 * @extends Factory<PlayerBadge>
 */
class PlayerBadgeFactory extends Factory
{
    use FakesUsername;

    protected $model = PlayerBadge::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'User' => $this->fakeUsername(),
            'AwardType' => AwardType::Mastery,
            'AwardData' => fake()->numberBetween(0, 9999) * 10,
            'AwardDataExtra' => 0,
            'DisplayOrder' => 0,
        ];
    }
}
