<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('GameData', function (Blueprint $table) {
            $table->unsignedBigInteger('ForumTopicID')->change();
        });

        Schema::table('GameData', function (Blueprint $table) {
            $table->foreign('ForumTopicID')->references('ID')->on('ForumTopic')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('GameData', function (Blueprint $table) {
            $table->dropForeign(['ForumTopicID']);
        });

        Schema::table('GameData', function (Blueprint $table) {
            $table->integer('ForumTopicID')->change();
        });
    }
};
