<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('LeaderboardDef', function (Blueprint $table) {
            $table->dropColumn('Author');
        });
    }

    public function down(): void
    {
        Schema::table('LeaderboardDef', function (Blueprint $table) {
            $table->string('Author', 32)->after('DisplayOrder');
        });
    }
};
