<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('triggers')) {
            return;
        }

        /*
         * triggers are versioned
         * multiple devs should be able to push new triggers
         * only one version can be the active
         * the "stable" one should be linked on the triggerable itself for convenience
         */
        Schema::create('triggers', function (Blueprint $table) {
            $table->bigIncrements('id');

            /*
             * may be achievement, hash set (rp), leaderboard
             */
            $table->morphs('triggerable');

            /*
             * trigger has an initial create/owner
             * may be null in case it's unknown or the user is removed -> "community space"
             */
            $table->unsignedBigInteger('user_id')->nullable();

            /*
             * the version may be nullable
             * if a version is present, it means it's an "officially registered" version
             * unversioned triggers are development states
             * if "promoted to core" it will add an incremented version on it
             */
            $table->unsignedInteger('version')->nullable();

            /*
             * parent id should be a reference to the trigger version that the current one is based on
             * allows to determine which achievement a developer version is based on
             * may be used to notify a user that their achievement is outdated because a new version was promoted
             * only do that once when [parent achievement version] == [just published achievement version] - 1
             */
            $table->unsignedBigInteger('parent_id')->nullable();

            /*
             * the interesting bit here
             */
            $table->text('conditions')->nullable();

            /*
             * type may be ratrigger, rascript, lua, something else?
             */
            $table->text('type')->nullable();

            // stats for "measured"
            $table->string('stat')->nullable();
            $table->string('stat_goal')->nullable();
            $table->string('stat_format', 50)->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['triggerable_type', 'triggerable_id', 'version']);

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triggers');
    }
};
