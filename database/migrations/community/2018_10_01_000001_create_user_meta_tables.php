<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        Schema::create('user_activity_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->timestampTz('created_at')->nullable();
            $table->timestampTz('updated_at')->nullable();
            $table->smallInteger('activity_type_id');
            $table->nullableMorphs('subject', 'user_activity_log_subject_index');
            $table->unsignedBigInteger('subject_context')->nullable();

            $table->index('user_id');
            $table->index('activity_type_id');
            $table->index('created_at');
            $table->index('updated_at');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        /*
         * https://github.com/maynagashev/laravel-social-connections/blob/master/src/migrations/2017_02_27_152820_create_social_logins_table.php
         */
        Schema::create('user_connections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('provider', 60);
            $table->string('provider_user_id')->nullable();
            $table->string('token')->nullable();
            $table->string('token_secret')->nullable();
            $table->string('refresh_token')->nullable();
            $table->string('expires')->nullable();
            $table->string('nickname')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('avatar')->nullable();
            $table->string('url')->nullable();
            $table->jsonb('raw')->nullable();
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'provider', 'provider_user_id']);
            $table->index(['provider', 'provider_user_id']);
        });

        Schema::create('user_relations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('related_user_id');
            $table->unsignedSmallInteger('status_flag')->nullable();

            /*
             * earlier creation dates are not known
             */
            $table->timestampsTz();

            $table->foreign('related_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_activity_log');
        Schema::dropIfExists('user_connections');
        Schema::dropIfExists('user_relations');
    }
};
