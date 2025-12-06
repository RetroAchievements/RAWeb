<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('discord_message_thread_mappings', function (Blueprint $table) {
            $table->string('reportable_type', 255)->nullable()->after('discord_thread_id');
            $table->unsignedBigInteger('reportable_id')->nullable()->after('reportable_type');

            $table->index(['reportable_type', 'reportable_id'], 'idx_reportable_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('discord_message_thread_mappings', function (Blueprint $table) {
            $table->dropIndex('idx_reportable_type_id');
            $table->dropColumn(['reportable_type', 'reportable_id']);
        });
    }
};
