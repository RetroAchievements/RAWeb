<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\PlayerAchievement;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Some hashes, such as translations, have very few players. Many unlocks before
 * the report can hide that nobody actually earned the achievement on the hash that
 * was used by a ticket reporter. This may point to a hash being problematic rather
 * than the triggerable logic.
 *
 * Important: multi-disc hashes are skipped. A play session records the disc it started
 * on, and may have no hash at all. Multi-disc games may change hashes mid-session and
 * we don't necessarily know about that change.
 */
class FindUnlocksOnlyOnOtherHashesAction
{
    /**
     * Each unlock costs a read of its row and of its session. Popular achievements
     * take seconds to count in full. The most recent unlocks are enough to show
     * whether anyone earned it on the reporter's given hash.
     */
    private const RECENT_UNLOCK_LIMIT = 100;

    /**
     * We need a large enough sample size.
     */
    private const MIN_OTHER_HASH_UNLOCKS = 5;

    /**
     * @param Collection<int, Ticket> $tickets achievement tickets
     * @param array<int, Carbon> $windowStartByTicketId
     * @return array<int, int> the recent unlock count keyed by ticket id, only for tickets whose recent unlocks were all on other hashes
     */
    public function execute(Collection $tickets, array $windowStartByTicketId): array
    {
        $tickets->loadMissing('gameHash');
        $ticketsWithHash = $tickets->filter(
            fn (Ticket $ticket) => $ticket->gameHash !== null && !$ticket->gameHash->isMultiDiscGameHash(),
        );
        if ($ticketsWithHash->isEmpty()) {
            return [];
        }

        $perTicketQueries = $ticketsWithHash->map(function (Ticket $ticket) use ($windowStartByTicketId) {
            $recentUnlockHashes = PlayerAchievement::query()
                ->join('player_sessions', 'player_sessions.id', '=', 'player_achievements.player_session_id')
                ->where('player_achievements.achievement_id', $ticket->ticketable_id)
                ->whereBetween('player_achievements.unlocked_effective_at', [
                    $windowStartByTicketId[$ticket->id],
                    Carbon::parse($ticket->created_at),
                ])
                ->whereNotNull('player_sessions.game_hash_id')
                ->when($ticket->reporter_id, fn ($query, int $reporterId) => $query->where('player_achievements.user_id', '!=', $reporterId))
                ->orderByDesc('player_achievements.unlocked_effective_at')
                ->limit(self::RECENT_UNLOCK_LIMIT)
                ->select('player_sessions.game_hash_id')
                ->toBase();

            return DB::query()
                ->fromSub($recentUnlockHashes, 'recent_unlock_hashes')
                ->selectRaw(
                    '? as ticket_id, COUNT(*) as unlock_count, SUM(CASE WHEN game_hash_id = ? THEN 1 ELSE 0 END) as on_hash_count',
                    [$ticket->id, $ticket->game_hash_id],
                );
        });

        $unionQuery = $perTicketQueries->first();
        $perTicketQueries->skip(1)->each(fn ($perTicketQuery) => $unionQuery->unionAll($perTicketQuery));

        $unlockCountByTicketId = [];
        foreach ($unionQuery->get() as $countRow) {
            if ((int) $countRow->on_hash_count === 0 && (int) $countRow->unlock_count >= self::MIN_OTHER_HASH_UNLOCKS) {
                $unlockCountByTicketId[(int) $countRow->ticket_id] = (int) $countRow->unlock_count;
            }
        }

        return $unlockCountByTicketId;
    }
}
