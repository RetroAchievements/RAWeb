<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('game_sets', function (Blueprint $table) {
            $table->unsignedBigInteger('forum_topic_id')->nullable()->after('game_id');
        });

        Schema::table('game_sets', function (Blueprint $table) {
            $table->foreign('forum_topic_id')->references('ID')->on('ForumTopic')->onDelete('set null');
        });
        Schema::table('game_sets', function (Blueprint $table) {
            $table->index('forum_topic_id');
        });
    }

    public function down(): void
    {
        Schema::table('game_sets', function (Blueprint $table) {
            $table->dropForeign(['forum_topic_id']);
            $table->dropIndex(['forum_topic_id']);
            $table->dropColumn('forum_topic_id');
        });
    }
};
