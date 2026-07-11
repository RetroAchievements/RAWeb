<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // Lets rank calculations (count of better scores on a leaderboard) resolve
        // entirely from the index instead of scanning every entry row per leaderboard.
        DB::statement('ALTER TABLE leaderboard_entries ADD INDEX leaderboard_entries_leaderboard_id_deleted_at_score_index (leaderboard_id, deleted_at, score), ALGORITHM=INPLACE, LOCK=NONE');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE leaderboard_entries DROP INDEX leaderboard_entries_leaderboard_id_deleted_at_score_index, ALGORITHM=INPLACE, LOCK=NONE');
    }
};
