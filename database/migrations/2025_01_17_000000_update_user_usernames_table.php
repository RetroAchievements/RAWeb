<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('user_usernames', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('username');
        });
    }

    public function down(): void
    {
        Schema::table('user_usernames', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};
