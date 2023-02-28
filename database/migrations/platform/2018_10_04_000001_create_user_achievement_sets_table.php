<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        /*
         * users can opt into multiple sets of a game
         * community sets are served with unofficial achievements (TODO: or with official as well?)
         */
        Schema::create('user_achievement_sets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');

            $table->unsignedBigInteger('achievement_set_id');

            /*
             * no reference to version here -> always playing the latest version of the set
             * if other achievements should be in there for development -> additional set opt-in
             */
            // $table->unsignedInteger('achievement_set_version')->nullable();

            $table->timestampsTz();
            /*
             * let's have those deleted for good
             * we don't have to keep everything
             * note: removing the official core set should probably be prevented
             */
            // $table->softDeletesTz();

            /*
             * users should only sign into a set once
             */
            $table->unique(['user_id', 'achievement_set_id']);

            // $table->index(['achievement_set_id', 'achievement_set_version'], 'user_achievement_sets_id_version_index');

            $table->foreign('achievement_set_id')->references('id')->on('achievement_sets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_achievement_sets');
    }
};
