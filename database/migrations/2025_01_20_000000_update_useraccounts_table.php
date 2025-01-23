<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('UserAccounts', function (Blueprint $table) {
            $table->unsignedBigInteger('visible_role_id')->nullable()->after('display_name');

            $table->foreign('visible_role_id')
                ->references('id')
                ->on('auth_roles')
                ->onDelete('set null');

            $table->index('visible_role_id');
        });
    }

    public function down(): void
    {
        Schema::table('UserAccounts', function (Blueprint $table) {
            $table->dropForeign(['visible_role_id']);
            $table->dropIndex(['visible_role_id']);
            $table->dropColumn('visible_role_id');
        });
    }
};
