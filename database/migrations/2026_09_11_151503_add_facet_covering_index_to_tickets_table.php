<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->index([
                'deleted_at',
                'state',
                'type',
                'hardcore',
                'emulator_id',
                'ticketable_type',
                'ticketable_id',
                'ticketable_author_id',
            ], 'tickets_facet_counts_index');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_facet_counts_index');
        });
    }
};
