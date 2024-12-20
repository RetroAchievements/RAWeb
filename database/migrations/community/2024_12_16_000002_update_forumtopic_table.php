<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        if (DB::connection() instanceof SQLiteConnection) {
            // do nothing
        } else {
            Schema::table('ForumTopic', function (Blueprint $table) {
                // Check for both possible foreign key names since they could differ
                // between production and local environments after migrations/rollbacks.
                $foreignKeys = DB::select(<<<SQL
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.TABLE_CONSTRAINTS
                    WHERE TABLE_NAME = "ForumTopic"
                    AND CONSTRAINT_TYPE = "FOREIGN KEY"
                    AND CONSTRAINT_NAME IN ('forum_topics_forumid_foreign', 'forums_topics_forum_id_foreign')
                SQL);

                foreach ($foreignKeys as $fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                }
            });

            Schema::table('ForumTopic', function (Blueprint $table) {
                $table->dropIndex('forum_topics_forum_id_index');
            });
        }

        Schema::table('ForumTopic', function (Blueprint $table) {
            if (DB::connection() instanceof SQLiteConnection) {
                $table->dropForeign(['author_id']);
            } else {
                $table->dropForeign('forumtopic_author_id_foreign');
            }
        });

        Schema::rename('ForumTopic', 'forum_topics');

        Schema::table('forum_topics', function (Blueprint $table) {
            $table->renameColumn('ID', 'id');
            $table->renameColumn('ForumID', 'forum_id');
            $table->renameColumn('Title', 'title');
            $table->renameColumn('DateCreated', 'created_at');
            $table->renameColumn('Updated', 'updated_at');
            $table->renameColumn('LatestCommentID', 'latest_comment_id');
            $table->renameColumn('RequiredPermissions', 'required_permissions');
        });

        Schema::table('forum_topics', function (Blueprint $table) {
            $table->text('body')->after('title')->nullable();
        });

        Schema::table('forum_topics', function (Blueprint $table) {
            $table->index('forum_id', 'forum_topics_forum_id_index');
            $table->foreign('forum_id')->references('id')->on('forums')->onDelete('set null');
        });
        Schema::table('forum_topics', function (Blueprint $table) {
            $table->foreign('author_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('forum_topics', function (Blueprint $table) {
            $table->dropForeign(['forum_id']);
            $table->dropForeign(['author_id']);
        });

        Schema::table('forum_topics', function (Blueprint $table) {
            $table->renameColumn('id', 'ID');
            $table->renameColumn('forum_id', 'ForumID');
            $table->renameColumn('title', 'Title');
            $table->renameColumn('created_at', 'DateCreated');
            $table->renameColumn('updated_at', 'Updated');
            $table->renameColumn('latest_comment_id', 'LatestCommentID');
            $table->renameColumn('required_permissions', 'RequiredPermissions');

            $table->dropColumn('body');
        });

        Schema::table('forum_topics', function (Blueprint $table) {
            $table->foreign('ForumID')->references('id')->on('forums')->onDelete('set null');
            $table->foreign('author_id', 'forumtopic_author_id_foreign')->references('ID')->on('UserAccounts')->onDelete('set null');
        });

        Schema::rename('forum_topics', 'ForumTopic');
    }
};
