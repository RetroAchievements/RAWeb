<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop foreign key constraints that reference columns we're renaming.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('GameData', function (Blueprint $table) {
                $table->dropForeign('gamedata_forumtopicid_foreign');
                $table->dropForeign('games_systems_id_foreign');
            });
        }

        // Rename columns.
        Schema::table('GameData', function (Blueprint $table) {
            $table->renameColumn('ID', 'id');
            $table->renameColumn('Title', 'title');
            $table->renameColumn('ConsoleID', 'system_id');
            $table->renameColumn('ForumTopicID', 'forum_topic_id');
            $table->renameColumn('ImageIcon', 'image_icon_asset_path');
            $table->renameColumn('ImageTitle', 'image_title_asset_path');
            $table->renameColumn('ImageIngame', 'image_ingame_asset_path');
            $table->renameColumn('ImageBoxArt', 'image_box_art_asset_path');
            $table->renameColumn('Publisher', 'publisher');
            $table->renameColumn('Developer', 'developer');
            $table->renameColumn('Genre', 'genre');
            $table->renameColumn('TotalTruePoints', 'points_weighted');
            $table->renameColumn('RichPresencePatch', 'trigger_definition');
            $table->renameColumn('GuideURL', 'legacy_guide_url');
            $table->renameColumn('Created', 'created_at');
            $table->renameColumn('Updated', 'updated_at');
        });

        // Drop the Flags column.
        Schema::table('GameData', function (Blueprint $table) {
            $table->dropColumn('Flags');
        });

        // Rename the table.
        Schema::rename('GameData', 'games');

        // Recreate foreign key constraints with new column names.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('games', function (Blueprint $table) {
                $table->foreign('forum_topic_id', 'games_forum_topic_id_foreign')
                    ->references('id')
                    ->on('forum_topics')
                    ->onDelete('set null');

                $table->foreign('system_id', 'games_system_id_foreign')
                    ->references('id')
                    ->on('systems')
                    ->onDelete('set null');
            });
        }

        // Rename indexes to match the new table name.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('games', function (Blueprint $table) {
                $table->renameIndex('gamedata_sort_title_index', 'games_sort_title_index');
                $table->renameIndex('gamedata_trigger_id_index', 'games_trigger_id_index');
            });
        }
    }

    public function down(): void
    {
        // Drop new foreign key constraints.
        Schema::table('games', function (Blueprint $table) {
            $table->dropForeign('games_forum_topic_id_foreign');
            $table->dropForeign('games_system_id_foreign');
        });

        // Rename indexes back.
        Schema::table('games', function (Blueprint $table) {
            $table->renameIndex('games_sort_title_index', 'gamedata_sort_title_index');
            $table->renameIndex('games_trigger_id_index', 'gamedata_trigger_id_index');
        });

        // Rename table back.
        Schema::rename('games', 'GameData');

        // Add back the Flags column.
        Schema::table('GameData', function (Blueprint $table) {
            $table->integer('Flags')->nullable()->after('forum_topic_id');
        });

        // Rename columns back.
        Schema::table('GameData', function (Blueprint $table) {
            $table->renameColumn('id', 'ID');
            $table->renameColumn('title', 'Title');
            $table->renameColumn('system_id', 'ConsoleID');
            $table->renameColumn('forum_topic_id', 'ForumTopicID');
            $table->renameColumn('image_icon_asset_path', 'ImageIcon');
            $table->renameColumn('image_title_asset_path', 'ImageTitle');
            $table->renameColumn('image_ingame_asset_path', 'ImageIngame');
            $table->renameColumn('image_box_art_asset_path', 'ImageBoxArt');
            $table->renameColumn('publisher', 'Publisher');
            $table->renameColumn('developer', 'Developer');
            $table->renameColumn('genre', 'Genre');
            $table->renameColumn('points_weighted', 'TotalTruePoints');
            $table->renameColumn('trigger_definition', 'RichPresencePatch');
            $table->renameColumn('legacy_guide_url', 'GuideURL');
            $table->renameColumn('created_at', 'Created');
            $table->renameColumn('updated_at', 'Updated');
        });

        // Recreate original foreign key constraints.
        Schema::table('GameData', function (Blueprint $table) {
            $table->foreign('ForumTopicID', 'gamedata_forumtopicid_foreign')
                ->references('id')
                ->on('forum_topics')
                ->onDelete('set null');

            $table->foreign('ConsoleID', 'games_systems_id_foreign')
                ->references('id')
                ->on('systems')
                ->onDelete('set null');
        });
    }
};
