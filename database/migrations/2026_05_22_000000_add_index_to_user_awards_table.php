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

        // equivalent to CONCURRENTLY in postgres
        DB::statement('ALTER TABLE user_awards ADD INDEX user_awards_awarded_at_award_type_index (awarded_at, award_type), ALGORITHM=INPLACE, LOCK=NONE');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE user_awards DROP INDEX user_awards_awarded_at_award_type_index, ALGORITHM=INPLACE, LOCK=NONE');
    }
};
