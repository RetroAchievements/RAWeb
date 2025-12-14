<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('game_sets', function (Blueprint $table) {
            $table->string('sort_title')->nullable()->after('title');
            $table->index('sort_title');
        });
    }

    public function down(): void
    {
        Schema::table('game_sets', function (Blueprint $table) {
            $table->dropIndex(['sort_title']);
            $table->dropColumn('sort_title');
        });
    }
};
