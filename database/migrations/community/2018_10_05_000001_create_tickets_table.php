<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('Ticket', function (Blueprint $table) {
            $table->bigIncrements('ID')->change();

            // nullable morphs
            $table->string('ticketable_model')->nullable()->after('ID');
            $table->unsignedBigInteger('ticketable_id')->nullable()->after('ticketable_model');
            $table->index(['ticketable_model', 'ticketable_id'], 'tickets_ticketable_index');
            $table->unique(['ticketable_model', 'ticketable_id', 'ReportedByUserID'], 'tickets_ticketable_reporter_id_index');

            $table->unsignedBigInteger('AchievementID')->nullable()->change();
            $table->unsignedBigInteger('ReportedByUserID')->nullable()->change();
            $table->unsignedBigInteger('ResolvedByUserID')->nullable()->change();

            // in case it is known which rom the user was playing when a bug occurred
            $table->unsignedBigInteger('game_hash_set_id')->nullable()->after('ReportedByUserID');

            /*
             * in case it is known "when" the user played it
             * when reporting a bug the user should be able to tell whether it occurred within the last given session
             */
            $table->unsignedBigInteger('player_session_id')->nullable()->after('game_hash_set_id');

            $table->softDeletesTz();

            $table->foreign('game_hash_set_id', 'tickets_game_hash_set_id_foreign')->references('id')->on('game_hash_sets')->onDelete('set null');
            $table->foreign('player_session_id', 'tickets_player_session_id_foreign')->references('id')->on('player_sessions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('Ticket', function (Blueprint $table) {
            $table->dropForeign('tickets_game_hash_set_id_foreign');
            $table->dropForeign('tickets_player_session_id_foreign');
            $table->dropIndex('tickets_ticketable_index');
            $table->dropUnique('tickets_ticketable_reporter_id_index');

            $table->dropColumn('ticketable_model');
            $table->dropColumn('ticketable_id');
            $table->dropColumn('game_hash_set_id');
            $table->dropColumn('player_session_id');

            $table->dropSoftDeletesTz();
        });
    }
};
