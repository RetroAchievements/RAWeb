<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_beta_feedback_submissions', function (Blueprint $table) {
            $table->string('app_version', 50)->nullable()->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('user_beta_feedback_submissions', function (Blueprint $table) {
            $table->dropColumn('app_version');
        });
    }
};
