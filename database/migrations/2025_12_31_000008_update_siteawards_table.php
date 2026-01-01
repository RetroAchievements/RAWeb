<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite: Use Laravel's schema builder for compatibility.
            Schema::table('SiteAwards', function (Blueprint $table) {
                $table->renameColumn('AwardDate', 'awarded_at');
                $table->renameColumn('AwardType', 'award_type');
                $table->renameColumn('AwardData', 'award_key');
                $table->renameColumn('AwardDataExtra', 'award_tier');
                $table->renameColumn('DisplayOrder', 'order_column');
            });

            Schema::rename('SiteAwards', 'user_awards');

            return;
        }

        // Delete orphaned awards first while table has original structure.
        DB::table('SiteAwards')->whereNotIn('AwardType', [1, 2, 3, 6, 7, 8, 9])->delete();

        // Combine column renames, type change, and table rename into single ALTER.
        // Drop indexes containing award_type to speed up the UPDATE.
        DB::statement(<<<SQL
            ALTER TABLE SiteAwards
                CHANGE COLUMN AwardDate awarded_at DATETIME NOT NULL,
                CHANGE COLUMN AwardType award_type VARCHAR(30) DEFAULT NULL,
                CHANGE COLUMN AwardData award_key INT DEFAULT NULL,
                CHANGE COLUMN AwardDataExtra award_tier INT NOT NULL DEFAULT 0,
                CHANGE COLUMN DisplayOrder order_column SMALLINT NOT NULL DEFAULT 0 COMMENT 'Display order to show site awards in',
                RENAME INDEX siteawards_user_id_index TO user_awards_user_id_index,
                DROP INDEX siteawards_awardtype_index,
                DROP INDEX siteawards_awarddata_awardtype_awarddate_index,
                DROP INDEX siteawards_user_id_awarddata_awardtype_awarddataextra_index,
                RENAME TO user_awards
        SQL);

        // Convert integer values to string enum values.
        // Without indexes on award_type, this is faster.
        DB::statement(<<<SQL
            UPDATE user_awards
            SET award_type = CASE award_type
                WHEN '1' THEN 'mastery'
                WHEN '2' THEN 'achievement_unlocks_yield'
                WHEN '3' THEN 'achievement_points_yield'
                WHEN '6' THEN 'patreon_supporter'
                WHEN '7' THEN 'certified_legend'
                WHEN '8' THEN 'game_beaten'
                WHEN '9' THEN 'event'
            END
            WHERE award_type IN ('1', '2', '3', '6', '7', '8', '9')
        SQL);

        // Recreate indexes with new names after the data transformation.
        DB::statement("ALTER TABLE user_awards ADD INDEX user_awards_award_type_index (award_type)");
        DB::statement("ALTER TABLE user_awards ADD INDEX user_awards_award_key_award_type_awarded_at_index (award_key, award_type, awarded_at)");
        DB::statement("ALTER TABLE user_awards ADD INDEX user_awards_user_id_award_key_award_type_award_tier_index (user_id, award_key, award_type, award_tier)");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('user_awards', function (Blueprint $table) {
                $table->renameColumn('awarded_at', 'AwardDate');
                $table->renameColumn('award_type', 'AwardType');
                $table->renameColumn('award_key', 'AwardData');
                $table->renameColumn('award_tier', 'AwardDataExtra');
                $table->renameColumn('order_column', 'DisplayOrder');
            });

            Schema::rename('user_awards', 'SiteAwards');

            return;
        }

        // Drop indexes containing award_type to speed up the UPDATE.
        DB::statement("ALTER TABLE user_awards DROP INDEX user_awards_award_type_index");
        DB::statement("ALTER TABLE user_awards DROP INDEX user_awards_award_key_award_type_awarded_at_index");
        DB::statement("ALTER TABLE user_awards DROP INDEX user_awards_user_id_award_key_award_type_award_tier_index");

        // Convert string enum values back to integers.
        DB::statement(<<<SQL
            UPDATE user_awards
            SET award_type = CASE award_type
                WHEN 'mastery' THEN '1'
                WHEN 'achievement_unlocks_yield' THEN '2'
                WHEN 'achievement_points_yield' THEN '3'
                WHEN 'patreon_supporter' THEN '6'
                WHEN 'certified_legend' THEN '7'
                WHEN 'game_beaten' THEN '8'
                WHEN 'event' THEN '9'
            END
            WHERE award_type IN ('mastery', 'achievement_unlocks_yield', 'achievement_points_yield', 'patreon_supporter', 'certified_legend', 'game_beaten', 'event')
        SQL);

        // Combine column reversions, index recreation, and table rename into single ALTER.
        DB::statement(<<<SQL
            ALTER TABLE user_awards
                CHANGE COLUMN awarded_at AwardDate DATETIME NOT NULL,
                CHANGE COLUMN award_type AwardType INT DEFAULT NULL,
                CHANGE COLUMN award_key AwardData INT DEFAULT NULL,
                CHANGE COLUMN award_tier AwardDataExtra INT NOT NULL DEFAULT 0,
                CHANGE COLUMN order_column DisplayOrder SMALLINT NOT NULL DEFAULT 0 COMMENT 'Display order to show site awards in',
                RENAME INDEX user_awards_user_id_index TO siteawards_user_id_index,
                ADD INDEX siteawards_awardtype_index (AwardType),
                ADD INDEX siteawards_awarddata_awardtype_awarddate_index (AwardData, AwardType, AwardDate),
                ADD INDEX siteawards_user_id_awarddata_awardtype_awarddataextra_index (user_id, AwardData, AwardType, AwardDataExtra),
                RENAME TO SiteAwards
        SQL);
    }
};
