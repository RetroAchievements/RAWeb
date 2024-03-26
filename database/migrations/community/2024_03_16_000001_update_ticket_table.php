<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('Ticket', function (Blueprint $table) {
            $table->renameColumn('ReportedByUserID', 'reporter_id');
        });
        Schema::table('Ticket', function (Blueprint $table) {
            $table->renameColumn('ResolvedByUserID', 'resolver_id');
        });

        Schema::table('Ticket', function (Blueprint $table) {
            $table->foreign('reporter_id')->references('ID')->on('UserAccounts')->onDelete('set null');
            $table->foreign('resolver_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('Ticket', function (Blueprint $table) {
            $table->dropForeign(['reporter_id']);
            $table->dropForeign(['resolver_id']);
        });

        Schema::table('Ticket', function (Blueprint $table) {
            $table->renameColumn('reporter_id', 'ReportedByUserID');
        });
        Schema::table('Ticket', function (Blueprint $table) {
            $table->renameColumn('resolver_id', 'ResolvedByUserID');
        });
    }
};
