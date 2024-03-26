<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('Comment', function (Blueprint $table) {
            $table->renameColumn('UserID', 'user_id');
        });

        Schema::table('Comment', function (Blueprint $table) {
            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('Comment', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('Comment', function (Blueprint $table) {
            $table->renameColumn('user_id', 'UserID');
        });
    }
};
