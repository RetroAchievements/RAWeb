<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('Rating');
        Schema::dropIfExists('Votes');
    }

    public function down()
    {
        Schema::create('Rating', function ($table) {
            $table->bigIncrements('id');
            $table->string('ratable_model')->nullable();
            $table->unsignedBigInteger('ratable_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('User');
            $table->smallInteger('RatingObjectType');
            $table->smallInteger('RatingID');
            $table->smallInteger('RatingValue');
            $table->timestamp('Created')->nullable()->useCurrent();
            $table->timestamp('Updated')->nullable()->useCurrent()->useCurrentOnUpdate();
            
            $table->unique(['User', 'RatingObjectType', 'RatingID']);
            $table->index(['ratable_model', 'ratable_id'], 'ratings_ratable_index');
            $table->foreign('user_id', 'ratings_user_id_foreign')->references('ID')->on('UserAccounts')->onDelete('cascade');
        });

        Schema::create('Votes', function ($table) {
            $table->bigIncrements('id');
            $table->string('votable_model')->nullable();
            $table->unsignedBigInteger('votable_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('User', 50);
            $table->unsignedInteger('AchievementID');
            $table->tinyInteger('Vote');
            $table->timestamp('Created')->nullable()->useCurrent();
            $table->timestamp('Updated')->nullable()->useCurrent()->useCurrentOnUpdate();
            
            $table->unique(['User', 'AchievementID']);
            $table->index(['votable_model', 'votable_id'], 'votes_votable_index');
            $table->foreign('user_id', 'votes_user_id_foreign')->references('ID')->on('UserAccounts')->onDelete('cascade');
        });
    }
};
