<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('SetRequest', function (Blueprint $table) {
            $table->dropColumn('User');
        });
    }

    public function down(): void
    {
        Schema::table('SetRequest', function (Blueprint $table) {
            $table->string('User', 32);
        });
    }
};
