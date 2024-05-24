<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ForumTopic;
use Illuminate\Database\Seeder;

class ForumTopicSeeder extends Seeder
{
    public function run(): void
    {
        if (ForumTopic::count() > 0) {
            return;
        }

        ForumTopic::factory()->count(15)->create();
    }
}
