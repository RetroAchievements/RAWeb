<?php

declare(strict_types=1);

namespace Database\Factories\Legacy;

use Illuminate\Database\Eloquent\Factories\Factory;
use LegacyApp\Community\Enums\ActivityType;
use LegacyApp\Site\Models\UserActivity;
use LegacyApp\Support\Database\Eloquent\FakesUsername;

/**
 * @extends Factory<UserActivity>
 */
class UserActivityFactory extends Factory
{
    use FakesUsername;

    protected $model = UserActivity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activitytype' => ActivityType::Login,
            'User' => $this->fakeUsername(),
        ];
    }
}
