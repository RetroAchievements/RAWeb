<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        // "News" -> "news_temp" -> "news".
        // We have to do this because table names are case-insensitive in SQLite.
        // If we don't use a temp transition table, all of our tests that hit the DB fail.
        Schema::rename('News', 'news_temp');
        Schema::rename('news_temp', 'news');

        Schema::table('news', function (Blueprint $table) {
            $table->renameColumn('ID', 'id');
            $table->renameColumn('Timestamp', 'created_at');
            $table->renameColumn('Updated', 'updated_at');
            $table->renameColumn('Title', 'title');
            $table->renameColumn('Payload', 'body');
            $table->renameColumn('Link', 'link');
            $table->renameColumn('Image', 'image_asset_path');

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->timestamp('pinned_at')->nullable()->after('unpublish_at');
        });

        Schema::table('news', function (Blueprint $table) {
            // SQLite does not support dropping the real FK by name.
            if (DB::getDriverName() === 'sqlite') {
                $table->dropForeign(['user_id']);
            } else {
                $table->dropForeign('news_user_id_foreign');
            }
        });
        Schema::table('news', function (Blueprint $table) {
            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id', 'news_user_id_foreign')->references('ID')->on('UserAccounts')->onDelete('set null');

            $table->renameColumn('id', 'ID');
            $table->renameColumn('created_at', 'Timestamp');
            $table->renameColumn('updated_at', 'Updated');
            $table->renameColumn('title', 'Title');
            $table->renameColumn('body', 'Payload');
            $table->renameColumn('link', 'Link');
            $table->renameColumn('image_asset_path', 'Image');

            $table->dropColumn('pinned_at');
        });

        Schema::rename('news', 'News');
    }
};
