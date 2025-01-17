<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('Achievements', function (Blueprint $table) {
            $table->foreignId('trigger_id')
                ->nullable()
                ->after('user_id')
                ->constrained('triggers')
                ->nullOnDelete();

            $table->index('trigger_id');
        });

        Schema::table('LeaderboardDef', function (Blueprint $table) {
            $table->foreignId('trigger_id')
                ->nullable()
                ->after('author_id')
                ->constrained('triggers')
                ->nullOnDelete();

            $table->index('trigger_id');
        });

        Schema::table('GameData', function (Blueprint $table) {
            $table->foreignId('trigger_id')
                ->nullable()
                ->after('releases')
                ->constrained('triggers')
                ->nullOnDelete();

            $table->index('trigger_id');
        });
    }

    public function down(): void
    {
        Schema::table('GameData', function (Blueprint $table) {
            $table->dropForeign(['trigger_id']);
            $table->dropIndex(['trigger_id']);
            $table->dropColumn('trigger_id');
        });

        Schema::table('LeaderboardDef', function (Blueprint $table) {
            $table->dropForeign(['trigger_id']);
            $table->dropIndex(['trigger_id']);
            $table->dropColumn('trigger_id');
        });

        Schema::table('Achievements', function (Blueprint $table) {
            $table->dropForeign(['trigger_id']);
            $table->dropIndex(['trigger_id']);
            $table->dropColumn('trigger_id');
        });
    }
};
