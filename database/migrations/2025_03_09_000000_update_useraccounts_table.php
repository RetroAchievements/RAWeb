<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('UserAccounts', function (Blueprint $table) {
            $table->index(
                ['Untracked', 'Deleted', 'RAPoints', 'TrueRAPoints'],
                'user_accounts_leaderboard_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('UserAccounts', function (Blueprint $table) {
            $table->dropIndex('user_accounts_leaderboard_index');
        });
    }
};
