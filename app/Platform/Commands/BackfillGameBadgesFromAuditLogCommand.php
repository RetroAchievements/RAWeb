<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\System;
use App\Platform\Enums\GameBadgeAttribution;
use App\Platform\Services\GameBadgeBackfillService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;

class BackfillGameBadgesFromAuditLogCommand extends Command
{
    protected $signature = 'ra:platform:game-badges:backfill-audit-log';
    protected $description = 'Backfill game_badges rows from audit_log badge change events.';

    public function handle(GameBadgeBackfillService $backfillService): void
    {
        $this->info('Building media file index...');
        $backfillService->buildFileIndex();

        $this->info('Counting audit_log badge events...');

        $baseQuery = DB::table('audit_log')
            ->join('games', function ($join): void {
                $join->on('audit_log.subject_id', '=', 'games.id')
                    ->where('audit_log.subject_type', '=', 'game');
            })
            ->whereNotIn('games.system_id', System::getNonGameSystems())
            ->where('audit_log.properties', 'like', '%image_icon_asset_path%');

        $total = (int) (clone $baseQuery)->count();
        $this->info("Processing {$total} audit_log entries...");

        $progressBar = $this->output->createProgressBar($total);

        // use a cursor so we don't load the full result set into memory
        $currentGameId = null;
        $previousTimestamp = null;
        $previousNewPath = null;

        $baseQuery
            ->orderBy('audit_log.subject_id')
            ->orderBy('audit_log.id')
            ->select([
                'audit_log.id',
                'audit_log.subject_id as game_id',
                'audit_log.properties',
                'audit_log.created_at',
            ])
            ->cursor()
            ->each(function (stdClass $entry) use ($backfillService, $progressBar, &$currentGameId, &$previousTimestamp, &$previousNewPath): void {
                $gameId = (int) $entry->game_id;

                if ($gameId !== $currentGameId) {
                    $currentGameId = $gameId;
                    $previousTimestamp = null;
                    $previousNewPath = null;
                }

                $properties = json_decode((string) $entry->properties, true);

                if (!is_array($properties)) {
                    $progressBar->advance();

                    return;
                }

                $attributes = $properties['attributes'] ?? [];
                $old = $properties['old'] ?? [];

                if (
                    !array_key_exists('image_icon_asset_path', $attributes)
                    && !array_key_exists('image_icon_asset_path', $old)
                ) {
                    $progressBar->advance();

                    return;
                }

                $timestamp = Carbon::parse($entry->created_at);
                $oldPath = is_string($old['image_icon_asset_path'] ?? null) ? $old['image_icon_asset_path'] : null;
                $newPath = is_string($attributes['image_icon_asset_path'] ?? null) ? $attributes['image_icon_asset_path'] : null;

                if ($oldPath !== null && $oldPath !== $newPath) {
                    $becameCurrentAt = ($previousTimestamp !== null && $previousNewPath === $oldPath)
                        ? $previousTimestamp
                        : null;

                    if ($becameCurrentAt === null) {
                        $mtime = $backfillService->resolveFileMtime($oldPath);
                        $becameCurrentAt = ($mtime !== null && $mtime->lessThan($timestamp))
                            ? $mtime
                            : $timestamp->copy()->subSecond();
                    }

                    $backfillService->markAsCurrent(
                        gameId: $gameId,
                        imageAssetPath: $oldPath,
                        at: $becameCurrentAt,
                        attribution: GameBadgeAttribution::BackfillAuditLog,
                    );
                    $backfillService->markAsReplaced($gameId, $oldPath, $timestamp);
                }

                if ($newPath !== null && $newPath !== $oldPath) {
                    $backfillService->markAsCurrent(
                        gameId: $gameId,
                        imageAssetPath: $newPath,
                        at: $timestamp,
                        attribution: GameBadgeAttribution::BackfillAuditLog,
                    );
                }

                $previousTimestamp = $timestamp;
                $previousNewPath = $newPath;
                $progressBar->advance();
            });

        $progressBar->finish();
        $this->newLine();
        $this->info('Audit log backfill complete.');
    }
}
