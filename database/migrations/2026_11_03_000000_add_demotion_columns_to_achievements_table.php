<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->timestamp('demoted_at')->nullable()->after('is_promoted');

            /**
             * True only when the demotion happened at least 7 days after publish.
             * @see Achievement::MIN_PUBLISHED_DAYS_TO_KEEP_POINTS
             */
            $table->boolean('should_keep_demoted_points')->default(false)->after('demoted_at');

            $table->index('should_keep_demoted_points');
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->backfillDemotedAtFromAuditLog();
            $this->backfillRetainedPointsFlag();
        }
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropIndex(['should_keep_demoted_points']);
            $table->dropColumn(['demoted_at', 'should_keep_demoted_points']);
        });
    }

    private function backfillDemotedAtFromAuditLog(): void
    {
        DB::statement(<<<'SQL'
            UPDATE achievements ach
            INNER JOIN (
                SELECT subject_id, MAX(created_at) AS last_demotion_at
                FROM audit_log
                WHERE subject_type = 'achievement'
                    AND event = 'updated'
                    AND (
                        JSON_EXTRACT(properties, '$.attributes.is_promoted') = 'false'
                        OR JSON_EXTRACT(properties, '$.attributes.Flags') = 5
                    )
                GROUP BY subject_id
            ) demotion_events ON demotion_events.subject_id = ach.id
            SET ach.demoted_at = demotion_events.last_demotion_at
            WHERE ach.is_promoted = 0
        SQL);
    }

    private function backfillRetainedPointsFlag(): void
    {
        DB::statement(<<<'SQL'
            UPDATE achievements ach
            SET ach.should_keep_demoted_points = 1
            WHERE ach.is_promoted = 0
                AND ach.demoted_at >= '2026-01-01 00:00:00'
                AND NOT EXISTS (
                    SELECT 1
                    FROM audit_log promotion_event
                    WHERE promotion_event.subject_type = 'achievement'
                        AND promotion_event.subject_id = ach.id
                        AND promotion_event.created_at <= ach.demoted_at
                        AND promotion_event.created_at > ach.demoted_at - INTERVAL 7 DAY
                        AND (
                            JSON_EXTRACT(promotion_event.properties, '$.attributes.is_promoted') = 'true'
                            OR JSON_EXTRACT(promotion_event.properties, '$.attributes.Flags') = 3
                        )
                )
        SQL);
    }
};
