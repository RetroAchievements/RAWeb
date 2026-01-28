<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('leaderboards')
            ->where('state', 'unpublished')
            ->update(['state' => 'unpromoted']);
    }

    public function down(): void
    {
        DB::table('leaderboards')
            ->where('state', 'unpromoted')
            ->update(['state' => 'unpublished']);
    }
};
