<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('SiteAwards', function (Blueprint $table) {
            $table->dropIndex(['User']);
        });
        Schema::table('SiteAwards', function (Blueprint $table) {
            $table->dropUnique(['User', 'AwardData', 'AwardType', 'AwardDataExtra']);
        });
        Schema::table('SiteAwards', function (Blueprint $table) {
            $table->dropColumn('User');
        });
        Schema::table('SiteAwards', function (Blueprint $table) {
            $table->index(['user_id', 'AwardData', 'AwardType', 'AwardDataExtra']);
        });
    }

    public function down(): void
    {
        Schema::table('SiteAwards', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'AwardData', 'AwardType', 'AwardDataExtra']);
            $table->string('User', 50)->after('AwardDate');
            $table->index('User');
        });
    }
};
