<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('UserAccounts', function (Blueprint $table) {
            $table->ulid('ulid')->after('ID')->unique()->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('UserAccounts', function (Blueprint $table) {
            $table->dropColumn('ulid');
        });
    }
};
