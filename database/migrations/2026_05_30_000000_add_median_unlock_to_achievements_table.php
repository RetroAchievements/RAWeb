<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->integer('median_time_to_unlock_samples')->nullable()->after('unlock_hardcore_percentage');
            $table->integer('median_time_to_unlock_hardcore_samples')->nullable()->after('median_time_to_unlock_samples');
            $table->integer('median_time_to_unlock')->nullable()->after('median_time_to_unlock_hardcore_samples');
            $table->integer('median_time_to_unlock_hardcore')->nullable()->after('median_time_to_unlock');
        });
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropColumn('median_time_to_unlock_samples');
            $table->dropColumn('median_time_to_unlock_hardcore_samples');
            $table->dropColumn('median_time_to_unlock');
            $table->dropColumn('median_time_to_unlock_hardcore');
        });
    }
};
