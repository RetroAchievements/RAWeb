<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('event_achievements', function (Blueprint $table) {
            $table->string('decorator', 96)->nullable()->after('active_until');
        });
    }

    public function down(): void
    {
        Schema::table('event_achievements', function (Blueprint $table) {
            $table->dropColumn('decorator');
        });
    }
};
