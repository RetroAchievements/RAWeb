<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('Forum', function (Blueprint $table) {
            if (DB::connection() instanceof SQLiteConnection) {
                $table->dropForeign(['CategoryID']);
            } else {
                $table->dropForeign('forums_forum_category_id_foreign');
            }
        });

        Schema::rename('Forum', 'forums');

        Schema::table('forums', function (Blueprint $table) {
            $table->renameColumn('ID', 'id');
            $table->renameColumn('CategoryID', 'forum_category_id');
            $table->renameColumn('Title', 'title');
            $table->renameColumn('Description', 'description');
            $table->renameColumn('LatestCommentID', 'latest_comment_id');
            $table->renameColumn('DisplayOrder', 'order_column');
            $table->renameColumn('Created', 'created_at');
            $table->renameColumn('Updated', 'updated_at');
        });

        Schema::table('forums', function (Blueprint $table) {
            $table->foreign('forum_category_id')->references('id')->on('forum_categories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('forums', function (Blueprint $table) {
            $table->dropForeign(['forum_category_id']);
        });

        Schema::table('forums', function (Blueprint $table) {
            $table->renameColumn('id', 'ID');
            $table->renameColumn('forum_category_id', 'CategoryID');
            $table->renameColumn('title', 'Title');
            $table->renameColumn('description', 'Description');
            $table->renameColumn('latest_comment_id', 'LatestCommentID');
            $table->renameColumn('order_column', 'DisplayOrder');
            $table->renameColumn('created_at', 'Created');
            $table->renameColumn('updated_at', 'Updated');

            $table->foreign('CategoryID', 'forums_forum_category_id_foreign')->references('id')->on('forum_categories')->onDelete('set null');
        });

        Schema::rename('forums', 'Forum');
    }
};
