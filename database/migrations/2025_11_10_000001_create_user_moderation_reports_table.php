<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_moderation_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reporter_user_id');
            $table->unsignedBigInteger('reported_user_id')->nullable();
            $table->string('reportable_type', 255);
            $table->unsignedBigInteger('reportable_id');
            $table->unsignedBigInteger('message_thread_id');
            $table->timestamps();

            $table->index('reporter_user_id');
            $table->index('reported_user_id');
            $table->index(['reportable_type', 'reportable_id'], 'idx_reportable');

            $table->foreign('reporter_user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
            $table->foreign('reported_user_id')->references('ID')->on('UserAccounts')->onDelete('set null');
            $table->foreign('message_thread_id')->references('id')->on('message_threads')->onDelete('cascade');
        });

        Schema::table('discord_message_thread_mappings', function (Blueprint $table) {
            $table->dropIndex('idx_reportable_type_id');
            $table->dropColumn(['reportable_type', 'reportable_id']);

            $table->unsignedBigInteger('moderation_report_id')->nullable()->after('discord_thread_id');
            $table->index('moderation_report_id');

            $table->foreign('moderation_report_id')->references('id')->on('user_moderation_reports')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('discord_message_thread_mappings', function (Blueprint $table) {
            $table->dropForeign(['moderation_report_id']);
            $table->dropIndex(['moderation_report_id']);
            $table->dropColumn('moderation_report_id');

            $table->string('reportable_type', 255)->nullable()->after('discord_thread_id');
            $table->unsignedBigInteger('reportable_id')->nullable()->after('reportable_type');
            $table->index(['reportable_type', 'reportable_id'], 'idx_reportable_type_id');
        });

        Schema::dropIfExists('user_moderation_reports');
    }
};
