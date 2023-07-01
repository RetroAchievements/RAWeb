<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->increments('id');

            $table->string('title');
            $table->text('description');

            /*
             * what type of event is it?
             * aotw
             * leapfrog
             * motm
             * aotm
             */
            $table->string('type');

            /*
             * week, month, year
             * null => individual
             */
            $table->string('recurring');

            // not needed -> use forumable instead
            // $table->unsignedInteger('forum_topic_id');

            // somebody created/owns the event
            $table->unsignedBigInteger('user_id')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });

        /*
         * for recurring events
         */
        Schema::create('event_dates', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedInteger('event_id')->nullable();

            // somebody created/owns the event date
            $table->unsignedBigInteger('user_id')->nullable();

            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });

        /*
         * the entries to  dates of an event
         */
        Schema::create('event_entries', function (Blueprint $table) {
            $table->bigIncrements('id');

            // users enter events
            $table->unsignedBigInteger('event_date_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('event_date_id')->references('id')->on('event_dates')->onDelete('cascade');

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_entries');
        Schema::dropIfExists('event_dates');
        Schema::dropIfExists('events');
    }
};
