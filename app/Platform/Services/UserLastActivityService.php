<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UserLastActivityService
{
    private const CACHE_PREFIX = 'user-activity:';
    private const PENDING_KEY = 'user-activity-flush:pending';
    private const CACHE_TTL_HOURS = 24;
    private const LOCK_TTL_SECONDS = 10;
    private const LOCK_WAIT_SECONDS = 2;

    /**
     * Record user activity in the cache, which will be flushed periodically to the DB.
     */
    public function touch(User|int $user): void
    {
        $userId = $user instanceof User ? $user->id : $user;
        $timestamp = now()->timestamp;

        // Store the user's current activity timestamp.
        Cache::put(
            self::CACHE_PREFIX . $userId,
            $timestamp,
            now()->addHours(self::CACHE_TTL_HOURS)
        );

        // Track this user ID as needing to be flushed to DB.
        // The lock prevents race conditions on the read-modify-write cycle for the pending list.
        // Actual lock hold time is ~1-2ms. Under extreme load (1000+ req/s), requests that can't
        // acquire the lock within LOCK_WAIT_SECONDS will silently skip tracking.
        Cache::lock(self::PENDING_KEY . ':lock', self::LOCK_TTL_SECONDS)->block(self::LOCK_WAIT_SECONDS, function () use ($userId) {
            $pending = Cache::get(self::PENDING_KEY, []);
            if (!array_key_exists($userId, $pending)) {
                $pending[$userId] = true;
                Cache::put(self::PENDING_KEY, $pending, now()->addHours(self::CACHE_TTL_HOURS));
            }
        });
    }

    /**
     * Get the last activity timestamp for a user.
     */
    public function getLastActivity(int $userId): ?Carbon
    {
        $timestamp = Cache::get(self::CACHE_PREFIX . $userId);
        if ($timestamp !== null) {
            return Carbon::createFromTimestamp((int) $timestamp);
        }

        // Use a raw DB query here to avoid triggering the user model's accessor recursively.
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
        // Atomically get and clear the pending updates list.
        $pending = Cache::lock(self::PENDING_KEY . ':lock', self::LOCK_TTL_SECONDS)->block(self::LOCK_WAIT_SECONDS, function () {
            $pending = Cache::get(self::PENDING_KEY, []);
            Cache::forget(self::PENDING_KEY);

            return $pending;
        });

        if (empty($pending)) {
            return 0;
        }

        // Collect timestamps for all the pending users.
        $activities = [];
        foreach (array_keys($pending) as $userId) {
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
