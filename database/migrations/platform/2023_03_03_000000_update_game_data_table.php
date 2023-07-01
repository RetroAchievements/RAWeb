<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumns('GameData', ['GuideURL'])) {
            Schema::table('GameData', function (Blueprint $table) {
                $table->string('GuideURL')->nullable()->after('TotalTruePoints');
            });
        }
    }

    public function down(): void
    {
        Schema::table('GameData', function (Blueprint $table) {
            $table->dropColumn('GuideURL');
        });
    }
};
