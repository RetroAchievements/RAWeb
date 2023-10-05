<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('LeaderboardEntry', function (Blueprint $table) {
            // TODO clean up failing relations
            $table->foreign('LeaderboardID', 'leaderboard_entries_leaderboard_id_foreign')->references('ID')->on('LeaderboardDef')->onDelete('cascade');
        });

        Schema::table('CodeNotes', function (Blueprint $table) {
            // TODO clean up failing relations
            $table->foreign('AuthorID', 'memory_notes_user_id_foreign')->references('ID')->on('UserAccounts')->onDelete('set null');
        });

        Schema::table('Comment', function (Blueprint $table) {
            // TODO clean up failing relations
            $table->foreign('UserID', 'comments_user_id_foreign')->references('ID')->on('UserAccounts')->onDelete('set null');
        });

        Schema::table('ForumTopic', function (Blueprint $table) {
            // TODO clean up failing relations
            $table->foreign('AuthorID', 'forum_topics_author_id_foreign')->references('ID')->on('UserAccounts')->onDelete('set null');
            $table->foreign('ForumID', 'forum_topics_forum_id_foreign')->references('ID')->on('Forum')->onDelete('cascade');
        });

        Schema::table('ForumTopicComment', function (Blueprint $table) {
            // TODO clean up failing relations
            $table->foreign('AuthorID', 'forum_topic_comment_author_id_foreign')->references('ID')->on('UserAccounts')->onDelete('set null');
            $table->foreign('ForumTopicID', 'forum_topic_comment_forum_topic_id_foreign')->references('ID')->on('ForumTopic')->onDelete('cascade');
        });

        Schema::table('Ticket', function (Blueprint $table) {
            // TODO clean up failing relations
            $table->foreign('ReportedByUserID', 'tickets_reporter_id_foreign')->references('ID')->on('UserAccounts')->onDelete('set null');
            $table->foreign('ResolvedByUserID', 'tickets_resolver_id_foreign')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('LeaderboardEntry', function (Blueprint $table) {
            $table->dropForeign('leaderboard_entries_leaderboard_id_foreign');
        });

        Schema::table('CodeNotes', function (Blueprint $table) {
            $table->dropForeign('memory_notes_user_id_foreign');
        });

        Schema::table('Comment', function (Blueprint $table) {
            $table->dropForeign('comments_user_id_foreign');
        });

        Schema::table('ForumTopic', function (Blueprint $table) {
            $table->dropForeign('forum_topics_author_id_foreign');
            $table->dropForeign('forum_topics_forum_id_foreign');
        });

        Schema::table('ForumTopicComment', function (Blueprint $table) {
            $table->dropForeign('forum_topic_comment_author_id_foreign');
            $table->dropForeign('forum_topic_comment_forum_topic_id_foreign');
        });

        Schema::table('Ticket', function (Blueprint $table) {
            $table->dropForeign('tickets_reporter_id_foreign');
            $table->dropForeign('tickets_resolver_id_foreign');
        });
    }
};
