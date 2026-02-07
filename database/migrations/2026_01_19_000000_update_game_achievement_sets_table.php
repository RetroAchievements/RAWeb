<?php

declare(strict_types=1);

use App\Platform\Enums\AchievementSetType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('game_achievement_sets', function (Blueprint $table) {
            $table->string('type')->default(AchievementSetType::Core->value)->change();
            $table->unsignedInteger('order_column')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('game_achievement_sets', function (Blueprint $table) {
            $table->string('type')->nullable()->default(null)->change();
            $table->unsignedInteger('order_column')->nullable()->default(null)->change();
        });
    }
};
