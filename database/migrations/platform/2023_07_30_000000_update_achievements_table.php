<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumns('Achievements', ['classification'])) {
            Schema::table('Achievements', function (Blueprint $table) {
                $table
                    ->tinyInteger('classification')
                    ->enum('classification', [1, 2])
                    ->nullable()
                    ->after('unlock_hardcore_percentage');
            });
        }
    }

    public function down(): void
    {
        Schema::table('Achievements', function (Blueprint $table) {
            $table->dropColumn('classification');
        });
    }
};
