<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('game_sets', function (Blueprint $table) {
            $table->boolean('has_mature_content')->default(false)->after('definition');
        });
    }

    public function down(): void
    {
        Schema::table('game_sets', function (Blueprint $table) {
            $table->dropColumn('has_mature_content');
        });
    }
};
