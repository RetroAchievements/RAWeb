<?php

declare(strict_types=1);

namespace Database\Factories\Legacy;

use Illuminate\Support\Carbon;
use LegacyApp\Community\Enums\ClaimSetType;
use LegacyApp\Community\Enums\ClaimSpecial;
use LegacyApp\Community\Enums\ClaimStatus;
use LegacyApp\Community\Enums\ClaimType;
use LegacyApp\Community\Models\AchievementSetClaim;
use LegacyApp\Platform\Models\Achievement;
use LegacyApp\Support\Database\Eloquent\Factory;

/**
 * @extends Factory<Achievement>
 */
class AchievementSetClaimFactory extends Factory
{
    protected $model = AchievementSetClaim::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'User' => $this->fakeUsername(),
            'GameID' => 0,
            'ClaimType' => ClaimType::Primary,
            'SetType' => ClaimSetType::NewSet,
            'Status' => ClaimStatus::Active,
            'Extension' => ClaimStatus::Active,
            'Special' => ClaimSpecial::None,
            'Finished' => Carbon::now(),
        ];
    }
}
