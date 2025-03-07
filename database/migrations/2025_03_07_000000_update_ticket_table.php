<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('Ticket', function (Blueprint $table) {
            $table->dropForeign('tickets_game_hash_set_id_foreign');
            $table->dropColumn('game_hash_set_id');
            $table->dropForeign('tickets_player_session_id_foreign');
            $table->dropColumn('player_session_id');

            $table->unsignedBigInteger('game_hash_id')->nullable()->after('AchievementID');
            $table->unsignedInteger('emulator_id')->nullable()->after('game_hash_id');
            $table->string('emulator_version', 32)->nullable()->after('emulator_id');
            $table->string('emulator_core', 96)->nullable()->after('emulator_version');

            $table->foreign('game_hash_id', 'tickets_game_hash_id_foreign')
                ->references('id')
                ->on('game_hashes')
                ->onDelete('set null');

            $table->foreign('emulator_id', 'tickets_emulator_id_foreign')
                ->references('id')
                ->on('emulators')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('Ticket', function (Blueprint $table) {
            $table->dropForeign('tickets_emulator_id_foreign');
            $table->dropForeign('tickets_game_hash_id_foreign');
            $table->dropColumn('emulator_core');
            $table->dropColumn('emulator_version');
            $table->dropColumn('emulator_id');
            $table->dropColumn('game_hash_id');

            $table->unsignedBigInteger('game_hash_set_id')->nullable()->after('AchievementID');
            $table->unsignedBigInteger('player_session_id')->nullable()->after('game_hash_set_id');

            $table->foreign('game_hash_set_id', 'tickets_game_hash_set_id_foreign')
                ->references('id')
                ->on('game_hash_sets')
                ->onDelete('set null');

            $table->foreign('player_session_id', 'tickets_player_session_id_foreign')
                ->references('id')
                ->on('player_sessions')
                ->onDelete('set null');
        });
    }
};
