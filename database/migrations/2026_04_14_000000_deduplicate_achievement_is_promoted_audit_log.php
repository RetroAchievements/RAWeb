<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('audit_log')
            ->where('subject_type', 'achievement')
            ->where('event', 'updated')
            ->where('properties', 'like', '%is_promoted%')
            ->whereIn('id', function ($query) {
                $query->select('max_id')
                    ->fromSub(
                        DB::table('audit_log')
                            ->selectRaw('MAX(id) as max_id')
                            ->where('subject_type', 'achievement')
                            ->where('event', 'updated')
                            ->where('properties', 'like', '%is_promoted%')
                            ->groupBy('subject_id', 'causer_id', 'created_at')
                            ->havingRaw('COUNT(*) > 1'),
                        'dupes'
                    );
            })
            ->delete();
    }

    public function down(): void
    {
        // irreversible
    }
};
