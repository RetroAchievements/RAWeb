<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class UserLastActivityService
{
    private const CACHE_PREFIX = 'user-activity:';
    private const PENDING_SET_KEY = 'user-activity-flush:pending-set';
    private const CACHE_TTL_HOURS = 24;

    /**
     * Record user activity timestamps.
     *
     * When the Redis cache driver is enabled, timestamps are cached
     * and then batch-flushed to the DB. This helps us mitigate lock
     * contention issues on the users table.
     *
     * When Redis isn't being used (eg: tests), timestamps are instead
     * written directly to the DB.
     */
    public function touch(User|int $user): void
    {
        $userId = $user instanceof User ? $user->id : $user;
        $timestamp = now()->timestamp;

        if (config('cache.default') === 'redis') {
            // Cache for fast reads and track for batch flush.
            Cache::put(
                self::CACHE_PREFIX . $userId,
                $timestamp,
                now()->addHours(self::CACHE_TTL_HOURS)
            );

            Redis::sadd(self::PENDING_SET_KEY, $userId); // https://redis.io/docs/latest/commands/sadd/
        } else {
            // Write directly to DB for non-Redis environments (eg: tests).
            DB::table('users')->where('id', $userId)->update([
                'last_activity_at' => Carbon::createFromTimestamp($timestamp),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Get the last activity timestamp for a user.
     */
    public function getLastActivity(int $userId): ?Carbon
    {
        // Check the cache first.
        if (config('cache.default') === 'redis') {
            $timestamp = Cache::get(self::CACHE_PREFIX . $userId);
            if ($timestamp !== null) {
                return Carbon::createFromTimestamp((int) $timestamp);
            }
        }

        // Otherwise, fall back to the DB.
        // We use the DB facade so we don't trigger the user model's accessor recursively.
        $dbValue = DB::table('users')->where('id', $userId)->value('last_activity_at');

        return $dbValue !== null ? Carbon::parse($dbValue) : null;
    }

    /**
     * Count all users active in the last N minutes.
     *
     * NOTE: There's a small window where users who became active very
     * recently may not be counted if their activity hasn't been flushed
     * to the database yet.
     */
    public function countOnline(int $withinMinutes = 10): int
    {
        // In local dev, flush first since the scheduler may not be running.
        if (app()->isLocal()) {
            $this->flushToDatabase();
        }

        return User::where('last_activity_at', '>', now()->subMinutes($withinMinutes))->count();
    }

    /**
     * Flush pending activity timestamps from the cache to the DB.
     * This is what actually writes to the users.last_activity_at fields.
     */
    public function flushToDatabase(): int
    {
        // Batch flushing only works with Redis. Other drivers don't track pending users.
        if (config('cache.default') !== 'redis') {
            return 0;
        }

        // Atomically get and clear the pending user IDs.
        /** @phpstan-ignore-next-line -- PHPStan doesn't understand Laravel's Redis facade types */
        $results = Redis::pipeline(function ($pipe) {
            $pipe->smembers(self::PENDING_SET_KEY); // https://redis.io/docs/latest/commands/smembers/
            $pipe->del(self::PENDING_SET_KEY); // https://redis.io/docs/latest/commands/del/
        });

        $pendingUserIds = array_map('intval', $results[0] ?? []);

        if (empty($pendingUserIds)) {
            return 0;
        }

        // Collect timestamps for all pending users.
        $activities = [];
        foreach ($pendingUserIds as $userId) {
            $timestamp = Cache::pull(self::CACHE_PREFIX . $userId);
            if ($timestamp !== null) {
                $activities[$userId] = $timestamp;
            }
        }

        if (empty($activities)) {
            return 0;
        }

        // Write in chunks to avoid overly-massive queries.
        $chunks = array_chunk($activities, 500, preserve_keys: true);

        foreach ($chunks as $chunk) {
            $this->bulkUpdateChunk($chunk);
        }

        return count($activities);
    }

    private function bulkUpdateChunk(array $activities): void
    {
        $cases = [];
        $bindings = [];
        $ids = [];

        foreach ($activities as $userId => $timestamp) {
            $ids[] = (int) $userId;
            $cases[] = 'WHEN ? THEN ?';
            $bindings[] = (int) $userId;
            $bindings[] = Carbon::createFromTimestamp((int) $timestamp);
        }

        $caseStatement = implode(' ', $cases);
        $idPlaceholders = implode(',', array_fill(0, count($ids), '?'));

        $bindings[] = now();
        $bindings = array_merge($bindings, $ids);

        // Eloquent doesn't let us update N users with N different timestamps in a single query,
        // so we'll bypass Eloquent and use raw SQL instead.
        DB::update(
            "UPDATE users SET last_activity_at = CASE id {$caseStatement} END, updated_at = ? WHERE id IN ({$idPlaceholders})",
            $bindings
        );
    }
}
