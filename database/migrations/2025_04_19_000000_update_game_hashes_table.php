<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('game_hashes', function (Blueprint $table) {
            $table->unsignedBigInteger('compatibility_tester_id')->after('compatibility')->nullable();

            $table->foreign('compatibility_tester_id')
                ->references('ID')
                ->on('UserAccounts')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('game_hashes', function (Blueprint $table) {
            $table->dropForeign(['compatibility_tester_id']);
            $table->dropColumn('compatibility_tester_id');
        });
    }
};
