<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('GameHashLibrary', function (Blueprint $table) {
            $table->string('internal_status')->nullable()->after('file_name_md5'); // Unverified, In Progress, Verified, Problematic
            $table->string('internal_patch_url')->nullable()->after('source_version'); // RAPatches .zip File Link
        });
    }

    public function down(): void
    {
        Schema::table('GameHashLibrary', function (Blueprint $table) {
            $table->dropColumn('internal_status');
            $table->dropColumn('internal_patch_url');
        });
    }
};
