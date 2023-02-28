<?php

namespace Database\Factories\Legacy;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use LegacyApp\Community\Enums\ClaimSetType;
use LegacyApp\Community\Enums\ClaimSpecial;
use LegacyApp\Community\Enums\ClaimStatus;
use LegacyApp\Community\Enums\ClaimType;
use LegacyApp\Community\Models\AchievementSetClaim;
use LegacyApp\Support\Database\Eloquent\FakesUsername;

/**
 * @extends Factory<AchievementSetClaim>
 */
class AchievementSetClaimFactory extends Factory
{
    use FakesUsername;

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
            'Extension' => 0,
            'Special' => ClaimSpecial::None,
            'Finished' => Carbon::now()->addMonths(3),
        ];
    }
}
