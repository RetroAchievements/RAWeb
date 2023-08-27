<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Community\Models\News;
use Illuminate\Database\Seeder;

class NewsTableSeeder extends Seeder
{
    public function run(): void
    {
        if (News::count() > 0) {
            return;
        }

        News::factory()->count(15)->create();
    }
}
