<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('forum_topic_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('sent_by_id')->nullable()->after('author_id');
            $table->unsignedBigInteger('edited_by_id')->nullable()->after('sent_by_id');

            $table->foreign('sent_by_id')->references('ID')->on('UserAccounts')->onDelete('set null');
            $table->foreign('edited_by_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('forum_topic_comments', function (Blueprint $table) {
            $table->dropForeign(['sent_by_id']);
            $table->dropColumn('sent_by_id');

            $table->dropForeign(['edited_by_id']);
            $table->dropColumn('edited_by_id');
        });
    }
};
