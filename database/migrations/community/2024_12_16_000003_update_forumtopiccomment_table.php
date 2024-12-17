<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('ForumTopicComment', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
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
        });

        Schema::table('forum_topic_comments', function (Blueprint $table) {
            $table->renameColumn('id', 'ID');
            $table->renameColumn('forum_topic_id', 'ForumTopicID');
            $table->renameColumn('body', 'Payload');
            $table->renameColumn('created_at', 'DateCreated');
            $table->renameColumn('updated_at', 'DateModified');
            $table->renameColumn('is_authorized', 'Authorised');

            $table->foreign('ForumTopicID')->references('id')->on('forum_topics')->onDelete('set null');
            $table->foreign('author_id', 'forumtopiccomment_author_id_foreign')->references('ID')->on('UserAccounts')->onDelete('set null');
        });

        Schema::rename('forum_topic_comments', 'ForumTopicComment');
    }
};
