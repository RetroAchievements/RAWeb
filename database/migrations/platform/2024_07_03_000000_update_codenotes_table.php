<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::rename('CodeNotes', 'memory_notes');

        // SQLite doesn't support multiple calls to dropColumn / renameColumn in a single modification.
        Schema::table('memory_notes', function (Blueprint $table) {
            $table->renameColumn('Address', 'address');
        });
        Schema::table('memory_notes', function (Blueprint $table) {
            $table->renameColumn('Note', 'body');
        });
        Schema::table('memory_notes', function (Blueprint $table) {
            $table->renameColumn('Created', 'created_at');
        });
        Schema::table('memory_notes', function (Blueprint $table) {
            $table->renameColumn('Updated', 'updated_at');
        });
        Schema::table('memory_notes', function (Blueprint $table) {
            $table->renameColumn('GameID', 'game_id');
        });

        Schema::table('memory_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('game_id')->change();

            $table->foreign('game_id')->references('ID')->on('GameData')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('memory_notes', function (Blueprint $table) {
            $table->dropForeign(['game_id']);

            $table->renameColumn('game_id', 'GameID');
            $table->renameColumn('address', 'Address');
            $table->renameColumn('body', 'Note');
            $table->renameColumn('created_at', 'Created');
            $table->renameColumn('updated_at', 'Updated');
        });

        Schema::rename('memory_notes', 'CodeNotes');
    }
};
