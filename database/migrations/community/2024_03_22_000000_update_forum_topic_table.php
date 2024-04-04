<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('ForumTopic', function (Blueprint $table) {
            $table->renameColumn('AuthorID', 'author_id');
        });

        Schema::table('ForumTopic', function (Blueprint $table) {
            $table->unsignedBigInteger('author_id')->nullable()->change();
        });

        Schema::table('ForumTopic', function (Blueprint $table) {
            $table->foreign('author_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('ForumTopic', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
        });

        Schema::table('ForumTopic', function (Blueprint $table) {
            $table->renameColumn('author_id', 'AuthorID');
        });
    }
};
