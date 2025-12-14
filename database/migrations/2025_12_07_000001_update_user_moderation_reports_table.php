<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_moderation_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('action_id')->nullable()->after('message_thread_id');

            $table->foreign('action_id')
                ->references('id')
                ->on('user_moderation_actions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_moderation_reports', function (Blueprint $table) {
            $table->dropForeign(['action_id']);
            $table->dropColumn('action_id');
        });
    }
};
