<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Add a virtual generated column so we can index the generated
            // state rank instead of sorting by a CASE expression.
            $table->unsignedTinyInteger('state_sort_order')->virtualAs(
                "CASE state WHEN 'open' THEN 0 WHEN 'request' THEN 1 WHEN 'quarantined' THEN 2 WHEN 'resolved' THEN 3 WHEN 'closed' THEN 4 ELSE 5 END",
            );

            // Both directions keep newest tickets first. They need separate indexes.
            $table->index(['deleted_at', 'state_sort_order', DB::raw('created_at DESC'), DB::raw('id DESC')], 'tickets_state_sort_index');
            $table->index(['deleted_at', 'state_sort_order', 'created_at', 'id'], 'tickets_state_sort_reverse_index');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_state_sort_reverse_index');
            $table->dropIndex('tickets_state_sort_index');

            $table->dropColumn('state_sort_order');
        });
    }
};
