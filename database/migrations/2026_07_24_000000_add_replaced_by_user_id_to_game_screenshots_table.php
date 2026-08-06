<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('game_screenshots', function (Blueprint $table) {
            $table->foreignId('replaced_by_user_id')
                ->nullable()
                ->after('captured_by_user_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('game_screenshots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replaced_by_user_id');
        });
    }
};
