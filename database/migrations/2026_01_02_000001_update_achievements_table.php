<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->unsignedInteger('author_yield_unlocks')->default(0)->after('unlock_hardcore_percentage');
        });

        // SELECT SUM(...) FROM achievements WHERE user_id = ? AND is_promoted = 1
        Schema::table('achievements', function (Blueprint $table) {
            $table->index(['user_id', 'is_promoted'], 'achievements_user_id_is_promoted_index');
        });
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropIndex('achievements_user_id_is_promoted_index');
        });

        Schema::table('achievements', function (Blueprint $table) {
            $table->dropColumn('author_yield_unlocks');
        });
    }
};
