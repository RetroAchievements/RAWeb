<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('SetRequest', function (Blueprint $table) {
            $table->renameColumn('GameID', 'game_id');
            $table->renameColumn('Updated', 'updated_at');
        });

        Schema::rename('SetRequest', 'user_game_list_entries');

        Schema::table('user_game_list_entries', function (Blueprint $table) {
            $table->renameIndex('setrequest_gameid_type_index', 'user_game_list_entries_game_id_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('user_game_list_entries', function (Blueprint $table) {
            $table->renameIndex('user_game_list_entries_game_id_type_index', 'setrequest_gameid_type_index');
        });

        Schema::rename('user_game_list_entries', 'SetRequest');

        Schema::table('SetRequest', function (Blueprint $table) {
            $table->renameColumn('game_id', 'GameID');
            $table->renameColumn('updated_at', 'Updated');
        });
    }
};
