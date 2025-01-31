<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('Comment', function (Blueprint $table) {
            $table->index(['user_id', 'Submitted']);
        });
    }

    public function down(): void
    {
        Schema::table('Comment', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'Submitted']);
        });
    }
};
