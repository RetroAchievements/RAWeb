<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cleans up a data issue where merging a subset note would insert a second row next to
 * an already soft deleted note.
 */
return new class extends Migration {
    public function up(): void
    {
        $duplicateAddresses = DB::table('memory_notes')
            ->whereNotNull('game_id')
            ->select(['game_id', 'address'])
            ->groupBy('game_id', 'address')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateAddresses as $duplicateAddress) {
            $noteIds = DB::table('memory_notes')
                ->where('game_id', $duplicateAddress->game_id)
                ->where('address', $duplicateAddress->address)
                ->orderByRaw('deleted_at IS NULL DESC')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->pluck('id');

            DB::table('memory_notes')
                ->whereIn('id', $noteIds->skip(1))
                ->delete();
        }
    }

    public function down(): void
    {
        // irreversible
    }
};
