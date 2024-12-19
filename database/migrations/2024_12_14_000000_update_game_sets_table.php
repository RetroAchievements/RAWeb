<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('game_sets', function (Blueprint $table) {
            $table->text('internal_notes')->nullable()->after('game_id');
        });
    }

    public function down(): void
    {
        Schema::table('game_sets', function (Blueprint $table) {
            $table->dropColumn('internal_notes');
        });
    }
};
