<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('SiteAwards', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->bigIncrements('id')->first();
            }
        });
    }

    public function down(): void
    {
        Schema::table('SiteAwards', function (Blueprint $table) {
            $table->dropColumn('id');
        });
    }
};
