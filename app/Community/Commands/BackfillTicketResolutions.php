<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Community\Enums\CommentableType;
use App\Community\Enums\TicketResolution;
use App\Community\Enums\TicketState;
use App\Models\Comment;
use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BackfillTicketResolutions extends Command
{
    protected $signature = 'ra:community:ticket:backfill-resolutions';
    protected $description = 'Backfills the resolution column for resolved/closed tickets from their state and automated closing comments';

    private const CHUNK_SIZE = 1000;

    private int $resolvedCount = 0;

    /** @var array<string, int> keyed by resolution value */
    private array $closedCounts = [];

    /** @var list<int> */
    private array $unmatchedIds = [];

    public function handle(): void
    {
        $this->backfillResolvedTickets();
        $this->backfillClosedTickets();

        $this->info(sprintf('Resolved tickets: %d changed.', $this->resolvedCount));
        $this->info(sprintf('Closed tickets: %d changed.', array_sum($this->closedCounts)));
        foreach (TicketResolution::cases() as $resolution) {
            if (isset($this->closedCounts[$resolution->value])) {
                $this->line(sprintf('  %s: %d changed.', $resolution->value, $this->closedCounts[$resolution->value]));
            }
        }

        $this->info(sprintf('Unmatched closed tickets: %d.', count($this->unmatchedIds)));
        if ($this->unmatchedIds) {
            $this->line('  ' . implode(', ', $this->unmatchedIds));
        }
    }

    private function backfillResolvedTickets(): void
    {
        $this->pendingTicketsQuery(TicketState::Resolved)
            ->chunkById(self::CHUNK_SIZE, function (Collection $tickets): void {
                $this->resolvedCount += $this->writeResolution($tickets->modelKeys(), TicketResolution::Fixed);
            });
    }

    private function backfillClosedTickets(): void
    {
        $this->pendingTicketsQuery(TicketState::Closed)
            ->chunkById(self::CHUNK_SIZE, function (Collection $tickets): void {
                $ids = $tickets->modelKeys();
                $resolutionsByTicketId = $this->findResolutionsFromCloseComments($ids);

                foreach ($ids as $id) {
                    if (($resolutionsByTicketId[$id] ?? null) === null) {
                        $this->unmatchedIds[] = $id;
                    }
                }

                foreach (TicketResolution::cases() as $resolution) {
                    $resolutionIds = array_keys(array_filter(
                        $resolutionsByTicketId,
                        fn (?TicketResolution $found) => $found === $resolution,
                    ));

                    if ($resolutionIds) {
                        $changed = $this->writeResolution($resolutionIds, $resolution);
                        $this->closedCounts[$resolution->value] = ($this->closedCounts[$resolution->value] ?? 0) + $changed;
                    }
                }
            });
    }

    /**
     * @param list<int> $ticketIds
     * @return array<int, TicketResolution|null>
     */
    private function findResolutionsFromCloseComments(array $ticketIds): array
    {
        $comments = Comment::query()
            ->where('commentable_type', CommentableType::AchievementTicket)
            ->whereIn('commentable_id', $ticketIds)
            ->where('user_id', Comment::SYSTEM_USER_ID)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'commentable_id', 'body']);

        $resolutionsByTicketId = [];
        foreach ($comments as $comment) {
            if (array_key_exists($comment->commentable_id, $resolutionsByTicketId)) {
                continue;
            }

            $reason = $this->parseCloseReasonText($comment->body);
            if ($reason === null) {
                continue;
            }

            $resolutionsByTicketId[$comment->commentable_id] = $this->resolutionFromReasonText($reason);
        }

        return $resolutionsByTicketId;
    }

    private function parseCloseReasonText(string $body): ?string
    {
        return preg_match('/Reason: "(.+)"\.\s*$/s', $body, $matches) ? $matches[1] : null;
    }

    private function resolutionFromReasonText(string $reasonText): ?TicketResolution
    {
        $reasonText = trim($reasonText);

        foreach (TicketResolution::cases() as $resolution) {
            if ($resolution->closeReasonText() === $reasonText) {
                return $resolution;
            }
        }

        return null;
    }

    /**
     * @return Builder<Ticket>
     */
    private function pendingTicketsQuery(TicketState $state): Builder
    {
        return Ticket::withTrashed()
            ->where('state', $state)
            ->whereNull('resolution')
            ->select('id');
    }

    /**
     * @param list<int> $ticketIds
     * @return int the number of tickets actually changed
     */
    private function writeResolution(array $ticketIds, TicketResolution $resolution): int
    {
        return Ticket::withTrashed()
            ->whereIn('id', $ticketIds)
            ->where('state', $resolution->finishedState())
            ->whereNull('resolution')
            ->update([
                'resolution' => $resolution->value,
                'updated_at' => DB::raw('updated_at'), // quietly
            ]);
    }
}
