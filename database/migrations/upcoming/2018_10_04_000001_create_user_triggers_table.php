<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        /*
         * let users "sign into" development versions of a trigger
         * this allows to have multiple devs work on a trigger at the same time
         *
         * all devs on one trigger version are "owner" - yet, there is an actual owner on a trigger
         *
         * note: a user should not be able to have more than one override active for an achievement?
         */
        Schema::create('user_triggers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('trigger_id');
            $table->timestampsTz();
            /*
             * let's have those deleted for good
             * we don't have to keep everything
             */
            // $table->softDeletesTz();

            $table->unique(['user_id', 'trigger_id']);

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
            $table->foreign('trigger_id')->references('id')->on('triggers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_triggers');
    }
};
