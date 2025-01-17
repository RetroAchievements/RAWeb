<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Achievement;
use App\Models\Trigger;
use App\Models\User;
use App\Platform\Enums\TriggerableType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trigger>
 */
class TriggerFactory extends Factory
{
    protected $model = Trigger::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conditions' => '0xH00fffb=0_0xH00fe10=1_0xH00fe11=2_0xH00f7d0>=32',
            'version' => 1,
            'parent_id' => null,
            'user_id' => User::factory()->create()->id,
            'triggerable_type' => TriggerableType::Achievement,
            'triggerable_id' => Achievement::factory()->create()->id,
        ];
    }
}
