<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('Votes', function (Blueprint $table) {
            $table->dropPrimary(['User', 'AchievementID']);
        });

        Schema::table('Votes', function (Blueprint $table) {
            // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->bigIncrements('id')->first();
            }

            // nullable morphs
            $table->string('votable_model')->nullable()->after('id');
            $table->unsignedBigInteger('votable_id')->nullable()->after('votable_model');
            $table->index(['votable_model', 'votable_id'], 'votes_votable_index');

            $table->unsignedBigInteger('user_id')->nullable()->after('votable_id');

            // drop this in favor of ratable morph
            // kept to make sure only unique ratings exist
            $table->unique(['User', 'AchievementID'], 'votes_user_achievement_id_unique');

            $table->foreign('user_id', 'votes_user_id_foreign')->references('ID')->on('UserAccounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('Votes', function (Blueprint $table) {
            $table->dropIndex('votes_votable_index');
            $table->dropUnique('votes_user_achievement_id_unique');
            $table->dropForeign('votes_user_id_foreign');
            $table->dropColumn('id');
            $table->dropColumn('votable_model');
            $table->dropColumn('votable_id');
            $table->dropColumn('user_id');
        });

        Schema::table('Votes', function (Blueprint $table) {
            $table->primary(['User', 'AchievementID']);
        });
    }
};
