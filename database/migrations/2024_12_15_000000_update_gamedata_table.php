<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('GameData', function (Blueprint $table) {
            $table->dropColumn(['Released', 'IsFinal']);
        });
    }

    public function down(): void
    {
        Schema::table('GameData', function (Blueprint $table) {
            $table->string('Released', 50)->nullable()->default(null)->after('Genre');
            $table->tinyInteger('IsFinal')->unsigned()->default(0)->after('releases');
        });
    }
};
