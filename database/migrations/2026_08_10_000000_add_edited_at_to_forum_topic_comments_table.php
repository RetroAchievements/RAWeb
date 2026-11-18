<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * A minute holding at least this many updates is a batch write, probably
     * caused by an Artisan command updating stuff in the table. User-driven
     * edits don't have these sorts of dense clusters, so we filter these
     * clusters out of writing to the new column.
     */
    private const BULK_WRITE_MINUTE_THRESHOLD = 20;

    /** @var array<string, int> */
    private array $bulkWriteMinutes = [];

    public function up(): void
    {
        Schema::table('forum_topic_comments', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('edited_by_id');
        });

        $this->backfillEditedAt();
    }

    public function down(): void
    {
        Schema::table('forum_topic_comments', function (Blueprint $table) {
            $table->dropColumn('edited_at');
        });
    }

    /**
     * Before edited_at existed, the UI inferred "edited" from the gap between
     * created_at and updated_at. Unless we backfill, every pre-existing edit
     * is going to lose its label in the React front-end.
     */
    private function backfillEditedAt(): void
    {
        $this->bulkWriteMinutes = $this->fetchBulkWriteMinutes();

        DB::table('forum_topic_comments')
            ->select('id', 'author_id', 'updated_at', 'authorized_at')
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at')
            ->whereColumn('updated_at', '!=', 'created_at')
            ->orderBy('id')
            ->chunkById(2000, function ($comments): void {
                $verifiedAtByUserId = $this->fetchForumVerifiedAtByUserId(
                    $comments->pluck('author_id')->filter()->unique()->all()
                );

                $editedIds = [];
                foreach ($comments as $comment) {
                    if ($this->looksEdited($comment, $verifiedAtByUserId[$comment->author_id] ?? null)) {
                        $editedIds[] = $comment->id;
                    }
                }

                if ($editedIds !== []) {
                    DB::transaction(function () use ($editedIds): void {
                        DB::table('forum_topic_comments')
                            ->whereIn('id', $editedIds)
                            ->update([
                                'edited_at' => DB::raw('updated_at'),
                                'updated_at' => DB::raw('updated_at'), // preserve updated_at's value
                            ]);
                    }, attempts: 5); // capture edits while the migration runs
                }
            });
    }

    /**
     * @param int[] $userIds
     * @return array<int, string|null>
     */
    private function fetchForumVerifiedAtByUserId(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return DB::table('users')
            ->whereIn('id', $userIds)
            ->whereNotNull('forum_verified_at')
            ->pluck('forum_verified_at', 'id')
            ->all();
    }

    private function looksEdited(object $comment, ?string $forumVerifiedAt): bool
    {
        $updatedAt = Carbon::parse($comment->updated_at);

        if (isset($this->bulkWriteMinutes[$updatedAt->format('Y-m-d H:i')])) {
            return false;
        }

        if ($comment->authorized_at !== null && $this->isSameEvent($updatedAt, $comment->authorized_at)) {
            return false;
        }

        if ($forumVerifiedAt !== null && $this->isSameEvent($updatedAt, $forumVerifiedAt)) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, int> minute keys ("Y-m-d H:i")
     */
    private function fetchBulkWriteMinutes(): array
    {
        return DB::table('forum_topic_comments')
            ->selectRaw('substr(updated_at, 1, 16) as minute')
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at')
            ->whereColumn('updated_at', '!=', 'created_at')
            ->groupBy('minute')
            ->havingRaw('COUNT(*) >= ?', [self::BULK_WRITE_MINUTE_THRESHOLD])
            ->pluck('minute')
            ->flip()
            ->all();
    }

    private function isSameEvent(Carbon $updatedAt, string $eventAt): bool
    {
        return $updatedAt->diffInSeconds(Carbon::parse($eventAt), absolute: true) <= 5;
    }
};
