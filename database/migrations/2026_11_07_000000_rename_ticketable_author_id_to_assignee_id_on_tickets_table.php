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
            $table->renameColumn('ticketable_author_id', 'assignee_id');
        });

        // Assign every unresolved achievement ticket to the achievement's active maintainer or author.
        DB::table('tickets')
            ->where('ticketable_type', 'achievement')
            ->whereIn('state', ['open', 'request', 'quarantined'])
            ->update([
                'assignee_id' => DB::raw('COALESCE(
                    (SELECT m.user_id FROM achievement_maintainers m WHERE m.achievement_id = tickets.ticketable_id AND m.is_active = 1 LIMIT 1),
                    (SELECT a.user_id FROM achievements a WHERE a.id = tickets.ticketable_id),
                    tickets.assignee_id
                )'),
            ]);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->renameColumn('assignee_id', 'ticketable_author_id');
        });
    }
};
