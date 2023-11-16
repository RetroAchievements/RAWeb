<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('SiteAwards', function (Blueprint $table) {
            $table->index(['AwardData', 'AwardType', 'AwardDate']);
        });
    }

    public function down(): void
    {
        Schema::table('SiteAwards', function (Blueprint $table) {
            $table->dropIndex(['AwardData', 'AwardType', 'AwardDate']);
        });
    }
};
