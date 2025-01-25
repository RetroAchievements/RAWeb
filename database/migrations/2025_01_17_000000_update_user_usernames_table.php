<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('user_usernames', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'username']);

            $table->foreign('user_id', 'user_usernames_user_id_foreign')
                ->references('ID')
                ->on('UserAccounts')
                ->onDelete('set null');

            $table->timestamp('approved_at')->nullable()->after('username');
            $table->timestamp('denied_at')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_usernames', function (Blueprint $table) {
            $table->dropColumn(['approved_at', 'denied_at']);

            $table->dropForeign('user_usernames_user_id_foreign');

            $table->unique(['user_id', 'username']);

            $table->foreign('user_id', 'user_usernames_user_id_foreign')
                ->references('ID')
                ->on('UserAccounts')
                ->onDelete('SET NULL');
        });
    }
};
