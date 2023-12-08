<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('UserAccounts', function (Blueprint $table) {
            $table->unsignedInteger('websitePrefs')->nullable()->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('UserAccounts', function (Blueprint $table) {
            $table->unsignedSmallInteger('websitePrefs')->nullable()->default(0)->change();
        });
    }
};
