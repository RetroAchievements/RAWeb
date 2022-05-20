<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('Messages', function (Blueprint $table) {
            $table->bigIncrements('ID')->change();

            $table->unsignedBigInteger('recipient_id')->nullable()->after('ID');
            $table->unsignedBigInteger('sender_id')->nullable()->after('recipient_id');

            $table->timestampTz('read_at')->nullable()->after('TimeSent');

            // should be dropped
            $table->boolean('Unread')->default(1)->change();
            $table->unsignedInteger('Type')->nullable()->change();

            $table->timestamp('recipient_deleted_at')->nullable();
            $table->timestamp('sender_deleted_at')->nullable();

            $table->softDeletesTz();

            $table->index('read_at');

            $table->foreign('recipient_id', 'messages_recipient_id_foreign')->references('ID')->on('UserAccounts')->onDelete('set null');
            $table->foreign('sender_id', 'messages_sender_id_foreign')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('Messages', function (Blueprint $table) {
            $table->dropForeign('messages_recipient_id_foreign');
            $table->dropForeign('messages_sender_id_foreign');
            $table->dropColumn('recipient_id');
            $table->dropColumn('sender_id');
            $table->dropColumn('read_at');
            $table->dropColumn('recipient_deleted_at');
            $table->dropColumn('sender_deleted_at');
            $table->dropSoftDeletesTz();
        });
    }
};
