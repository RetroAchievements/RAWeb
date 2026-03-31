<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\UnrankedUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnrankedUser>
 */
class UnrankedUserFactory extends Factory
{
    protected $model = UnrankedUser::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
        ];
    }
}
