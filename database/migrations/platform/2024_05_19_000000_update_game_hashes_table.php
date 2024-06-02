<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        // SQLite doesn't support multiple calls to dropColumn / renameColumn in a single modification.
        Schema::table('game_hashes', function (Blueprint $table) {
            $table->dropColumn('User');
        });
    }

    public function down(): void
    {
        Schema::table('game_hashes', function (Blueprint $table) {
            $table->string('User', 32)->after('user_id');
        });
    }
};
