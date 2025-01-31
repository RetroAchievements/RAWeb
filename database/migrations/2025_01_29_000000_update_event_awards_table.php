<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('event_awards', function (Blueprint $table) {
            $table->renameColumn('achievements_required', 'points_required');
        });
    }

    public function down(): void
    {
        Schema::table('event_awards', function (Blueprint $table) {
            $table->renameColumn('points_required', 'achievements_required');
        });
    }
};
