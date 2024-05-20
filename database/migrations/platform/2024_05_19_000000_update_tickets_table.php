<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('Ticket', function (Blueprint $table) {
            $table->unsignedBigInteger('assignee_id')->nullable()->after('reporter_id');
            $table->foreign('assignee_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('Ticket', function (Blueprint $table) {
            $table->dropForeign(['assignee_id']);
        });

        Schema::table('Ticket', function (Blueprint $table) {
            $table->dropColumn('assignee_id');
        });
    }
};
