<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_awards', function (Blueprint $table) {
            $table->integer('display_award_tier')->nullable()->after('award_tier');
        });
    }

    public function down(): void
    {
        Schema::table('user_awards', function (Blueprint $table) {
            $table->dropColumn('display_award_tier');
        });
    }
};
