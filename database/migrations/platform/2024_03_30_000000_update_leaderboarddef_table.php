<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('LeaderboardDef', function (Blueprint $table) {
            $table->unsignedBigInteger('author_id')->nullable()->after('Author');
            $table->foreign('author_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('LeaderboardDef', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
        });

        Schema::table('LeaderboardDef', function (Blueprint $table) {
            $table->dropColumn('author_id');
        });
    }
};
