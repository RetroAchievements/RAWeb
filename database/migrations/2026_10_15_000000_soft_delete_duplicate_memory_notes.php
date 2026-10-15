<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cleans up a data issue where merging a subset note would insert a second row next to
 * an already soft deleted note. We'll hang on to the most recently updated note at each
 * address and soft delete the rest.
 */
return new class extends Migration {
    public function up(): void
    {
        $duplicateAddresses = DB::table('memory_notes')
            ->whereNull('deleted_at')
            ->whereNotNull('game_id')
            ->select(['game_id', 'address'])
            ->groupBy('game_id', 'address')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateAddresses as $duplicateAddress) {
            $noteIds = DB::table('memory_notes')
                ->whereNull('deleted_at')
                ->where('game_id', $duplicateAddress->game_id)
                ->where('address', $duplicateAddress->address)
                ->orderByDesc('updated_at')
                ->orderBy('id')
                ->pluck('id');

            DB::table('memory_notes')
                ->whereIn('id', $noteIds->skip(1))
                ->update(['deleted_at' => now()]);
        }
    }

    public function down(): void
    {
        // irreversible
    }
};
