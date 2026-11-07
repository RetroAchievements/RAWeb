<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->renameColumn('signal_verdict', 'current_signal_verdict');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->string('resolved_signal_verdict', 30)->nullable()->after('current_signal_verdict');
        });

        Schema::create('ticket_verdict_accuracy_results', function (Blueprint $table) {
            $table->id();
            $table->string('verdict', 30);
            $table->unsignedInteger('ticket_count');
            $table->unsignedInteger('correct_count');
            $table->timestamp('window_started_at');
            $table->timestamp('window_ended_at');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_verdict_accuracy_results');

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('resolved_signal_verdict');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->renameColumn('current_signal_verdict', 'signal_verdict');
        });
    }
};
