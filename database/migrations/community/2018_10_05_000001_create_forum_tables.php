<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('ForumCategory', function (Blueprint $table) {
            $table->bigIncrements('ID')->change();
            $table->softDeletesTz();
        });

        Schema::table('Forum', function (Blueprint $table) {
            $table->bigIncrements('ID')->change();

            // nullable morphs
            // games and systems have their own forums
            $table->string('forumable_model')->nullable()->after('ID');
            $table->unsignedBigInteger('forumable_id')->nullable()->after('forumable_model');

            $table->unsignedBigInteger('CategoryID')->nullable()->change();
            $table->unsignedBigInteger('LatestCommentID')->nullable()->change();

            $table->softDeletesTz();

            $table->unique(['forumable_model', 'forumable_id'], 'forums_forumable_unique');

            $table->foreign('CategoryID', 'forums_forum_category_id_foreign')->references('ID')->on('ForumCategory')->onDelete('set null');
        });

        Schema::table('ForumTopic', function (Blueprint $table) {
            $table->bigIncrements('ID')->change();

            $table->unsignedBigInteger('ForumID')->nullable()->change();
            $table->unsignedBigInteger('AuthorID')->nullable()->change();

            $table->timestampTz('pinned_at')->nullable()->after('AuthorID');
            $table->timestampTz('locked_at')->nullable()->after('pinned_at');

            $table->unsignedBigInteger('LatestCommentID')->nullable()->change();

            $table->softDeletesTz();

            $table->index('DateCreated', 'forum_topics_created_at_index');
        });

        Schema::table('ForumTopicComment', function (Blueprint $table) {
            $table->bigIncrements('ID')->change();

            $table->unsignedBigInteger('ForumTopicID')->nullable()->change();
            $table->unsignedBigInteger('AuthorID')->nullable()->change();

            $table->timestampTz('authorized_at')->nullable()->after('AuthorID');
            $table->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::table('ForumCategory', function (Blueprint $table) {
            $table->dropSoftDeletesTz();
        });

        Schema::table('Forum', function (Blueprint $table) {
            $table->dropForeign('forums_forum_category_id_foreign');
            $table->dropUnique('forums_forumable_unique');
            $table->dropSoftDeletesTz();
            $table->dropColumn('forumable_model');
            $table->dropColumn('forumable_id');
        });

        Schema::table('ForumTopic', function (Blueprint $table) {
            $table->dropIndex('forum_topics_created_at_index');
            $table->dropSoftDeletesTz();
            $table->dropColumn('pinned_at');
            $table->dropColumn('locked_at');
        });

        Schema::table('ForumTopicComment', function (Blueprint $table) {
            $table->dropSoftDeletesTz();
            $table->dropColumn('authorized_at');
        });
    }
};
