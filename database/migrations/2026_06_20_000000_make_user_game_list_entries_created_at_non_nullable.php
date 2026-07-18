<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::table('user_game_list_entries')
            ->whereNull('created_at')
            ->update(['created_at' => DB::raw('updated_at')]);

        // MariaDB 10.11 cannot do this ALTER in-place.
        Schema::table('user_game_list_entries', function (Blueprint $table) {
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_game_list_entries', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->default(null)->change();
        });
    }
};
