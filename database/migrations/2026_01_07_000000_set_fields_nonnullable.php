<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::table('leaderboards')->whereNull('format')->update(['format' => '']);
        DB::table('leaderboards')->whereNull('title')->update(['title' => '']);
        DB::table('leaderboards')->whereNull('description')->update(['description' => '']);
        DB::table('achievements')->whereNull('description')->update(['description' => '']);

        Schema::table('leaderboards', function (Blueprint $table) {
            $table->string('format', 50)->default('')->nullable(false)->change();
            $table->string('title', 255)->default('')->nullable(false)->change();
            $table->string('description', 255)->default('')->nullable(false)->change();
        });

        Schema::table('achievements', function (Blueprint $table) {
            $table->string('description', 255)->default('')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('leaderboards', function (Blueprint $table) {
            $table->string('format', 50)->default('')->nullable()->change();
            $table->string('title', 255)->default('Leaderboard Title')->nullable()->change();
            $table->string('description', 255)->default('Leaderboard Description')->nullable()->change();
        });

        Schema::table('achievements', function (Blueprint $table) {
            $table->string('description', 255)->nullable()->change();
        });
    }
};
