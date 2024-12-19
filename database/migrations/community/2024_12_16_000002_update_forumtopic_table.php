<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('ForumTopic', function (Blueprint $table) {
            if (DB::connection() instanceof SQLiteConnection) {
                $table->dropForeign(['ForumID']);
            } else {
                $table->dropForeign('forum_topics_forumid_foreign');
            }
        });
        Schema::table('ForumTopic', function (Blueprint $table) {
            if (DB::connection() instanceof SQLiteConnection) {
                // do nothing
            } else {
                $table->dropIndex('forum_topics_forum_id_index');
            }
        });
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
            $table->index('forum_id', 'idx_forum_topics_forum_id'); // use a custom name to make SQLite happy
        });
        Schema::table('forum_topics', function (Blueprint $table) {
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
            $table->dropIndex(['forum_id']);
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

            $table->index('ForumID', 'forum_topics_forum_id_index');
            $table->foreign('ForumID')->references('id')->on('forums')->onDelete('set null');
            $table->foreign('author_id', 'forumtopic_author_id_foreign')->references('ID')->on('UserAccounts')->onDelete('set null');
        });

        Schema::rename('forum_topics', 'ForumTopic');
    }
};
