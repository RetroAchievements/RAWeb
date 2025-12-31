<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('SetClaim', function (Blueprint $table) {
            $table->renameColumn('ID', 'id');
            $table->renameColumn('ClaimType', 'claim_type');
            $table->renameColumn('SetType', 'set_type');
            $table->renameColumn('Status', 'status');
            $table->renameColumn('Extension', 'extensions_count');
            $table->renameColumn('Special', 'special_type');
            $table->renameColumn('Created', 'created_at');
            $table->renameColumn('Finished', 'finished_at');
            $table->renameColumn('Updated', 'updated_at');
        });

        Schema::rename('SetClaim', 'achievement_set_claims');

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // claim_type: 0 = 'primary', 1 = 'collaboration'
        DB::statement("ALTER TABLE achievement_set_claims MODIFY claim_type VARCHAR(20)");
        DB::table('achievement_set_claims')->where('claim_type', '0')->update(['claim_type' => 'primary']);
        DB::table('achievement_set_claims')->where('claim_type', '1')->update(['claim_type' => 'collaboration']);

        // set_type: 0 = 'new_set', 1 = 'revision'
        DB::statement("ALTER TABLE achievement_set_claims MODIFY set_type VARCHAR(20)");
        DB::table('achievement_set_claims')->where('set_type', '0')->update(['set_type' => 'new_set']);
        DB::table('achievement_set_claims')->where('set_type', '1')->update(['set_type' => 'revision']);

        // status: 0 = 'active', 1 = 'complete', 2 = 'dropped', 3 = 'in_review'
        DB::statement("ALTER TABLE achievement_set_claims MODIFY status VARCHAR(20)");
        DB::table('achievement_set_claims')->where('status', '0')->update(['status' => 'active']);
        DB::table('achievement_set_claims')->where('status', '1')->update(['status' => 'complete']);
        DB::table('achievement_set_claims')->where('status', '2')->update(['status' => 'dropped']);
        DB::table('achievement_set_claims')->where('status', '3')->update(['status' => 'in_review']);

        // special_type: 0 = 'none', 1 = 'own_revision', 2 = 'free_rollout', 3 = 'scheduled_release'
        DB::statement("ALTER TABLE achievement_set_claims MODIFY special_type VARCHAR(20)");
        DB::table('achievement_set_claims')->where('special_type', '0')->update(['special_type' => 'none']);
        DB::table('achievement_set_claims')->where('special_type', '1')->update(['special_type' => 'own_revision']);
        DB::table('achievement_set_claims')->where('special_type', '2')->update(['special_type' => 'free_rollout']);
        DB::table('achievement_set_claims')->where('special_type', '3')->update(['special_type' => 'scheduled_release']);
    }

    public function down(): void
    {
        DB::table('achievement_set_claims')->where('claim_type', 'primary')->update(['claim_type' => '0']);
        DB::table('achievement_set_claims')->where('claim_type', 'collaboration')->update(['claim_type' => '1']);
        DB::statement("ALTER TABLE achievement_set_claims MODIFY claim_type TINYINT UNSIGNED");

        DB::table('achievement_set_claims')->where('set_type', 'new_set')->update(['set_type' => '0']);
        DB::table('achievement_set_claims')->where('set_type', 'revision')->update(['set_type' => '1']);
        DB::statement("ALTER TABLE achievement_set_claims MODIFY set_type TINYINT UNSIGNED");

        DB::table('achievement_set_claims')->where('status', 'active')->update(['status' => '0']);
        DB::table('achievement_set_claims')->where('status', 'complete')->update(['status' => '1']);
        DB::table('achievement_set_claims')->where('status', 'dropped')->update(['status' => '2']);
        DB::table('achievement_set_claims')->where('status', 'in_review')->update(['status' => '3']);
        DB::statement("ALTER TABLE achievement_set_claims MODIFY status TINYINT UNSIGNED");

        DB::table('achievement_set_claims')->where('special_type', 'none')->update(['special_type' => '0']);
        DB::table('achievement_set_claims')->where('special_type', 'own_revision')->update(['special_type' => '1']);
        DB::table('achievement_set_claims')->where('special_type', 'free_rollout')->update(['special_type' => '2']);
        DB::table('achievement_set_claims')->where('special_type', 'scheduled_release')->update(['special_type' => '3']);
        DB::statement("ALTER TABLE achievement_set_claims MODIFY special_type TINYINT UNSIGNED");

        Schema::rename('achievement_set_claims', 'SetClaim');

        Schema::table('SetClaim', function (Blueprint $table) {
            $table->renameColumn('id', 'ID');
            $table->renameColumn('claim_type', 'ClaimType');
            $table->renameColumn('set_type', 'SetType');
            $table->renameColumn('status', 'Status');
            $table->renameColumn('extensions_count', 'Extension');
            $table->renameColumn('special_type', 'Special');
            $table->renameColumn('created_at', 'Created');
            $table->renameColumn('finished_at', 'Finished');
            $table->renameColumn('updated_at', 'Updated');
        });
    }
};
