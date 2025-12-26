<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('SiteAwards', function (Blueprint $table) {
            $table->renameColumn('AwardDate', 'awarded_at');
            $table->renameColumn('AwardType', 'award_type');
            $table->renameColumn('AwardData', 'award_data');
            $table->renameColumn('AwardDataExtra', 'award_data_extra');
            $table->renameColumn('DisplayOrder', 'order_column');
        });

        Schema::rename('SiteAwards', 'user_awards');

        Schema::table('user_awards', function (Blueprint $table) {
            $table->renameIndex('siteawards_awardtype_index', 'user_awards_award_type_index');
            $table->renameIndex('siteawards_awarddata_awardtype_awarddate_index', 'user_awards_award_data_award_type_awarded_at_index');
            $table->renameIndex('siteawards_user_id_index', 'user_awards_user_id_index');
            $table->renameIndex('siteawards_user_id_awarddata_awardtype_awarddataextra_index', 'user_awards_user_id_award_data_award_type_award_data_extra_index');
        });

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // Delete orphaned awards with deprecated types (there's a few thousand of these in prod).
        DB::table('user_awards')->whereNotIn('award_type', ['1', '2', '3', '6', '7', '8', '9'])->delete();

        // award_type: Convert integer values to string enum values.
        DB::statement("ALTER TABLE user_awards MODIFY award_type VARCHAR(30)");
        DB::table('user_awards')->where('award_type', '1')->update(['award_type' => 'mastery']);
        DB::table('user_awards')->where('award_type', '2')->update(['award_type' => 'achievement_unlocks_yield']);
        DB::table('user_awards')->where('award_type', '3')->update(['award_type' => 'achievement_points_yield']);
        DB::table('user_awards')->where('award_type', '6')->update(['award_type' => 'patreon_supporter']);
        DB::table('user_awards')->where('award_type', '7')->update(['award_type' => 'certified_legend']);
        DB::table('user_awards')->where('award_type', '8')->update(['award_type' => 'game_beaten']);
        DB::table('user_awards')->where('award_type', '9')->update(['award_type' => 'event']);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::table('user_awards')->where('award_type', 'mastery')->update(['award_type' => '1']);
            DB::table('user_awards')->where('award_type', 'achievement_unlocks_yield')->update(['award_type' => '2']);
            DB::table('user_awards')->where('award_type', 'achievement_points_yield')->update(['award_type' => '3']);
            DB::table('user_awards')->where('award_type', 'patreon_supporter')->update(['award_type' => '6']);
            DB::table('user_awards')->where('award_type', 'certified_legend')->update(['award_type' => '7']);
            DB::table('user_awards')->where('award_type', 'game_beaten')->update(['award_type' => '8']);
            DB::table('user_awards')->where('award_type', 'event')->update(['award_type' => '9']);
            DB::statement("ALTER TABLE user_awards MODIFY award_type INT");
        }

        Schema::table('user_awards', function (Blueprint $table) {
            $table->renameIndex('user_awards_award_type_index', 'siteawards_awardtype_index');
            $table->renameIndex('user_awards_award_data_award_type_awarded_at_index', 'siteawards_awarddata_awardtype_awarddate_index');
            $table->renameIndex('user_awards_user_id_index', 'siteawards_user_id_index');
            $table->renameIndex('user_awards_user_id_award_data_award_type_award_data_extra_index', 'siteawards_user_id_awarddata_awardtype_awarddataextra_index');
        });

        Schema::rename('user_awards', 'SiteAwards');

        Schema::table('SiteAwards', function (Blueprint $table) {
            $table->renameColumn('awarded_at', 'AwardDate');
            $table->renameColumn('award_type', 'AwardType');
            $table->renameColumn('award_data', 'AwardData');
            $table->renameColumn('award_data_extra', 'AwardDataExtra');
            $table->renameColumn('order_column', 'DisplayOrder');
        });
    }
};
