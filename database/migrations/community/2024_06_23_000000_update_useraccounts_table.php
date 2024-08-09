<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('UserAccounts', function (Blueprint $table) {
            $table->unique('display_name');
        });
    }

    public function down(): void
    {
        Schema::table('UserAccounts', function (Blueprint $table) {
            $table->dropUnique(['display_name']);
        });
    }
};
