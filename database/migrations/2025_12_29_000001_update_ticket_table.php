<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop the unique constraint that would block the data migration.
        // This unique constraint isn't desirable - a user may open another ticket
        // for the same achievement at some time in the future.
        Schema::table('Ticket', function (Blueprint $table) {
            $table->dropUnique('tickets_ticketable_reporter_id_index');
        });

        // Rename columns.
        Schema::table('Ticket', function (Blueprint $table) {
            $table->renameColumn('ID', 'id');
            $table->renameColumn('ticketable_model', 'ticketable_type');
            $table->renameColumn('ReportType', 'type');
            $table->renameColumn('ReportState', 'state');
            $table->renameColumn('ReportNotes', 'body');
            $table->renameColumn('ReportedAt', 'created_at');
            $table->renameColumn('ResolvedAt', 'resolved_at');
            $table->renameColumn('Updated', 'updated_at');
            $table->renameColumn('Hardcore', 'hardcore');
        });

        // Migrate AchievementID data to ticketable columns.
        DB::statement(<<<SQL
            UPDATE Ticket
            SET ticketable_type = 'achievement',
                ticketable_id = AchievementID
            WHERE AchievementID IS NOT NULL
              AND ticketable_id IS NULL
        SQL);

        // Drop indexes that reference AchievementID before dropping the column.
        Schema::table('Ticket', function (Blueprint $table) {
            $table->dropIndex('tickets_achievement_id_reporter_id_index');
            $table->dropIndex('ticket_achievementid_reportstate_deleted_at_index');
        });

        // Drop the AchievementID column.
        Schema::table('Ticket', function (Blueprint $table) {
            $table->dropColumn('AchievementID');
        });

        // Rename the table.
        Schema::rename('Ticket', 'tickets');

        // Rename indexes.
        Schema::table('tickets', function (Blueprint $table) {
            $table->renameIndex('tickets_created_at_index', 'tickets_created_at_index');
            $table->renameIndex('tickets_ticketable_index', 'tickets_ticketable_index');
        });

        // Replace the dropped (AchievementID, state, deleted_at) index with a morph-based equivalent.
        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['ticketable_type', 'ticketable_id', 'state', 'deleted_at'], 'tickets_ticketable_state_index');
        });

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // Convert type column - change to VARCHAR first, then update values.
        // type: 1 = 'triggered_at_wrong_time', 2 = 'did_not_trigger'
        // Delete any tickets with invalid type values (legacy data inconsistencies).
        DB::statement("ALTER TABLE tickets MODIFY type VARCHAR(30)");
        DB::table('tickets')->whereNotIn('type', ['1', '2'])->delete();
        DB::table('tickets')->where('type', '1')->update(['type' => 'triggered_at_wrong_time']);
        DB::table('tickets')->where('type', '2')->update(['type' => 'did_not_trigger']);

        // Convert state column - change to VARCHAR first, then update values.
        // state: 0 = 'closed', 1 = 'open', 2 = 'resolved', 3 = 'request'
        DB::statement("ALTER TABLE tickets MODIFY state VARCHAR(30)");
        DB::table('tickets')->where('state', '0')->update(['state' => 'closed']);
        DB::table('tickets')->where('state', '1')->update(['state' => 'open']);
        DB::table('tickets')->where('state', '2')->update(['state' => 'resolved']);
        DB::table('tickets')->where('state', '3')->update(['state' => 'request']);

        // Reorder columns into logical groups.
        DB::statement(<<<SQL
            ALTER TABLE tickets
                -- Primary key
                MODIFY COLUMN `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT FIRST,

                -- Ticketable morph
                MODIFY COLUMN `ticketable_type` varchar(255) DEFAULT NULL AFTER `id`,
                MODIFY COLUMN `ticketable_id` bigint(20) unsigned DEFAULT NULL AFTER `ticketable_type`,
                MODIFY COLUMN `ticketable_author_id` bigint(20) unsigned DEFAULT NULL AFTER `ticketable_id`,

                -- Ticket details
                MODIFY COLUMN `type` varchar(30) NOT NULL AFTER `ticketable_author_id`,
                MODIFY COLUMN `state` varchar(30) NOT NULL DEFAULT 'open' AFTER `type`,
                MODIFY COLUMN `body` text NOT NULL AFTER `state`,
                MODIFY COLUMN `hardcore` tinyint(1) DEFAULT NULL AFTER `body`,

                -- Ticket folks
                MODIFY COLUMN `reporter_id` bigint(20) unsigned DEFAULT NULL AFTER `hardcore`,
                MODIFY COLUMN `resolver_id` bigint(20) unsigned DEFAULT NULL AFTER `reporter_id`,

                -- Context (emulator/hash stuff)
                MODIFY COLUMN `game_hash_id` bigint(20) unsigned DEFAULT NULL AFTER `resolver_id`,
                MODIFY COLUMN `emulator_id` int(10) unsigned DEFAULT NULL AFTER `game_hash_id`,
                MODIFY COLUMN `emulator_version` varchar(32) DEFAULT NULL AFTER `emulator_id`,
                MODIFY COLUMN `emulator_core` varchar(96) DEFAULT NULL AFTER `emulator_version`,

                -- Timestamps
                MODIFY COLUMN `resolved_at` timestamp NULL DEFAULT NULL AFTER `emulator_core`,
                MODIFY COLUMN `created_at` timestamp NULL DEFAULT NULL AFTER `resolved_at`,
                MODIFY COLUMN `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
                MODIFY COLUMN `deleted_at` timestamp NULL DEFAULT NULL AFTER `updated_at`
        SQL);
    }

    public function down(): void
    {
        // Revert enum columns to integer values.
        DB::table('tickets')->where('type', 'triggered_at_wrong_time')->update(['type' => '1']);
        DB::table('tickets')->where('type', 'did_not_trigger')->update(['type' => '2']);
        DB::statement("ALTER TABLE tickets MODIFY type TINYINT(3) UNSIGNED NOT NULL");

        DB::table('tickets')->where('state', 'closed')->update(['state' => '0']);
        DB::table('tickets')->where('state', 'open')->update(['state' => '1']);
        DB::table('tickets')->where('state', 'resolved')->update(['state' => '2']);
        DB::table('tickets')->where('state', 'request')->update(['state' => '3']);
        DB::statement("ALTER TABLE tickets MODIFY state TINYINT(3) UNSIGNED NOT NULL DEFAULT 1");

        // Restore original column order.
        DB::statement(<<<SQL
            ALTER TABLE tickets
                MODIFY COLUMN `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT FIRST,
                MODIFY COLUMN `ticketable_type` varchar(255) DEFAULT NULL AFTER `id`,
                MODIFY COLUMN `ticketable_id` bigint(20) unsigned DEFAULT NULL AFTER `ticketable_type`,
                MODIFY COLUMN `ticketable_author_id` bigint(20) unsigned DEFAULT NULL AFTER `ticketable_id`,
                MODIFY COLUMN `game_hash_id` bigint(20) unsigned DEFAULT NULL AFTER `ticketable_author_id`,
                MODIFY COLUMN `emulator_id` int(10) unsigned DEFAULT NULL AFTER `game_hash_id`,
                MODIFY COLUMN `emulator_version` varchar(32) DEFAULT NULL AFTER `emulator_id`,
                MODIFY COLUMN `emulator_core` varchar(96) DEFAULT NULL AFTER `emulator_version`,
                MODIFY COLUMN `reporter_id` bigint(20) unsigned DEFAULT NULL AFTER `emulator_core`,
                MODIFY COLUMN `type` tinyint(3) unsigned NOT NULL AFTER `reporter_id`,
                MODIFY COLUMN `hardcore` tinyint(1) DEFAULT NULL AFTER `type`,
                MODIFY COLUMN `body` text NOT NULL AFTER `hardcore`,
                MODIFY COLUMN `created_at` timestamp NULL DEFAULT NULL AFTER `body`,
                MODIFY COLUMN `resolved_at` timestamp NULL DEFAULT NULL AFTER `created_at`,
                MODIFY COLUMN `resolver_id` bigint(20) unsigned DEFAULT NULL AFTER `resolved_at`,
                MODIFY COLUMN `state` tinyint(3) unsigned NOT NULL DEFAULT 1 AFTER `resolver_id`,
                MODIFY COLUMN `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `state`,
                MODIFY COLUMN `deleted_at` timestamp NULL DEFAULT NULL AFTER `updated_at`
        SQL);

        // Drop the ticketable state index.
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_ticketable_state_index');
        });

        // Rename indexes back.
        Schema::table('tickets', function (Blueprint $table) {
            $table->renameIndex('tickets_created_at_index', 'tickets_created_at_index');
            $table->renameIndex('tickets_ticketable_index', 'tickets_ticketable_index');
        });

        // Rename the table back.
        Schema::rename('tickets', 'Ticket');

        // Re-add the AchievementID column in original position.
        Schema::table('Ticket', function (Blueprint $table) {
            $table->unsignedBigInteger('AchievementID')->nullable()->after('ticketable_author_id');
        });

        // Migrate data back from ticketable columns to AchievementID.
        DB::statement(<<<SQL
            UPDATE Ticket
            SET AchievementID = ticketable_id
            WHERE ticketable_type = 'achievement'
        SQL);

        // Clear ticketable columns since we've restored AchievementID.
        DB::table('Ticket')->update(['ticketable_type' => null, 'ticketable_id' => null]);

        // Re-add the dropped indexes.
        Schema::table('Ticket', function (Blueprint $table) {
            $table->index(['AchievementID', 'reporter_id'], 'tickets_achievement_id_reporter_id_index');
            $table->index(['AchievementID', 'state', 'deleted_at'], 'ticket_achievementid_reportstate_deleted_at_index');
        });

        // Rename columns back.
        Schema::table('Ticket', function (Blueprint $table) {
            $table->renameColumn('id', 'ID');
            $table->renameColumn('ticketable_type', 'ticketable_model');
            $table->renameColumn('type', 'ReportType');
            $table->renameColumn('state', 'ReportState');
            $table->renameColumn('body', 'ReportNotes');
            $table->renameColumn('created_at', 'ReportedAt');
            $table->renameColumn('resolved_at', 'ResolvedAt');
            $table->renameColumn('updated_at', 'Updated');
            $table->renameColumn('hardcore', 'Hardcore');
        });

        // Re-add the unique constraint that was dropped in up().
        Schema::table('Ticket', function (Blueprint $table) {
            $table->unique(['ticketable_model', 'ticketable_id', 'reporter_id'], 'tickets_ticketable_reporter_id_index');
        });
    }
};
