<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Models\AchievementSetClaim;
use App\Models\User;
use App\Support\Database\Eloquent\Concerns\FakesUsername;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

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
        $user = User::inRandomOrder()->first();

        return [
            'user_id' => $user?->id ?? 1,
            'game_id' => 0,
            'ClaimType' => ClaimType::Primary,
            'SetType' => ClaimSetType::NewSet,
            'Status' => ClaimStatus::Active,
            'Extension' => 0,
            'Special' => ClaimSpecial::None,
            'Finished' => Carbon::now()->addMonths(3),
        ];
    }
}
