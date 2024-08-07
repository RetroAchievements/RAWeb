<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('EmailConfirmations', function (Blueprint $table) {
            if (!Schema::hasColumn('EmailConfirmations', 'id')) {
                $table->increments('id')->first();
            }

            $table->unsignedBigInteger('user_id')->nullable()->after('User');
        });

        Schema::table('EmailConfirmations', function (Blueprint $table) {
            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('EmailConfirmations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            $table->dropColumn('id');
        });
    }
};
