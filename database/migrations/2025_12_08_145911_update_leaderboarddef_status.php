<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('LeaderboardDef', function (Blueprint $table) {
            $table->string('state')->default('active')->after('DisplayOrder')->index();
        });
    }

    public function down(): void
    {
        Schema::table('LeaderboardDef', function (Blueprint $table) {
            $table->dropIndex(['state']);
            $table->dropColumn('state');
        });
    }
};
