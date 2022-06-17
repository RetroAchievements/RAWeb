<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->morphs('ticketable');

            /*
             * who reported it
             */
            $table->unsignedBigInteger('user_id')->nullable();

            /*
             * in case it is known which rom the user was playing when a bug occurred
             */
            $table->unsignedBigInteger('game_hash_set_id')->nullable();

            /*
             * in case it is known "when" the user played it
             * when reporting a bug the user should be able to tell whether it occurred within the last given session
             */
            $table->unsignedBigInteger('player_session_id')->nullable();

            $table->text('body')->nullable();

            /*
             * TODO: validate those
             */
            $table->string('type')->nullable(); // ?
            $table->unsignedSmallInteger('type_flag')->nullable(); // ?
            $table->string('state')->nullable(); // ?
            $table->unsignedSmallInteger('state_flag')->nullable(); // ?

            /*
             * created_at = reported at
             */
            $table->timestampsTz();
            /*
             * deleted_at = closed at
             */
            $table->softDeletesTz();

            $table->index('user_id');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('player_session_id')->references('id')->on('player_sessions')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tickets');
    }
};
