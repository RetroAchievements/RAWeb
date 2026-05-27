<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\Game;
use App\Models\GameBadge;
use App\Models\System;
use App\Platform\Enums\GameBadgeAttribution;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class GameBadgeBackfillService
{
    /** @var list<array{mtime: int, path: string}>|null */
    private ?array $fileIndex = null;

    /** @var array<string, string|null> */
    private array $sha1Cache = [];

    public function buildFileIndex(): void
    {
        $entries = [];

        foreach (Storage::disk('media')->allFiles('Images') as $relativePath) {
            if (!preg_match('/^Images\/\d+\.png$/', $relativePath)) {
                continue;
            }

            $mtime = Storage::disk('media')->lastModified($relativePath);
            $entries[] = [
                'mtime' => $mtime,
                'path' => '/' . $relativePath,
            ];
        }

        usort($entries, fn (array $a, array $b): int => $a['mtime'] <=> $b['mtime']);

        $this->fileIndex = $entries;
    }

    /**
     * Given a suspected badge upload time, find if any badge files were written to
     * the media disk around that same time. Those are our candidate matches.
     *
     * We'll only take candidates we're 100% sure of. If multiple files were written
     * around the same timestamp, we can't be certain of which one is the right file.
     *
     * @return list<array{mtime: int, path: string}>
     */
    public function findCandidatesInWindow(CarbonInterface $timestamp, int $windowSeconds = 60): array
    {
        $this->ensureFileIndexBuilt();

        $start = $timestamp->getTimestamp() - $windowSeconds;
        $end = $timestamp->getTimestamp() + $windowSeconds;

        // binary search for the first entry with mtime >= $start
        // the index is sorted by mtime in buildFileIndex(), so this is O(log N) instead of O(N)
        $count = count($this->fileIndex);
        $low = 0;
        $high = $count;
        while ($low < $high) {
            $mid = intdiv($low + $high, 2);
            if ($this->fileIndex[$mid]['mtime'] < $start) {
                $low = $mid + 1;
            } else {
                $high = $mid;
            }
        }

        // scan forward from $low until mtime exceeds $end
        $results = [];
        for ($i = $low; $i < $count; $i++) {
            if ($this->fileIndex[$i]['mtime'] > $end) {
                break;
            }
            $results[] = $this->fileIndex[$i];
        }

        return $results;
    }

    public function markAsCurrent(
        int $gameId,
        string $imageAssetPath,
        CarbonInterface $at,
        GameBadgeAttribution $attribution,
        ?int $uploadedByUserId = null,
    ): void {
        if ($this->isPlaceholderPath($imageAssetPath)) {
            return;
        }

        $sha1 = $this->computeSha1($imageAssetPath);

        if ($sha1 === null) {
            return;
        }

        $existingRow = GameBadge::query()
            ->where('game_id', $gameId)
            ->where('sha1', $sha1)
            ->first();

        if ($existingRow === null) {
            GameBadge::query()->create([
                'game_id' => $gameId,
                'image_asset_path' => $imageAssetPath,
                'sha1' => $sha1,
                'attribution_source' => $attribution,
                'uploaded_by_user_id' => $uploadedByUserId,
                'became_current_at' => $at,
                'replaced_at' => null,
            ]);
        } elseif ($at->greaterThanOrEqualTo($existingRow->became_current_at)) {
            $existingRow->update([
                'image_asset_path' => $imageAssetPath,
                'uploaded_by_user_id' => $uploadedByUserId ?? $existingRow->uploaded_by_user_id,
                'became_current_at' => $at,
                'replaced_at' => null,
            ]);
        }

        // mark all other previously-current rows for this game as replaced in a single query
        GameBadge::query()
            ->where('game_id', $gameId)
            ->whereNull('replaced_at')
            ->where('sha1', '!=', $sha1)
            ->where('became_current_at', '<', $at)
            ->update(['replaced_at' => $at]);
    }

    public function markAsReplaced(int $gameId, string $imageAssetPath, CarbonInterface $at): void
    {
        if ($this->isPlaceholderPath($imageAssetPath)) {
            return;
        }

        $sha1 = $this->computeSha1($imageAssetPath);

        if ($sha1 === null) {
            return;
        }

        $row = GameBadge::query()
            ->where('game_id', $gameId)
            ->where('sha1', $sha1)
            ->first();

        if ($row === null) {
            return;
        }

        if ($row->replaced_at !== null && $row->replaced_at->greaterThanOrEqualTo($at)) {
            return;
        }

        $row->update(['replaced_at' => $at]);
    }

    public function rowExistsForSha1(int $gameId, string $sha1): bool
    {
        return GameBadge::query()
            ->where('game_id', $gameId)
            ->where('sha1', $sha1)
            ->exists();
    }

    /**
     * Collapse same-day badge rows for a game.
     *
     * When multiple rows share the same calendar date for became_current_at,
     * keep only the row with the latest timestamp and delete the rest.
     * Devs sometimes iterate on a badge multiple times in a single editing session.
     * The intermediate versions were never the "actual" badge for any meaningful duration.
     *
     * Returns the number of rows deleted.
     */
    public function collapseSameDayTransitions(int $gameId): int
    {
        $rows = GameBadge::query()
            ->where('game_id', $gameId)
            ->orderBy('became_current_at')
            ->get();

        if ($rows->count() < 2) {
            return 0;
        }

        $deleteIds = [];

        $rows
            ->groupBy(fn (GameBadge $row): string => $row->became_current_at->toDateString())
            ->each(function ($group) use (&$deleteIds): void {
                if ($group->count() < 2) {
                    return;
                }

                $keeper = $group->sortByDesc(fn (GameBadge $row) => $row->became_current_at->getTimestamp())->first();

                foreach ($group as $row) {
                    if ($row->id !== $keeper->id) {
                        $deleteIds[] = $row->id;
                    }
                }
            });

        if (empty($deleteIds)) {
            return 0;
        }

        $deleted = GameBadge::query()->whereIn('id', $deleteIds)->delete();

        $this->chainReplacedAtForGame($gameId);

        return (int) $deleted;
    }

    public function chainReplacedAtForGame(int $gameId): void
    {
        $rows = GameBadge::query()
            ->where('game_id', $gameId)
            ->orderBy('became_current_at')
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        foreach ($rows as $index => $row) {
            $candidate = $rows->get($index + 1)?->became_current_at;

            // the last row should have replaced_at=null (canonical badge)
            if ($candidate === null) {
                if ($row->replaced_at !== null) {
                    $row->update(['replaced_at' => null]);
                }

                continue;
            }

            if ($row->replaced_at === null || $row->replaced_at->greaterThan($candidate)) {
                $row->update(['replaced_at' => $candidate]);
            }
        }
    }

    public function reconcileCurrentCanonical(Game $game): void
    {
        if (!System::isGameSystem($game->system_id)) {
            return;
        }

        $path = $game->image_icon_asset_path;

        if ($path === null || $this->isPlaceholderPath($path)) {
            return;
        }

        $sha1 = $this->computeSha1($path);

        if ($sha1 === null) {
            return;
        }

        $becameCurrentAt = $this->resolveFileTimestamp($path, $game);

        $existingRow = GameBadge::query()
            ->where('game_id', $game->id)
            ->where('sha1', $sha1)
            ->first();

        if ($existingRow !== null) {
            $this->retireStaleCurrentRows($game, $becameCurrentAt, exceptRowId: $existingRow->id);

            $existingRow->update([
                'image_asset_path' => $path,
                'replaced_at' => null,
                'became_current_at' => $existingRow->became_current_at->greaterThan($becameCurrentAt)
                    ? $existingRow->became_current_at
                    : $becameCurrentAt,
            ]);

            return;
        }

        $this->retireStaleCurrentRows($game, $becameCurrentAt);

        $game->badges()->create([
            'image_asset_path' => $path,
            'sha1' => $sha1,
            'attribution_source' => GameBadgeAttribution::BackfillCurrentCanonical,
            'uploaded_by_user_id' => null,
            'became_current_at' => $becameCurrentAt,
            'replaced_at' => null,
        ]);
    }

    public function computeSha1(string $imageAssetPath): ?string
    {
        if (array_key_exists($imageAssetPath, $this->sha1Cache)) {
            return $this->sha1Cache[$imageAssetPath];
        }

        $storagePath = ltrim($imageAssetPath, '/');

        if (!Storage::disk('media')->exists($storagePath)) {
            return $this->sha1Cache[$imageAssetPath] = null;
        }

        return $this->sha1Cache[$imageAssetPath] = sha1(Storage::disk('media')->get($storagePath));
    }

    public function resolveFileMtime(string $imageAssetPath): ?CarbonInterface
    {
        $storagePath = ltrim($imageAssetPath, '/');

        if (Storage::disk('media')->exists($storagePath)) {
            return Carbon::createFromTimestamp(Storage::disk('media')->lastModified($storagePath));
        }

        return null;
    }

    public function resolveFileTimestamp(string $imageAssetPath, Game $game): CarbonInterface
    {
        $storagePath = ltrim($imageAssetPath, '/');

        if (Storage::disk('media')->exists($storagePath)) {
            return Carbon::createFromTimestamp(Storage::disk('media')->lastModified($storagePath));
        }

        return $game->updated_at ?? now();
    }

    private function isPlaceholderPath(string $path): bool
    {
        return in_array($path, [Game::PLACEHOLDER_BADGE_PATH, Game::PLACEHOLDER_IMAGE_PATH], true);
    }

    private function ensureFileIndexBuilt(): void
    {
        if ($this->fileIndex === null) {
            $this->buildFileIndex();
        }
    }

    private function retireStaleCurrentRows(Game $game, CarbonInterface $at, ?int $exceptRowId = null): void
    {
        $query = $game->badges()->whereNull('replaced_at');
        if ($exceptRowId !== null) {
            $query->where('id', '!=', $exceptRowId);
        }

        foreach ($query->get() as $stale) {
            $candidate = $stale->became_current_at->greaterThanOrEqualTo($at)
                ? $stale->became_current_at->copy()->addSecond()
                : $at;

            $stale->update(['replaced_at' => $candidate]);
        }
    }
}
