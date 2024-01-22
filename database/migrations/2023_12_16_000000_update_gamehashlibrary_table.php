<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('GameHashLibrary', function (Blueprint $table) {
            $table->string('compatibility')->nullable()->after('file_name_md5'); // `HashCompatibility`
            $table->string('patch_url')->nullable()->after('source_version'); // RAPatches .zip/.7z File Link
        });
    }

    public function down(): void
    {
        Schema::table('GameHashLibrary', function (Blueprint $table) {
            $table->dropColumn('compatibility');
            $table->dropColumn('patch_url');
        });
    }
};
