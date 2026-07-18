<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Community\Enums\CommentableType;
use App\Models\Comment;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\GameBadgeAttribution;
use App\Platform\Services\GameBadgeBackfillService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BackfillGameBadgesFromCommentsCommand extends Command
{
    protected $signature = 'ra:platform:game-badges:backfill-comments';
    protected $description = 'Backfill game_badges rows from game-modification comments with 1-candidate file matches';

    private int $skippedMultiCandidate = 0;
    private int $skippedNoCandidate = 0;
    private int $skippedExisting = 0;
    private int $skippedWrongSize = 0;
    private int $processed = 0;

    /** @var array<string, int|null> */
    private array $nameToIdCache = [];

    public function handle(GameBadgeBackfillService $backfillService): void
    {
        $this->info('Building media file index...');
        $backfillService->buildFileIndex();

        $this->info('Counting comments...');

        $countQuery = Comment::query()
            ->where('commentable_type', CommentableType::GameModification)
            ->where('user_id', Comment::SYSTEM_USER_ID)
            ->where('body', 'like', '%changed the game icon%')
            ->whereExists(function ($q): void {
                $q->select(DB::raw(1))
                    ->from('games')
                    ->whereColumn('games.id', 'comments.commentable_id')
                    ->whereNotIn('games.system_id', System::getNonGameSystems());
            });

        $total = (int) $countQuery->count();
        $this->info("Processing {$total} comment entries...");

        $progressBar = $this->output->createProgressBar($total);

        // use a cursor so we don't use too much memory
        $cursor = Comment::query()
            ->where('commentable_type', CommentableType::GameModification)
            ->where('user_id', Comment::SYSTEM_USER_ID)
            ->where('body', 'like', '%changed the game icon%')
            ->whereExists(function ($q): void {
                $q->select(DB::raw(1))
                    ->from('games')
                    ->whereColumn('games.id', 'comments.commentable_id')
                    ->whereNotIn('games.system_id', System::getNonGameSystems());
            })
            ->orderBy('commentable_id')
            ->orderBy('created_at')
            ->cursor();

        $backfillService->streamByGame(
            $cursor,
            fn (Comment $comment): int => (int) $comment->commentable_id,
            function (Comment $comment, int $gameId) use ($backfillService, $progressBar): bool {
                $touched = $this->processComment($backfillService, $gameId, $comment);
                $progressBar->advance();

                return $touched;
            },
        );

        $progressBar->finish();

        $this->newLine();
        $this->info(sprintf(
            'Comment backfill complete. Processed: %d, skipped (multi-candidate): %d, skipped (no candidate): %d, '
            . 'skipped (existing): %d, skipped (wrong size): %d.',
            $this->processed,
            $this->skippedMultiCandidate,
            $this->skippedNoCandidate,
            $this->skippedExisting,
            $this->skippedWrongSize,
        ));
    }

    private function processComment(
        GameBadgeBackfillService $backfillService,
        int $gameId,
        Comment $comment,
    ): bool {
        /** @var Carbon $commentTimestamp */
        $commentTimestamp = $comment->created_at;

        $candidates = $backfillService->findCandidatesInWindow($commentTimestamp);

        if (count($candidates) === 0) {
            $this->skippedNoCandidate++;

            return false;
        }

        if (count($candidates) !== 1) {
            $this->skippedMultiCandidate++;

            return false;
        }

        $matchedPath = $candidates[0]['path'];
        $sha1 = $backfillService->computeSha1($matchedPath);

        if ($sha1 === null) {
            $this->skippedNoCandidate++;

            return false;
        }

        if (!$backfillService->isBadgeSized($matchedPath)) {
            $this->skippedWrongSize++;

            return false;
        }

        if ($backfillService->rowExistsForSha1($gameId, $sha1)) {
            $this->skippedExisting++;

            return false;
        }

        $uploadedByUserId = $this->resolveUploadedByUserId($comment->body);

        $backfillService->markAsCurrent(
            gameId: $gameId,
            imageAssetPath: $matchedPath,
            at: Carbon::createFromTimestamp($candidates[0]['mtime']),
            attribution: GameBadgeAttribution::BackfillCommentHeuristic,
            uploadedByUserId: $uploadedByUserId,
        );

        $this->processed++;

        return true;
    }

    private function resolveUploadedByUserId(string $body): ?int
    {
        if (!preg_match('/^(.+?) changed the game icon/i', trim($body), $matches)) {
            return null;
        }

        $name = trim($matches[1]);

        if (array_key_exists($name, $this->nameToIdCache)) {
            return $this->nameToIdCache[$name];
        }

        return $this->nameToIdCache[$name] = User::query()
            ->where('display_name', $name)
            ->orWhere('username', $name)
            ->value('id');
    }
}
