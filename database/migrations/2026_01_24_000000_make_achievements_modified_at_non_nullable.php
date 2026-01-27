<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::table('achievements')
            ->whereNull('modified_at')
            ->update(['modified_at' => now()]);

        Schema::table('achievements', function (Blueprint $table) {
            $table->timestamp('modified_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->timestamp('modified_at')->nullable()->default(null)->change();
        });
    }
};
