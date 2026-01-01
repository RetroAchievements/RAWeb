<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Add is_promoted column as nullable.
        Schema::table('Achievements', function (Blueprint $table) {
            $table->boolean('is_promoted')->nullable()->after('Flags');
        });

        // 2. Migrate Flags data to is_promoted (3 = OfficialCore = true, 5 = Unofficial = false).
        DB::statement("UPDATE Achievements SET is_promoted = CASE
            WHEN Flags = 3 THEN TRUE
            WHEN Flags = 5 THEN FALSE
            ELSE FALSE
        END");

        // 3. Make is_promoted non-nullable with a default of false.
        Schema::table('Achievements', function (Blueprint $table) {
            $table->boolean('is_promoted')->default(false)->nullable(false)->change();
        });

        // 4. Drop index that includes Flags before dropping the column.
        Schema::table('Achievements', function (Blueprint $table) {
            $table->dropIndex('achievements_game_id_published_index');
        });

        // 5. Drop unused columns.
        Schema::table('Achievements', function (Blueprint $table) {
            $table->dropColumn([
                'Flags',
                'Progress',
                'ProgressMax',
                'ProgressFormat',
                'VotesPos',
                'VotesNeg',
            ]);
        });

        // 6. Recreate index with new column name.
        Schema::table('Achievements', function (Blueprint $table) {
            $table->index(['GameID', 'is_promoted'], 'achievements_game_id_is_promoted_index');
        });

        // 7. Rename columns.
        Schema::table('Achievements', function (Blueprint $table) {
            $table->renameColumn('ID', 'id');
            $table->renameColumn('GameID', 'game_id');
            $table->renameColumn('Title', 'title');
            $table->renameColumn('Description', 'description');
            $table->renameColumn('Points', 'points');
            $table->renameColumn('TrueRatio', 'points_weighted');
            $table->renameColumn('Updated', 'updated_at');
            $table->renameColumn('BadgeName', 'image_name');
            $table->renameColumn('DisplayOrder', 'order_column');
            $table->renameColumn('AssocVideo', 'embed_url');
            $table->renameColumn('MemAddr', 'trigger_definition');
            $table->renameColumn('unlocks_hardcore_total', 'unlocks_hardcore');
            $table->renameColumn('DateModified', 'modified_at');
        });

        // 8. Reposition points_weighted to be right after points.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('Achievements', function (Blueprint $table) {
                $table->unsignedInteger('points_weighted')->default(0)->after('points')->change();
            });
        }

        // 9. Reposition modified_at to be after embed_url.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('Achievements', function (Blueprint $table) {
                $table->timestamp('modified_at')->nullable()->after('embed_url')->change();
            });
        }

        // 10. Rename DateCreated to created_at.
        Schema::table('Achievements', function (Blueprint $table) {
            $table->renameColumn('DateCreated', 'created_at');
        });

        // 11. Reposition created_at to be after modified_at.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('Achievements', function (Blueprint $table) {
                $table->timestamp('created_at')->nullable()->after('modified_at')->change();
            });
        }

        // 12. Rename table (skip for SQLite as it's case-insensitive).
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::rename('Achievements', 'achievements');
        }

        // 13. Rename indexes.
        Schema::table('achievements', function (Blueprint $table) {
            $table->renameIndex('achievements_gameid_index', 'achievements_game_id_index');
            $table->renameIndex('achievements_trueratio_index', 'achievements_points_weighted_index');
            $table->renameIndex('achievements_gameid_datemodified_deleted_at_index', 'achievements_game_id_modified_at_deleted_at_index');
        });
    }

    public function down(): void
    {
        // 1. Rename indexes back.
        Schema::table('achievements', function (Blueprint $table) {
            $table->renameIndex('achievements_game_id_index', 'achievements_gameid_index');
            $table->renameIndex('achievements_points_weighted_index', 'achievements_trueratio_index');
            $table->renameIndex('achievements_game_id_modified_at_deleted_at_index', 'achievements_gameid_datemodified_deleted_at_index');
        });

        // 2. Rename table back.
        Schema::rename('achievements', 'Achievements');

        // 3. Reposition created_at back to its original position.
        Schema::table('Achievements', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->after('unlock_hardcore_percentage')->change();
        });

        // 4. Reposition modified_at back to its original position.
        Schema::table('Achievements', function (Blueprint $table) {
            $table->timestamp('modified_at')->nullable()->after('trigger_definition')->change();
        });

        // 5. Add back VotesPos and VotesNeg.
        Schema::table('Achievements', function (Blueprint $table) {
            $table->smallInteger('VotesPos')->unsigned()->default(0)->after('modified_at');
            $table->smallInteger('VotesNeg')->unsigned()->default(0)->after('VotesPos');
        });

        // 6. Rename created_at back to DateCreated.
        Schema::table('Achievements', function (Blueprint $table) {
            $table->renameColumn('created_at', 'DateCreated');
        });

        // 7. Reposition points_weighted back to its original position.
        Schema::table('Achievements', function (Blueprint $table) {
            $table->unsignedInteger('points_weighted')->default(0)->after('embed_url')->change();
        });

        // 8. Rename columns back.
        Schema::table('Achievements', function (Blueprint $table) {
            $table->renameColumn('id', 'ID');
            $table->renameColumn('game_id', 'GameID');
            $table->renameColumn('title', 'Title');
            $table->renameColumn('description', 'Description');
            $table->renameColumn('points', 'Points');
            $table->renameColumn('points_weighted', 'TrueRatio');
            $table->renameColumn('updated_at', 'Updated');
            $table->renameColumn('image_name', 'BadgeName');
            $table->renameColumn('order_column', 'DisplayOrder');
            $table->renameColumn('embed_url', 'AssocVideo');
            $table->renameColumn('trigger_definition', 'MemAddr');
            $table->renameColumn('unlocks_hardcore', 'unlocks_hardcore_total');
            $table->renameColumn('modified_at', 'DateModified');
        });

        // 9. Add back Flags column.
        Schema::table('Achievements', function (Blueprint $table) {
            $table->tinyInteger('Flags')->unsigned()->default(5)->after('type');
        });

        // 10. Convert is_promoted back to Flags.
        DB::statement("UPDATE Achievements SET Flags = CASE
            WHEN is_promoted = TRUE THEN 3
            WHEN is_promoted = FALSE THEN 5
            ELSE 5
        END");

        // 11. Drop the is_promoted index.
        Schema::table('Achievements', function (Blueprint $table) {
            $table->dropIndex('achievements_game_id_is_promoted_index');
        });

        // 12. Add back remaining dropped columns and remove is_promoted.
        Schema::table('Achievements', function (Blueprint $table) {
            $table->dropColumn('is_promoted');

            $table->string('Progress', 255)->nullable()->after('MemAddr');
            $table->string('ProgressMax', 255)->nullable()->after('Progress');
            $table->string('ProgressFormat', 50)->nullable()->after('ProgressMax');
        });

        // 13. Recreate the original index with Flags.
        Schema::table('Achievements', function (Blueprint $table) {
            $table->index(['GameID', 'Flags'], 'achievements_game_id_published_index');
        });
    }
};
