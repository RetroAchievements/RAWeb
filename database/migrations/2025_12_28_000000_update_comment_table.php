<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Delete orphaned comments with unmapped ArticleType values.
        // (News=4, Forum=8, invalid=0, or any other unexpected values)
        DB::statement("DELETE FROM Comment WHERE ArticleType NOT IN (1, 2, 3, 5, 6, 7, 9, 10, 11, 12)");

        // Populate commentable_type from ArticleType.
        DB::statement("UPDATE Comment SET commentable_type = CASE ArticleType
            WHEN 1 THEN 'game.comment'
            WHEN 2 THEN 'achievement.comment'
            WHEN 3 THEN 'user.comment'
            WHEN 5 THEN 'user-activity.comment'
            WHEN 6 THEN 'leaderboard.comment'
            WHEN 7 THEN 'trigger.ticket.comment'
            WHEN 9 THEN 'user-moderation.comment'
            WHEN 10 THEN 'game-hash.comment'
            WHEN 11 THEN 'achievement-set-claim.comment'
            WHEN 12 THEN 'game-modification.comment'
        END WHERE commentable_type IS NULL");

        // Drop the index first, then the column (required for SQLite compatibility).
        Schema::table('Comment', function (Blueprint $table) {
            $table->dropIndex('comments_commentable_index');
        });
        Schema::table('Comment', function (Blueprint $table) {
            $table->dropColumn('commentable_id');
        });

        // Drop indexes before renaming columns, otherwise the migration may fail.
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }
        Schema::table('Comment', function (Blueprint $table) {
            $table->dropIndex('comment_articleid_index');
            $table->dropIndex('comment_user_id_submitted_index');
        });
        if ($driver !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        Schema::table('Comment', function (Blueprint $table) {
            $table->renameColumn('ArticleID', 'commentable_id');
            $table->string('commentable_type')->nullable(false)->change();
            $table->dropColumn('ArticleType');

            $table->renameColumn('ID', 'id');
            $table->renameColumn('Payload', 'body');
            $table->renameColumn('Submitted', 'created_at');
            $table->renameColumn('Edited', 'updated_at');
        });

        // Recreate indexes with new column names. Tests fail if it's just a rename.
        if ($driver === 'sqlite') {
            DB::statement("DROP INDEX IF EXISTS `comments_commentable_id_index`");
            DB::statement("DROP INDEX IF EXISTS `comments_user_id_created_at_index`");
        } else {
            // Temporarily disable FK checks - the migration fails unless I do this.
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::statement("DROP INDEX IF EXISTS `comments_commentable_id_index` ON `Comment`");
            DB::statement("DROP INDEX IF EXISTS `comments_user_id_created_at_index` ON `Comment`");
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
        Schema::table('Comment', function (Blueprint $table) {
            $table->index('commentable_id', 'comments_commentable_id_index');
            $table->index(['user_id', 'created_at'], 'comments_user_id_created_at_index');
        });

        Schema::rename('Comment', 'comments');
    }

    public function down(): void
    {
        Schema::rename('comments', 'Comment');

        // Disable FK checks - index may be used by FK constraint.
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        Schema::table('Comment', function (Blueprint $table) {
            $table->dropIndex('comments_commentable_id_index');
            $table->dropIndex('comments_user_id_created_at_index');
        });

        if ($driver !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        Schema::table('Comment', function (Blueprint $table) {
            $table->renameColumn('id', 'ID');
            $table->renameColumn('body', 'Payload');
            $table->renameColumn('created_at', 'Submitted');
            $table->renameColumn('updated_at', 'Edited');

            $table->unsignedTinyInteger('ArticleType')->after('ID');
        });

        DB::statement("UPDATE Comment SET ArticleType = CASE commentable_type
            WHEN 'game.comment' THEN 1
            WHEN 'achievement.comment' THEN 2
            WHEN 'user.comment' THEN 3
            WHEN 'user-activity.comment' THEN 5
            WHEN 'leaderboard.comment' THEN 6
            WHEN 'trigger.ticket.comment' THEN 7
            WHEN 'user-moderation.comment' THEN 9
            WHEN 'game-hash.comment' THEN 10
            WHEN 'achievement-set-claim.comment' THEN 11
            WHEN 'game-modification.comment' THEN 12
            ELSE 0
        END");

        Schema::table('Comment', function (Blueprint $table) {
            $table->renameColumn('commentable_id', 'ArticleID');
        });

        Schema::table('Comment', function (Blueprint $table) {
            $table->index('ArticleID', 'comment_articleid_index');
            $table->index(['user_id', 'Submitted'], 'comment_user_id_submitted_index');
        });

        Schema::table('Comment', function (Blueprint $table) {
            $table->unsignedBigInteger('commentable_id')->nullable()->after('user_id');
        });

        Schema::table('Comment', function (Blueprint $table) {
            $table->string('commentable_type')->nullable()->change();
        });

        Schema::table('Comment', function (Blueprint $table) {
            $table->index(['commentable_type', 'commentable_id'], 'comments_commentable_index');
        });
    }
};
