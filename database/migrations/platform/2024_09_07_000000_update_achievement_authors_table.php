<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * With the current unique constraint on achievement_authors, the same
 * user_id cannot be credited for different tasks on the same achievement.
 *
 * This migration changes the unique constraint to ['achievement_id', 'user_id', 'task'].
 */

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('achievement_authors', function (Blueprint $table) {
            $table->dropForeign(['achievement_id']);
            $table->dropUnique(['achievement_id', 'user_id']);

            $table->unique(['achievement_id', 'user_id', 'task']);
            $table->foreign('achievement_id')->references('ID')->on('Achievements')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('achievement_authors', function (Blueprint $table) {
            $table->dropForeign(['achievement_id']);
            $table->dropUnique(['achievement_id', 'user_id', 'task']);

            $table->unique(['achievement_id', 'user_id']);
            $table->foreign('achievement_id')->references('ID')->on('Achievements')->onDelete('cascade');
        });
    }
};
