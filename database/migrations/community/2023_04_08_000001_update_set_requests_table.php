<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('SetRequest', function (Blueprint $table) {
            $table->dropPrimary(['User', 'GameID']);
        });

        Schema::table('SetRequest', function (Blueprint $table) {
            // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->bigIncrements('id')->first();
            }
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->unsignedBigInteger('GameID')->change();

            $table->string('type')->nullable()->after('GameID');

            $table->timestampTz('created_at')->nullable()->after('type');

            $table->unique(['User', 'GameID', 'type'], 'user_game_list_entry_username_game_id_type_unique');
            $table->unique(['user_id', 'GameID', 'type'], 'user_game_list_entry_user_id_game_id_type_unique');

            $table->foreign('user_id', 'user_game_list_entry_user_id_foreign')->references('ID')->on('UserAccounts')->onDelete('cascade');
            $table->foreign('GameID', 'user_game_list_entry_game_id_foreign')->references('ID')->on('GameData')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('SetRequest', function (Blueprint $table) {
            $table->dropForeign('user_game_list_entry_user_id_foreign');
            $table->dropForeign('user_game_list_entry_game_id_foreign');
            $table->dropUnique('user_game_list_entry_username_game_id_type_unique');
            $table->dropUnique('user_game_list_entry_user_id_game_id_type_unique');
            $table->dropColumn('id');
            $table->dropColumn('user_id');
            $table->dropColumn('type');
            $table->dropColumn('created_at');
        });

        Schema::table('SetRequest', function (Blueprint $table) {
            $table->primary(['User', 'GameID']);
        });
    }
};
