<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('achievement_set_versions', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('version');

            $table->unsignedInteger('version')->nullable(false)->default(1)->change();
            $table->unsignedInteger('players_total')->nullable(false)->default(0)->change();
            $table->unsignedInteger('players_hardcore')->nullable(false)->default(0)->change();
            $table->unsignedInteger('achievements_published')->nullable(false)->default(0)->change();
            $table->unsignedInteger('achievements_unpublished')->nullable(false)->default(0)->change();
            $table->unsignedInteger('points_total')->nullable(false)->default(0)->change();
            $table->unsignedInteger('points_weighted')->nullable(false)->default(0)->change();

            $table->foreign('parent_id')
                ->references('id')
                ->on('achievement_set_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('achievement_set_versions', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id']);

            $table->unsignedInteger('version')->nullable()->change();
            $table->unsignedInteger('players_total')->nullable()->change();
            $table->unsignedInteger('players_hardcore')->nullable()->change();
            $table->unsignedInteger('achievements_published')->nullable()->change();
            $table->unsignedInteger('achievements_unpublished')->nullable()->change();
            $table->unsignedInteger('points_total')->nullable()->change();
            $table->unsignedInteger('points_weighted')->nullable()->change();
        });
    }
};
