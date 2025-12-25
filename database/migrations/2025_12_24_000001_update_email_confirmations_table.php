<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('EmailConfirmations', function (Blueprint $table) {
            $table->dropColumn('User');
            $table->renameColumn('EmailCookie', 'email_cookie');
        });

        Schema::table('EmailConfirmations', function (Blueprint $table) {
            $table->dateTime('expires_at')->nullable()->after('email_cookie');
        });

        DB::statement('UPDATE EmailConfirmations SET expires_at = Expires');

        Schema::table('EmailConfirmations', function (Blueprint $table) {
            $table->dropColumn('Expires');
        });

        Schema::table('EmailConfirmations', function (Blueprint $table) {
            $table->dateTime('expires_at')->nullable(false)->change();
        });

        Schema::rename('EmailConfirmations', 'email_confirmations');
    }

    public function down(): void
    {
        Schema::rename('email_confirmations', 'EmailConfirmations');

        Schema::table('EmailConfirmations', function (Blueprint $table) {
            $table->date('Expires')->nullable()->after('email_cookie');
        });

        DB::statement('UPDATE EmailConfirmations SET Expires = DATE(expires_at)');

        Schema::table('EmailConfirmations', function (Blueprint $table) {
            $table->date('Expires')->nullable(false)->change();
        });

        Schema::table('EmailConfirmations', function (Blueprint $table) {
            $table->dropColumn('expires_at');
            $table->renameColumn('email_cookie', 'EmailCookie');
            $table->string('User', 20)->after('id');
        });
    }
};
