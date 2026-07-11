<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The V2 users index defaults to ordering by points_hardcore desc with a
 * ulid asc pagination tiebreaker (the schema's JSON:API id column). This
 * filesorts the entire table without an index matching both directions.
 */

return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // equivalent to CONCURRENTLY in postgres
        DB::statement('ALTER TABLE users ADD INDEX users_points_hardcore_ulid_index (points_hardcore DESC, ulid ASC), ALGORITHM=INPLACE, LOCK=NONE');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP INDEX users_points_hardcore_ulid_index, ALGORITHM=INPLACE, LOCK=NONE');
    }
};
