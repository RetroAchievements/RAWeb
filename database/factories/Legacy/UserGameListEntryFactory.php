<?php

declare(strict_types=1);

namespace Database\Factories\Legacy;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use LegacyApp\Platform\Models\UserGameListEntry;

/**
 * @extends Factory<UserGameListEntry>
 */
class UserGameListEntryFactory extends Factory
{
    protected $model = UserGameListEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'User' => $this->fakeUsername(),
            'GameID' => random_int(1, 10000),
            'Updated' => Carbon::now(),
        ];
    }
}
