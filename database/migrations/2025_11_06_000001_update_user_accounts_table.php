<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('UserAccounts', function (Blueprint $table) {
            $table->dropIndex('users_last_activity_id_index');

            $table->dropColumn([
                'PasswordResetToken',
                'fbUser',
                'fbPrefs',
                'cookie',
                'LastActivityID',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('UserAccounts', function (Blueprint $table) {
            $table->string('PasswordResetToken', 32)->nullable()->after('UserWallActive');
            $table->bigInteger('fbUser')->after('RASoftcorePoints');
            $table->smallInteger('fbPrefs')->after('fbUser');
            $table->string('cookie', 100)->nullable()->after('fbPrefs');
            $table->integer('LastActivityID')->after('LastLogin');

            $table->index('LastActivityID', 'users_last_activity_id_index');
        });
    }
};
