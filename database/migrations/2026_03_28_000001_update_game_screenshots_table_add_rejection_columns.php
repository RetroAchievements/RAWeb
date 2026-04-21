<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('game_screenshots', function (Blueprint $table) {
            $table->string('rejection_reason', 30)->nullable()->after('reviewed_by_user_id');
            $table->text('rejection_notes')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('game_screenshots', function (Blueprint $table) {
            $table->dropColumn(['rejection_reason', 'rejection_notes']);
        });
    }
};
