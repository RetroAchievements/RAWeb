<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        /*
         * users reach badge stages for:
         * - reaching a certain progress on a badge that tracks a stat
         * - completing an achievement set multiple times because of version changes
         * - events
         */
        Schema::create('user_badge_stages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('badge_stage_id');

            /*
             * TODO: needed?
             */
            // $table->unsignedInteger('extra')->nullable();

            /*
             * reference may be
             * - game session for achievements
             * - an activity log event
             * - game session for achievement_set completion -> can reveal data on achievement_set_version
             */
            $table->nullableMorphs('reference');

            $table->timestampsTz();
            /*
             * deleted_at == revoked
             */
            $table->softDeletesTz();

            $table->unique(['user_id', 'badge_stage_id']);

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
            $table->foreign('badge_stage_id')->references('id')->on('badge_stages')->onDelete('cascade');
        });

        /*
         * users may have progress on badges
         */
        Schema::create('user_badges', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('badge_id');

            /*
             * reference may be game session for achievements, an activity event
             */
            $table->nullableMorphs('reference');

            $table->unsignedBigInteger('stat')->nullable();
            $table->timestampTz('completed_at');

            $table->timestampsTz();

            /*
             * deleted_at == revoked
             */
            $table->softDeletesTz();

            $table->unique(['user_id', 'badge_id']);

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
            $table->foreign('badge_id')->references('id')->on('badges')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badge_stages');
        Schema::dropIfExists('user_badges');
    }
};
