<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Community\Enums\ArticleType;
use App\Community\Enums\ModerationActionType;
use App\Models\Comment;
use App\Models\UserModerationAction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class BackfillModerationActions extends Command
{
    protected $signature = 'ra:community:backfill-moderation-actions';
    protected $description = 'Backfill user_moderation_actions from activity log history';

    public function handle(): void
    {
        if (UserModerationAction::exists()) {
            $this->info('user_moderation_actions table already has entries. Skipping backfill.');

            return;
        }

        $this->info('Backfilling moderation actions from activity log...');

        $query = DB::table('audit_log')
            ->where('subject_type', 'user')
            ->where(function (Builder $q) {
                $q->where('properties', 'like', '%muted_until%')
                    ->orWhere('properties', 'like', '%banned_at%')
                    ->orWhere('properties', 'like', '%unranked_at%');
            })
            ->orderBy('id');

        $count = $query->count();
        $this->info("Found {$count} activity log entries to process.");

        if ($count === 0) {
            return;
        }

        $progressBar = $this->output->createProgressBar($count);
        $actionsCreated = 0;

        $query->chunk(500, function ($entries) use ($progressBar, &$actionsCreated) {
            $toInsert = [];

            foreach ($entries as $entry) {
                /** @var array<string, mixed>|null $properties */
                $properties = json_decode($entry->properties, true);
                if (!$properties || !is_array($properties)) {
                    $progressBar->advance();

                    continue;
                }

                /** @var array<string, mixed> $attributes */
                $attributes = $properties['attributes'] ?? [];
                /** @var array<string, mixed> $old */
                $old = $properties['old'] ?? [];
                $entryCreatedAt = Carbon::parse($entry->created_at);

                // Users can't moderate themselves - skip rows with corrupt data.
                // We seem to have some corrupt data in the oldest activitylog stuff.
                if ($entry->causer_id === $entry->subject_id) {
                    $progressBar->advance();

                    continue;
                }

                // Process muted_until changes.
                if (array_key_exists('muted_until', $attributes) || array_key_exists('muted_until', $old)) {
                    $action = $this->determineMuteAction($old, $attributes, $entryCreatedAt);
                    if ($action) {
                        $toInsert[] = [
                            'user_id' => $entry->subject_id,
                            'actioned_by_id' => $entry->causer_id,
                            'action' => $action->value,
                            'reason' => null,
                            'expires_at' => $attributes['muted_until'] ?? null,
                            'created_at' => $entry->created_at,
                        ];
                    }
                }

                // Process banned_at changes.
                if (array_key_exists('banned_at', $attributes) || array_key_exists('banned_at', $old)) {
                    $action = $this->determineBanAction($old, $attributes);
                    if ($action) {
                        $toInsert[] = [
                            'user_id' => $entry->subject_id,
                            'actioned_by_id' => $entry->causer_id,
                            'action' => $action->value,
                            'reason' => null,
                            'expires_at' => null,
                            'created_at' => $entry->created_at,
                        ];
                    }
                }

                // Process unranked_at changes - skip implicit untracks from when folks are banned.
                $isAlsoBeingBanned = !empty($attributes['banned_at']) && empty($old['banned_at']);
                if ((array_key_exists('unranked_at', $attributes) || array_key_exists('unranked_at', $old)) && !$isAlsoBeingBanned) {
                    $action = $this->determineUnrankAction($old, $attributes);
                    if ($action) {
                        $toInsert[] = [
                            'user_id' => $entry->subject_id,
                            'actioned_by_id' => $entry->causer_id,
                            'action' => $action->value,
                            'reason' => null,
                            'expires_at' => null,
                            'created_at' => $entry->created_at,
                        ];
                    }
                }

                $progressBar->advance();
            }

            if (!empty($toInsert)) {
                UserModerationAction::insert($toInsert);
                $actionsCreated += count($toInsert);
            }
        });

        $progressBar->finish();
        $this->newLine();
        $this->info("Backfill complete. Created {$actionsCreated} moderation action records.");

        $this->correlateModeratorNotes();
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, mixed> $attributes
     */
    private function determineMuteAction(array $old, array $attributes, Carbon $entryCreatedAt): ?ModerationActionType
    {
        $oldMutedUntilValue = $old['muted_until'] ?? null;
        $oldMutedUntil = is_string($oldMutedUntilValue) && $oldMutedUntilValue !== ''
            ? Carbon::parse($oldMutedUntilValue)
            : null;

        $newMutedUntilValue = $attributes['muted_until'] ?? null;
        $newMutedUntil = is_string($newMutedUntilValue) && $newMutedUntilValue !== ''
            ? Carbon::parse($newMutedUntilValue)
            : null;

        $wasNotMuted = !$oldMutedUntil || $oldMutedUntil->lt($entryCreatedAt);
        $wasMuted = $oldMutedUntil && $oldMutedUntil->gt($entryCreatedAt);
        $isNowMuted = $newMutedUntil && $newMutedUntil->gt($entryCreatedAt);
        $isNowUnmuted = !$newMutedUntil || $newMutedUntil->lt($entryCreatedAt);

        // New mute or mute extension.
        if (($wasNotMuted && $isNowMuted) || ($wasMuted && $isNowMuted)) {
            return ModerationActionType::Mute;
        }

        // Unmute.
        if ($wasMuted && $isNowUnmuted) {
            return ModerationActionType::Unmute;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, mixed> $attributes
     */
    private function determineBanAction(array $old, array $attributes): ?ModerationActionType
    {
        $wasBanned = !empty($old['banned_at']);
        $isNowBanned = !empty($attributes['banned_at']);

        if (!$wasBanned && $isNowBanned) {
            return ModerationActionType::Ban;
        }

        if ($wasBanned && !$isNowBanned) {
            return ModerationActionType::Unban;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, mixed> $attributes
     */
    private function determineUnrankAction(array $old, array $attributes): ?ModerationActionType
    {
        $wasUnranked = !empty($old['unranked_at']);
        $isNowUnranked = !empty($attributes['unranked_at']);

        if (!$wasUnranked && $isNowUnranked) {
            return ModerationActionType::Unrank;
        }

        if ($wasUnranked && !$isNowUnranked) {
            return ModerationActionType::Rerank;
        }

        return null;
    }

    /**
     * Correlate moderator notes from the Comment table with action records.
     * This tries to populate the reason field with explanations moderators left.
     * This only works for stuff that has some correlated activitylog record (2024-03-31+).
     */
    private function correlateModeratorNotes(): void
    {
        $this->info('Correlating moderator notes from Comment table...');

        $notes = Comment::where('ArticleType', ArticleType::UserModeration)
            ->notAutomated()
            ->whereNotNull('user_id')
            ->where('Submitted', '>=', '2024-03-31')
            ->get();

        $numCorrelated = 0;
        foreach ($notes as $note) {
            /** @var Carbon $noteTimestamp */
            $noteTimestamp = $note->Submitted;

            $action = UserModerationAction::where('user_id', $note->ArticleID)
                ->where('actioned_by_id', $note->user_id)
                ->whereBetween('created_at', [
                    $noteTimestamp->copy()->subMinutes(5),
                    $noteTimestamp->copy()->addMinutes(5),
                ])
                ->whereNull('reason')
                ->first();

            if ($action) {
                $action->update(['reason' => $note->Payload]);
                $numCorrelated++;
            }
        }

        $this->info("Correlated {$numCorrelated} moderator notes with action records.");
    }
}
