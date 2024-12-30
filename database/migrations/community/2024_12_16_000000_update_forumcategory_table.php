<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::rename('ForumCategory', 'forum_categories');

        Schema::table('forum_categories', function (Blueprint $table) {
            $table->renameColumn('ID', 'id');
            $table->renameColumn('Name', 'title');
            $table->renameColumn('Description', 'description');
            $table->renameColumn('DisplayOrder', 'order_column');
            $table->renameColumn('Created', 'created_at');
            $table->renameColumn('Updated', 'updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('forum_categories', function (Blueprint $table) {
            $table->renameColumn('id', 'ID');
            $table->renameColumn('title', 'Name');
            $table->renameColumn('description', 'Description');
            $table->renameColumn('order_column', 'DisplayOrder');
            $table->renameColumn('created_at', 'Created');
            $table->renameColumn('updated_at', 'Updated');
        });

        Schema::rename('forum_categories', 'ForumCategory');
    }
};
