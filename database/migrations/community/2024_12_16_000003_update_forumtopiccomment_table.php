<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        if (DB::connection() instanceof SQLiteConnection) {
            // do nothing
        } else {
            Schema::table('ForumTopicComment', function (Blueprint $table) {
                // Check for both possible foreign key names since they could differ
                // between production and local environments after migrations/rollbacks.
                $foreignKeys = DB::select(<<<SQL
                    SELECT DISTINCT CONSTRAINT_NAME
                    FROM information_schema.TABLE_CONSTRAINTS
                    WHERE TABLE_NAME = "ForumTopicComment"
                    AND CONSTRAINT_TYPE = "FOREIGN KEY"
                SQL);

                foreach ($foreignKeys as $fk) {
                    try {
                        $table->dropForeign($fk->CONSTRAINT_NAME);
                    } catch (Exception $e) {
                        // If dropping the constraint fails, just continue to the next one.
                        continue;
                    }
                }
            });
        }

        Schema::table('ForumTopicComment', function (Blueprint $table) {
            $table->dropIndex('forum_topic_comments_forum_topic_id_index');
        });
        Schema::table('ForumTopicComment', function (Blueprint $table) {
            $table->dropIndex('forum_topic_comments_created_at_index');
        });
        Schema::table('ForumTopicComment', function (Blueprint $table) {
            $table->dropIndex('forum_topic_comments_author_id_created_at_index');
        });

        Schema::rename('ForumTopicComment', 'forum_topic_comments');

        Schema::table('forum_topic_comments', function (Blueprint $table) {
            $table->renameColumn('ID', 'id');
            $table->renameColumn('ForumTopicID', 'forum_topic_id');
            $table->renameColumn('Payload', 'body');
            $table->renameColumn('DateCreated', 'created_at');
            $table->renameColumn('DateModified', 'updated_at');
            $table->renameColumn('Authorised', 'is_authorized');
        });

        Schema::table('forum_topic_comments', function (Blueprint $table) {
            $table->index('forum_topic_id', 'forum_topic_comments_forum_topic_id_index');
        });
        Schema::table('forum_topic_comments', function (Blueprint $table) {
            $table->index('created_at', 'forum_topic_comments_created_at_index');
        });
        Schema::table('forum_topic_comments', function (Blueprint $table) {
            $table->index(['author_id', 'created_at'], 'forum_topic_comments_author_id_created_at_index');
        });

        Schema::table('forum_topic_comments', function (Blueprint $table) {
            $table->foreign('forum_topic_id')->references('id')->on('forum_topics')->onDelete('set null');
        });

        Schema::table('forum_topic_comments', function (Blueprint $table) {
            $table->foreign('author_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('forum_topic_comments', function (Blueprint $table) {
            $table->dropForeign(['forum_topic_id']);
            $table->dropForeign(['author_id']);
            $table->dropIndex('forum_topic_comments_forum_topic_id_index');
            $table->dropIndex('forum_topic_comments_created_at_index');
            $table->dropIndex('forum_topic_comments_author_id_created_at_index');
        });

        Schema::table('forum_topic_comments', function (Blueprint $table) {
            $table->renameColumn('id', 'ID');
            $table->renameColumn('forum_topic_id', 'ForumTopicID');
            $table->renameColumn('body', 'Payload');
            $table->renameColumn('created_at', 'DateCreated');
            $table->renameColumn('updated_at', 'DateModified');
            $table->renameColumn('is_authorized', 'Authorised');

            $table->foreign('author_id', 'forumtopiccomment_author_id_foreign')->references('ID')->on('UserAccounts')->onDelete('set null');
        });

        Schema::rename('forum_topic_comments', 'ForumTopicComment');

        Schema::table('ForumTopicComment', function (Blueprint $table) {
            $table->index('ForumTopicID', 'forum_topic_comments_forum_topic_id_index');
            $table->index('DateCreated', 'forum_topic_comments_created_at_index');
            $table->index(['author_id', 'DateCreated'], 'forum_topic_comments_author_id_created_at_index');
        });
    }
};
