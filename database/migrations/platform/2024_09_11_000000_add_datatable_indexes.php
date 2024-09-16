<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('Achievements', function (Blueprint $table) {
            $table->index(['GameID', 'DateModified', 'deleted_at']);
        });

        Schema::table('Ticket', function (Blueprint $table) {
            $table->index(['AchievementID', 'ReportState', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('Achievements', function (Blueprint $table) {
            $table->dropIndex(['GameID', 'DateModified', 'deleted_at']);
        });

        Schema::table('Ticket', function (Blueprint $table) {
            $table->dropIndex(['AchievementID', 'ReportState', 'deleted_at']);
        });
    }
};
