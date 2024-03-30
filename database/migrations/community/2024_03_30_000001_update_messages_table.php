<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('UserTo');
        });
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('UserFrom');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('UserTo', 32)->after('author_id')->index();
            $table->string('UserFrom', 32)->after('UserTo');
        });
    }
};
