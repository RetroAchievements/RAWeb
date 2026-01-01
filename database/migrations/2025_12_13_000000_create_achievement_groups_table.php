<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('achievement_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('achievement_set_id');
            $table->string('label', 100);
            $table->integer('order_column')->default(0);
            $table->timestamps();

            $table->foreign('achievement_set_id')
                ->references('id')
                ->on('achievement_sets')
                ->onDelete('cascade');

            $table->index(['achievement_set_id', 'order_column']);
        });

        Schema::table('achievement_set_achievements', function (Blueprint $table) {
            $table->unsignedBigInteger('achievement_group_id')->nullable()->after('achievement_id');

            $table->foreign('achievement_group_id')
                ->references('id')
                ->on('achievement_groups')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('achievement_set_achievements', function (Blueprint $table) {
            $table->dropForeign(['achievement_group_id']);
            $table->dropColumn('achievement_group_id');
        });

        Schema::dropIfExists('achievement_groups');
    }
};
