<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        Schema::create('forum_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->unsignedInteger('order_column')->nullable();

            /*
             * earlier creation dates are not known
             */
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('forums', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('forum_category_id')->nullable();

            /*
             * games and systems have their own forums
             */
            $table->string('forumable_model')->nullable();
            $table->unsignedBigInteger('forumable_id')->nullable();

            $table->string('title')->nullable();
            $table->string('description')->nullable();

            $table->unsignedInteger('order_column')->nullable();

            /*
             * earlier creation dates are not known
             */
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('forum_category_id');
            $table->unique(['forumable_model', 'forumable_id']);

            $table->foreign('forum_category_id')->references('id')->on('forum_categories')->onDelete('set null');
        });

        Schema::create('forum_topics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('forum_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            /*
             * news have their own forum topic
             * games used to have their own topic -> forums now
             */
            $table->string('forumable_model')->nullable();
            $table->unsignedBigInteger('forumable_id')->nullable();

            $table->string('title')->nullable();
            $table->text('body')->nullable();

            /*
             * TODO: move this to permissions
             */
            $table->unsignedSmallInteger('permissions')->nullable();

            $table->timestampTz('pinned_at')->nullable();
            $table->timestampTz('locked_at')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('forum_id');
            $table->index('created_at');
            $table->unique(['forumable_model', 'forumable_id']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('forum_id')->references('id')->on('forums')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('forum_topics');
        Schema::dropIfExists('forums');
        Schema::dropIfExists('forum_categories');
    }
};
