<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('Ticket', function (Blueprint $table) {
            $table->unsignedBigInteger('ticketable_author_id')->nullable()->after('ticketable_id');
            $table->foreign('ticketable_author_id')->references('ID')->on('UserAccounts');
        });
    }

    public function down(): void
    {
        Schema::table('Ticket', function (Blueprint $table) {
            $table->dropForeign(['ticketable_author_id']);
        });

        Schema::table('Ticket', function (Blueprint $table) {
            $table->dropColumn('ticketable_author_id');
        });
    }
};
