<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        if (
            !Schema::hasColumns('StaticData', [
                'num_hardcore_mastery_awards',
                'num_hardcore_game_beaten_awards',
                'last_game_hardcore_mastered_game_id',
                'last_game_hardcore_mastered_user_id',
                'last_game_hardcore_mastered_at',
                'last_game_hardcore_beaten_game_id',
                'last_game_hardcore_beaten_user_id',
                'last_game_hardcore_beaten_at',
            ])
        ) {
            Schema::table('StaticData', function (Blueprint $table) {
                $table->unsignedInteger('num_hardcore_mastery_awards')->after('NumRegisteredUsers')->default(0);
                $table->unsignedInteger('num_hardcore_game_beaten_awards')->after('num_hardcore_mastery_awards')->default(0);
                $table->unsignedBigInteger('last_game_hardcore_mastered_game_id')->after('num_hardcore_game_beaten_awards')->default(1);
                $table->unsignedBigInteger('last_game_hardcore_mastered_user_id')->after('last_game_hardcore_mastered_game_id')->default(1);
                $table->timestampTz('last_game_hardcore_mastered_at')->nullable()->after('last_game_hardcore_mastered_user_id');
                $table->unsignedBigInteger('last_game_hardcore_beaten_game_id')->after('last_game_hardcore_mastered_at')->default(1);
                $table->unsignedBigInteger('last_game_hardcore_beaten_user_id')->after('last_game_hardcore_beaten_game_id')->default(1);
                $table->timestampTz('last_game_hardcore_beaten_at')->nullable()->after('last_game_hardcore_beaten_user_id');

                $table
                    ->foreign('last_game_hardcore_mastered_game_id', 'last_game_hardcore_mastered_game_id_foreign')
                    ->references('ID')
                    ->on('GameData')
                    ->onDelete('restrict');

                $table
                    ->foreign('last_game_hardcore_mastered_user_id', 'last_game_hardcore_mastered_user_id_foreign')
                    ->references('ID')
                    ->on('UserAccounts')
                    ->onDelete('restrict');

                $table
                    ->foreign('last_game_hardcore_beaten_game_id', 'last_game_hardcore_beaten_game_id_foreign')
                    ->references('ID')
                    ->on('GameData')
                    ->onDelete('restrict');

                $table
                    ->foreign('last_game_hardcore_beaten_user_id', 'last_game_hardcore_beaten_user_id_foreign')
                    ->references('ID')
                    ->on('UserAccounts')
                    ->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::table('StaticData', function (Blueprint $table) {
            $table->dropForeign('last_game_hardcore_mastered_game_id_foreign');
            $table->dropForeign('last_game_hardcore_mastered_user_id_foreign');
            $table->dropForeign('last_game_hardcore_beaten_game_id_foreign');
            $table->dropForeign('last_game_hardcore_beaten_user_id_foreign');

            $table->dropColumn([
                'num_hardcore_mastery_awards',
                'num_hardcore_game_beaten_awards',
                'last_game_hardcore_mastered_game_id',
                'last_game_hardcore_mastered_user_id',
                'last_game_hardcore_mastered_at',
                'last_game_hardcore_beaten_game_id',
                'last_game_hardcore_beaten_user_id',
                'last_game_hardcore_beaten_at',
            ]);
        });
    }
};
