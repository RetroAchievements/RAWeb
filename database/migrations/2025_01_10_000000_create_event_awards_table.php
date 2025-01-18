<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('event_awards')) {
            Schema::create('event_awards', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('event_id');
                $table->integer('tier_index');
                $table->string('label', 40);
                $table->integer('achievements_required');
                $table->string('image_asset_path', 50);
                $table->timestamps();
            });

            Schema::table('event_awards', function (Blueprint $table) {
                $table->foreign('event_id')
                    ->references('id')
                    ->on('events')
                    ->onDelete('cascade');
    
                $table->unique(['event_id', 'tier_index']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_awards');
    }
};
