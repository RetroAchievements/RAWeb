<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class UserApiCallCountService
{
    private const PENDING_HASH_KEY = 'api-call-counts';

    /**
     * Record API usage counts.
     *
     * When the Redis cache driver is enabled, counts are buffered
     * and then batch-flushed to the DB. This helps us mitigate
     * contention issues on the users table.
     *
     * When Redis isn't being used (eg: tests), counts are instead
     * written directly to the DB.
     */
    public function record(User|int $user): void
    {
        $userId = $user instanceof User ? $user->id : $user;

        if (config('cache.default') === 'redis') {
            Redis::hincrby(self::PENDING_HASH_KEY, (string) $userId, 1);
        } else {
            // Write directly to DB for non-Redis environments (eg: tests).
            User::whereKey($userId)->increment('web_api_calls');
        }
    }

    /**
     * Flush pending call count deltas from Redis to the DB.
     * This is what actually writes to the users.web_api_calls fields.
     */
    public function flushToDatabase(): int
    {
        // Batch flushing only works with Redis. Other drivers don't track pending users.
        if (config('cache.default') !== 'redis') {
            return 0;
        }

        $callCounts = $this->getAndClearPendingCounts();

        if (empty($callCounts)) {
            return 0;
        }

        try {
            $chunks = array_chunk($callCounts, 500, preserve_keys: true);

            DB::transaction(function () use ($chunks): void {
                foreach ($chunks as $chunk) {
                    $this->bulkUpdateChunk($chunk);
                }
            });
        } catch (Throwable $exception) {
            // Restore captured deltas so any transient DB write error doesn't silently lose counts.
            $this->restorePendingCounts($callCounts);

            throw $exception;
        }

        return count($callCounts);
    }

    /**
     * @return array<int, int>
     */
    private function getAndClearPendingCounts(): array
    {
        // The transaction keeps HGETALL and DEL in one MULTI/EXEC block.
        /** @phpstan-ignore-next-line -- PHPStan doesn't understand Laravel's Redis facade types */
        $results = Redis::transaction(function (mixed $transaction): void {
            $transaction->hgetall(self::PENDING_HASH_KEY);
            $transaction->del(self::PENDING_HASH_KEY);
        });

        return $this->normalizeRedisHashResult($results[0] ?? []);
    }

    /**
     * @param array<int, int> $callCounts
     */
    private function restorePendingCounts(array $callCounts): void
    {
        foreach ($callCounts as $userId => $callCount) {
            Redis::hincrby(self::PENDING_HASH_KEY, (string) $userId, $callCount);
        }
    }

    /**
     * Normalize HGETALL responses across Redis clients.
     *
     * Predis returns associative arrays, while phpredis returns alternating
     * field/value lists from transaction responses.
     *
     * Which client is used is env-based, so we have to normalize for either
     * possibility.
     *
     * @return array<int, int>
     */
    private function normalizeRedisHashResult(mixed $results): array
    {
        if (!is_array($results)) {
            return [];
        }

        $callCounts = [];

        if (array_is_list($results)) {
            for ($i = 0; $i < count($results); $i += 2) {
                if (!isset($results[$i], $results[$i + 1])) {
                    continue;
                }

                $callCounts[(int) $results[$i]] = (int) $results[$i + 1];
            }

            return $callCounts;
        }

        foreach ($results as $userId => $callCount) {
            $callCounts[(int) $userId] = (int) $callCount;
        }

        return $callCounts;
    }

    /**
     * @param array<int, int> $callCounts
     */
    private function bulkUpdateChunk(array $callCounts): void
    {
        $cases = [];
        $bindings = [];
        $ids = [];

        foreach ($callCounts as $userId => $callCount) {
            $ids[] = (int) $userId;
            $cases[] = 'WHEN ? THEN ?';
            $bindings[] = (int) $userId;
            $bindings[] = (int) $callCount;
        }

        $caseStatement = implode(' ', $cases);
        $idPlaceholders = implode(',', array_fill(0, count($ids), '?'));

        $bindings = array_merge($bindings, $ids);

        // Eloquent doesn't let us update N users with N different deltas in a single query,
        // so we'll bypass Eloquent and use raw SQL instead.
        DB::update(
            "UPDATE users SET web_api_calls = web_api_calls + CASE id {$caseStatement} END WHERE id IN ({$idPlaceholders})",
            $bindings
        );
    }
}
