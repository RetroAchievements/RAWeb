<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // This column may already exist because it was added directly on some environments to avoid a locking table rebuild.
        if (Schema::hasColumn('users', 'avatar_updated_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('avatar_updated_at')->nullable()->after('muted_until');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'avatar_updated_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_updated_at');
        });
    }
};
