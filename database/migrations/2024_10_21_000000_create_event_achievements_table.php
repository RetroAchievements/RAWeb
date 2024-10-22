<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('event_achievements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('achievement_id');
            $table->unsignedBigInteger('source_achievement_id')->nullable();
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_until')->nullable();
            $table->timestamps();
        });

        Schema::table('event_achievements', function (Blueprint $table) {
            $table->foreign('achievement_id')
                ->references('ID')
                ->on('Achievements')
                ->onDelete('cascade');

            $table->foreign('source_achievement_id')
                ->references('ID')
                ->on('Achievements')
                ->onDelete('cascade');

            $table->index('source_achievement_id');
            $table->index('active_from');
            $table->index('active_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_achievements');
    }
};
