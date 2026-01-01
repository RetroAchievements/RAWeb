<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::rename('LeaderboardDef', 'leaderboards');

        Schema::table('leaderboards', function (Blueprint $table) {
            $table->renameColumn('ID', 'id');
            $table->renameColumn('GameID', 'game_id');
            $table->renameColumn('Title', 'title');
            $table->renameColumn('Description', 'description');
            $table->renameColumn('Format', 'format');
            $table->renameColumn('LowerIsBetter', 'rank_asc');
            $table->renameColumn('DisplayOrder', 'order_column');
            $table->renameColumn('Created', 'created_at');
            $table->renameColumn('Updated', 'updated_at');
            $table->renameColumn('Mem', 'trigger_definition');
        });
    }

    public function down(): void
    {
        Schema::table('leaderboards', function (Blueprint $table) {
            $table->renameColumn('id', 'ID');
            $table->renameColumn('game_id', 'GameID');
            $table->renameColumn('title', 'Title');
            $table->renameColumn('description', 'Description');
            $table->renameColumn('format', 'Format');
            $table->renameColumn('rank_asc', 'LowerIsBetter');
            $table->renameColumn('order_column', 'DisplayOrder');
            $table->renameColumn('created_at', 'Created');
            $table->renameColumn('updated_at', 'Updated');
            $table->renameColumn('trigger_definition', 'Mem');
        });

        Schema::rename('leaderboards', 'LeaderboardDef');
    }
};
