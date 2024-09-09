<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('emulators', function (Blueprint $table) {
            $table->dropIndex('emulators_integration_id_unique');
            $table->dropColumn('integration_id');
            $table->dropColumn('game_hash_column');
            $table->dropColumn('link');
            $table->renameColumn('handle', 'original_name');
            $table->string('documentation_url', 255)->nullable()->after('description');
            $table->string('download_url', 255)->nullable()->after('documentation_url');
            $table->string('download_x64_url', 255)->nullable()->after('download_url');
            $table->string('source_url', 255)->nullable()->after('download_x64_url');
        });
    }

    public function down(): void
    {
        Schema::table('emulators', function (Blueprint $table) {
            $table->string('integration_id')->nullable()->after('id');
            $table->unique('integration_id');
            $table->text('link')->nullable()->after('description');
            $table->text('game_hash_column')->nullable()->after('link');
            $table->renameColumn('original_name', 'handle');
            $table->dropColumn('documentation_url');
            $table->dropColumn('download_url');
            $table->dropColumn('download_x64_url');
            $table->dropColumn('source_url');
        });
    }
};
