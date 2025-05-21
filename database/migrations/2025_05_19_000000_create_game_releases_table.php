<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('game_releases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id');
            $table->timestamp('released_at')->nullable(); // matches GameData.released_at
            $table->string('released_at_granularity')->nullable(); // matches GameData.released_at_granularity
            $table->string('title', 80); // matches GameData.Title
            $table->string('region', 20)->nullable();
            $table->boolean('is_canonical_game_title')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('title');

            $table->foreign('game_id')
                ->references('ID')
                ->on('GameData')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_releases');
    }
};
