<?php

declare(strict_types=1);

use App\Models\User;
use App\Platform\Services\UserApiCallCountService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class);

function mockApiCallCountRedisSnapshot(array $snapshot): void
{
    Redis::shouldReceive('transaction')
        ->once()
        ->andReturnUsing(function (callable $callback) use ($snapshot): array {
            $transaction = new class {
                /** @var list<array{string, string}> */
                public array $commands = [];

                public function hgetall(string $key): void
                {
                    $this->commands[] = ['hgetall', $key];
                }

                public function del(string $key): void
                {
                    $this->commands[] = ['del', $key];
                }
            };

            $callback($transaction);

            expect($transaction->commands)->toEqual([
                ['hgetall', 'api-call-counts'],
                ['del', 'api-call-counts'],
            ]);

            return [$snapshot, 1];
        });
}

function countWebApiCallUpdateQueries(int &$updateQueries): void
{
    DB::listen(function (QueryExecuted $query) use (&$updateQueries): void {
        if (str_starts_with(strtolower($query->sql), 'update users set web_api_calls')) {
            $updateQueries++;
        }
    });
}

describe('record', function () {
    it('increments the redis hash and does not hit the DB when using redis', function () {
        // ARRANGE
        config(['cache.default' => 'redis']);

        $updateQueries = 0;
        $service = app(UserApiCallCountService::class);
        $user = User::factory()->create(['web_api_calls' => 0]);

        countWebApiCallUpdateQueries($updateQueries);

        Redis::shouldReceive('hincrby')
            ->once()
            ->with('api-call-counts', (string) $user->id, 1)
            ->andReturn(1);

        // ACT
        $service->record($user);

        // ASSERT
        expect($updateQueries)->toEqual(0);

        $user->refresh();
        expect($user->web_api_calls)->toEqual(0);
    });

    it('increments the column directly with Eloquent when not using redis', function () {
        // ARRANGE
        config(['cache.default' => 'array']);

        $service = app(UserApiCallCountService::class);
        $user = User::factory()->create(['web_api_calls' => 0]);

        // ACT
        $service->record($user);

        // ASSERT
        $user->refresh();
        expect($user->web_api_calls)->toEqual(1);
    });

    it('accepts both a User object and a user id', function () {
        // ARRANGE
        config(['cache.default' => 'array']);

        $service = app(UserApiCallCountService::class);
        $user1 = User::factory()->create(['web_api_calls' => 0]);
        $user2 = User::factory()->create(['web_api_calls' => 0]);

        // ACT
        $service->record($user1);
        $service->record($user2->id);

        // ASSERT
        expect($user1->refresh()->web_api_calls)->toEqual(1);
        expect($user2->refresh()->web_api_calls)->toEqual(1);
    });
});

describe('flushToDatabase', function () {
    it('returns 0 when not using the redis cache driver', function () {
        // ARRANGE
        config(['cache.default' => 'array']);

        $service = app(UserApiCallCountService::class);

        // ACT
        $flushedCount = $service->flushToDatabase();

        // ASSERT
        expect($flushedCount)->toEqual(0);
    });

    it('uses an atomic redis snapshot and clear primitive', function () {
        // ARRANGE
        config(['cache.default' => 'redis']);

        mockApiCallCountRedisSnapshot([]);

        $service = app(UserApiCallCountService::class);

        // ACT
        $flushedCount = $service->flushToDatabase();

        // ASSERT
        expect($flushedCount)->toEqual(0);
    });

    it('applies buffered deltas additively', function () {
        // ARRANGE
        config(['cache.default' => 'redis']);

        $user = User::factory()->create(['web_api_calls' => 5]);

        mockApiCallCountRedisSnapshot([(string) $user->id, '3']);

        $service = app(UserApiCallCountService::class);

        // ACT
        $flushedCount = $service->flushToDatabase();

        // ASSERT
        expect($flushedCount)->toEqual(1);
        expect($user->refresh()->web_api_calls)->toEqual(8);
    });

    it('handles two users in a single bulk update', function () {
        // ARRANGE
        config(['cache.default' => 'redis']);

        $user1 = User::factory()->create(['web_api_calls' => 1]);
        $user2 = User::factory()->create(['web_api_calls' => 10]);
        $updateQueries = 0;

        countWebApiCallUpdateQueries($updateQueries);

        mockApiCallCountRedisSnapshot([
            (string) $user1->id,
            '2',
            (string) $user2->id,
            '4',
        ]);

        $service = app(UserApiCallCountService::class);

        // ACT
        $flushedCount = $service->flushToDatabase();

        // ASSERT
        expect($flushedCount)->toEqual(2);
        expect($updateQueries)->toEqual(1);
        expect($user1->refresh()->web_api_calls)->toEqual(3);
        expect($user2->refresh()->web_api_calls)->toEqual(14);
    });

    it('does not update users updated_at', function () {
        // ARRANGE
        config(['cache.default' => 'redis']);

        $updatedAt = Carbon::now()->subDay()->startOfSecond();
        $user = User::factory()->create([
            'updated_at' => $updatedAt,
            'web_api_calls' => 5,
        ]);
        $originalUpdatedAt = $user->getRawOriginal('updated_at');

        mockApiCallCountRedisSnapshot([(string) $user->id, '1']);

        $service = app(UserApiCallCountService::class);

        // ACT
        $service->flushToDatabase();

        // ASSERT
        $user->refresh();
        expect($user->getRawOriginal('updated_at'))->toEqual($originalUpdatedAt);
    });

    it('is a no-op when the redis hash is empty', function () {
        // ARRANGE
        config(['cache.default' => 'redis']);

        $updateQueries = 0;

        countWebApiCallUpdateQueries($updateQueries);

        mockApiCallCountRedisSnapshot([]);

        $service = app(UserApiCallCountService::class);

        // ACT
        $flushedCount = $service->flushToDatabase();

        // ASSERT
        expect($flushedCount)->toEqual(0);
        expect($updateQueries)->toEqual(0);
    });
});
