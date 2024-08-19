<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('game_set_links', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('parent_game_set_id');
            $table->unsignedBigInteger('child_game_set_id');
            $table->timestamps();
        });

        Schema::table('game_set_links', function (Blueprint $table) {
            $table->foreign('parent_game_set_id')
                ->references('id')
                ->on('game_sets')
                ->onDelete('cascade');

            $table->foreign('child_game_set_id')
                ->references('id')
                ->on('game_sets')
                ->onDelete('cascade');

            $table->index('parent_game_set_id');
            $table->index('child_game_set_id');

            $table->unique(['parent_game_set_id', 'child_game_set_id']);
        });

        Schema::table('game_sets', function (Blueprint $table) {
            $table->string('type')->after('id');
            $table->string('title', 80)->nullable()->after('type');
            $table->string('image_asset_path', 50)->nullable()->after('title')->default('/Images/000001.png');
            $table->renameColumn('legacy_game_id', 'game_id');
        });

        Schema::table('game_sets', function (Blueprint $table) {
            $table->foreign('game_id')->references('ID')->on('GameData')->onDelete('cascade');
        });

        Schema::table('game_set_games', function (Blueprint $table) {
            $table->unique(['game_set_id', 'game_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_set_links');

        Schema::table('game_sets', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('title');
            $table->dropColumn('image_asset_path');
            $table->renameColumn('game_id', 'legacy_game_id');
            $table->dropForeign(['game_id']);
        });

        Schema::table('game_set_games', function (Blueprint $table) {
            $table->dropUnique(['game_set_id', 'game_id']);
        });
    }
};
