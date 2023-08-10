<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumns('Achievements', ['type'])) {
            Schema::table('Achievements', function (Blueprint $table) {
                $table->string('type')->nullable()->after('Flags')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::table('Achievements', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
