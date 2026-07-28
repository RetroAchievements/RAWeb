<?php

declare(strict_types=1);

use App\Community\Enums\AwardType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $awardType = AwardType::MediaContribution->value;

        $userIds = DB::table('user_awards')
            ->where('award_type', $awardType)
            ->select('user_id')
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            $this->consolidateForUser((int) $userId, $awardType);
        }
    }

    private function consolidateForUser(int $userId, string $awardType): void
    {
        $rows = DB::table('user_awards')
            ->where('user_id', $userId)
            ->where('award_type', $awardType)
            ->orderByDesc('award_key')
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $survivor = $rows->first();
        $highestTier = (int) $survivor->award_key;

        $visibleRows = $rows->filter(fn ($row) => (int) $row->order_column !== -1);
        $highestVisibleRow = $visibleRows->first();
        $highestVisibleTier = $highestVisibleRow ? (int) $highestVisibleRow->award_key : null;

        if ($highestVisibleTier !== null && $highestVisibleTier < $highestTier) {
            $displayAwardTier = $highestVisibleTier;
            $orderColumn = (int) $highestVisibleRow->order_column;
        } else {
            $displayAwardTier = null;
            $orderColumn = $highestVisibleRow ? (int) $highestVisibleRow->order_column : -1;
        }

        DB::table('user_awards')
            ->where('id', $survivor->id)
            ->update([
                'award_key' => $highestTier,
                'award_tier' => $highestTier,
                'display_award_tier' => $displayAwardTier,
                'order_column' => $orderColumn,
            ]);

        $idsToDelete = $rows->where('id', '!=', $survivor->id)->pluck('id')->all();
        if ($idsToDelete) {
            DB::table('user_awards')->whereIn('id', $idsToDelete)->delete();
        }
    }

    public function down(): void
    {
        // not reversible
    }
};
