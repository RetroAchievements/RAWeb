<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\ForumTopicComment;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\GameBadgeAttribution;
use App\Platform\Services\GameBadgeBackfillService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class BackfillGameBadgesFromForumCommentsCommand extends Command
{
    protected $signature = 'ra:platform:game-badges:backfill-forum-comments';
    protected $description = 'Backfill game_badges rows from icon change posts in forum topics.';

    private int $processed = 0;
    private int $newRows = 0;
    private int $reusedRows = 0;
    private int $skippedContamination = 0; // posts for other assets
    private int $skippedNoLink = 0;
    private int $skippedNoKeyword = 0;
    private int $skippedUnlabeledMulti = 0;
    private int $skippedMissingFile = 0;
    private int $skippedWrongSize = 0; // matched a non-96x96 image

    /** @var array<int, int|null> */
    private array $userIdCache = [];

    public function handle(GameBadgeBackfillService $backfillService): void
    {
        $this->info('Building media file index...');
        $backfillService->buildFileIndex();

        $this->info('Counting forum comments...');

        $total = (int) $this->baseQuery()->count();
        $this->info("Processing {$total} forum comment entries...");

        $progressBar = $this->output->createProgressBar($total);

        $backfillService->streamByGame(
            $this->baseQuery()
                ->orderBy('games.id')
                ->orderBy('forum_topic_comments.created_at')
                ->cursor(),
            fn (ForumTopicComment $comment): int => (int) $comment->getAttribute('game_id'),
            function (ForumTopicComment $comment, int $gameId) use ($backfillService, $progressBar): bool {
                $touched = $this->processComment($backfillService, $gameId, $comment);
                $progressBar->advance();

                return $touched;
            },
        );

        $progressBar->finish();

        $this->newLine();
        $this->info(sprintf(
            'Forum comment backfill complete. Processed: %d (new rows: %d, reused rows: %d). '
            . 'Skipped - contamination: %d, no link: %d, no keyword: %d, unlabeled multi-image: %d, '
            . 'missing file: %d, wrong size: %d.',
            $this->processed,
            $this->newRows,
            $this->reusedRows,
            $this->skippedContamination,
            $this->skippedNoLink,
            $this->skippedNoKeyword,
            $this->skippedUnlabeledMulti,
            $this->skippedMissingFile,
            $this->skippedWrongSize,
        ));
    }

    /**
     * @return Builder<ForumTopicComment>
     */
    private function baseQuery()
    {
        return ForumTopicComment::query()
            ->authorized()
            ->join('games', 'games.forum_topic_id', '=', 'forum_topic_comments.forum_topic_id')
            ->whereNotIn('games.system_id', System::getNonGameSystems())
            ->where('forum_topic_comments.body', 'like', '%/Images/%')
            ->select('forum_topic_comments.*', 'games.id as game_id');
    }

    private function processComment(
        GameBadgeBackfillService $backfillService,
        int $gameId,
        ForumTopicComment $comment,
    ): bool {
        /** @var Carbon $commentTimestamp */
        $commentTimestamp = $comment->created_at;
        $body = $comment->body;
        $low = strtolower($body);

        if ($this->containsAny($low, ['mastery', 'box art', 'boxart', 'box-art', 'title screen'])) {
            $this->skippedContamination++;

            return false;
        }

        preg_match_all('#/Images/(\d+)\.png#', $body, $matches);
        $paths = array_map(fn (string $id): string => "/Images/{$id}.png", $matches[1]);

        if (count($paths) === 0) {
            $this->skippedNoLink++;

            return false;
        }

        $hasNewLabel = (bool) preg_match('/new\s+(?:icon|badge)/i', $body);
        $hasOldLabel = (bool) preg_match('/old\s+(?:icon|badge)/i', $body);

        if ($hasNewLabel && $hasOldLabel) {
            $newPath = $this->firstPathAfter($body, '/new\s+(?:icon|badge)/i');
            $recordedNew = $newPath !== null
                && $this->recordNew($backfillService, $gameId, $newPath, $commentTimestamp, $this->resolveUploaderId($body));

            $oldPath = $this->firstPathAfter($body, '/old\s+(?:icon|badge)/i');
            $recordedOld = $oldPath !== null
                && $this->recordOld($backfillService, $gameId, $oldPath, $commentTimestamp);

            return $recordedNew || $recordedOld;
        }

        if (count($paths) === 1) {
            $hasIconKeyword = str_contains($low, 'icon') || str_contains($low, 'badge');
            $hasChangeKeyword = $this->containsAny($low, [
                'backup', 'outvoted', 'community vote', 'icon-gauntlet', 'archive of previous', 'replaced', 'added via',
            ]);

            if (!$hasIconKeyword || !$hasChangeKeyword) {
                $this->skippedNoKeyword++;

                return false;
            }

            $isNewIcon = $this->containsAny($low, ['added via', 'icon by', 'new icon']);

            return $isNewIcon
                ? $this->recordNew($backfillService, $gameId, $paths[0], $commentTimestamp, $this->resolveUploaderId($body))
                : $this->recordOld($backfillService, $gameId, $paths[0], $commentTimestamp);
        }

        // multiple images without New/Old labels ... we can't tell which one is the relevant icon
        $this->skippedUnlabeledMulti++;

        return false;
    }

    private function recordNew(
        GameBadgeBackfillService $backfillService,
        int $gameId,
        string $imageAssetPath,
        Carbon $at,
        ?int $uploadedByUserId,
    ): bool {
        if (!$this->preflight($backfillService, $gameId, $imageAssetPath)) {
            return false;
        }

        $backfillService->markAsCurrent(
            gameId: $gameId,
            imageAssetPath: $imageAssetPath,
            at: $at,
            attribution: GameBadgeAttribution::BackfillForumComment,
            uploadedByUserId: $uploadedByUserId,
        );

        $this->processed++;

        return true;
    }

    private function recordOld(
        GameBadgeBackfillService $backfillService,
        int $gameId,
        string $imageAssetPath,
        Carbon $at,
    ): bool {
        if (!$this->preflight($backfillService, $gameId, $imageAssetPath)) {
            return false;
        }

        // the post only tells us this icon was replaced at the comment time. its became_current_at
        // is unknown, so fall back to the file mtime (capped below the comment) unless an earlier
        // post already established a later, more accurate timestamp (markAsCurrent keeps the later)
        $mtime = $backfillService->resolveFileMtime($imageAssetPath);
        $becameCurrentAt = ($mtime !== null && $mtime->lessThan($at))
            ? $mtime
            : $at->copy()->subSecond();

        $backfillService->markAsCurrent(
            gameId: $gameId,
            imageAssetPath: $imageAssetPath,
            at: $becameCurrentAt,
            attribution: GameBadgeAttribution::BackfillForumComment,
        );
        $backfillService->markAsReplaced($gameId, $imageAssetPath, $at);

        $this->processed++;

        return true;
    }

    /**
     * Gate on a missing file (the service can't be called without a sha1) and on
     * non-badge dimensions (the service refuses to record anything that isn't 96x96).
     */
    private function preflight(GameBadgeBackfillService $backfillService, int $gameId, string $imageAssetPath): bool
    {
        $sha1 = $backfillService->computeSha1($imageAssetPath);

        if ($sha1 === null) {
            $this->skippedMissingFile++;

            return false;
        }

        if (!$backfillService->isBadgeSized($imageAssetPath)) {
            $this->skippedWrongSize++;

            return false;
        }

        if ($backfillService->rowExistsForSha1($gameId, $sha1)) {
            $this->reusedRows++;
        } else {
            $this->newRows++;
        }

        return true;
    }

    private function firstPathAfter(string $body, string $labelPattern): ?string
    {
        if (!preg_match($labelPattern, $body, $labelMatch, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $offset = $labelMatch[0][1] + strlen($labelMatch[0][0]);

        if (preg_match('#/Images/(\d+)\.png#', $body, $pathMatch, 0, $offset)) {
            return "/Images/{$pathMatch[1]}.png";
        }

        return null;
    }

    private function resolveUploaderId(string $body): ?int
    {
        if (!preg_match('/(?:icon|badge)\s+by\s*\[user=(\d+)\]/i', $body, $matches)) {
            return null;
        }

        $userId = (int) $matches[1];

        if (array_key_exists($userId, $this->userIdCache)) {
            return $this->userIdCache[$userId];
        }

        // resolve through withTrashed since uploaders from very old votes may be deleted
        return $this->userIdCache[$userId] = User::withTrashed()->whereKey($userId)->value('id');
    }

    /**
     * @param list<string> $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
