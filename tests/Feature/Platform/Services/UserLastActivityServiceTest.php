<?php

declare(strict_types=1);

use App\Models\User;
use App\Platform\Services\UserLastActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class);

describe('touch', function () {
    it('writes activity directly to the DB when not using the redis cache driver', function () {
        // ARRANGE
        $now = Carbon::now()->startOfSecond();
        Carbon::setTestNow($now);

        $service = app(UserLastActivityService::class);
        $user = User::factory()->create(['last_activity_at' => null]);

        // ACT
        $service->touch($user);

        // ASSERT
        $user->refresh();
        expect($user->getRawOriginal('last_activity_at'))->not->toBeNull();
    });

    it('accepts both a User object and a user id', function () {
        // ARRANGE
        $now = Carbon::now()->startOfSecond();
        Carbon::setTestNow($now);

        $service = app(UserLastActivityService::class);
        $user1 = User::factory()->create(['last_activity_at' => null]);
        $user2 = User::factory()->create(['last_activity_at' => null]);

        // ACT
        $service->touch($user1);
        $service->touch($user2->id);

        // ASSERT
        expect($service->getLastActivity($user1->id))->not->toBeNull();
        expect($service->getLastActivity($user2->id))->not->toBeNull();
    });

    it('does not write directly to DB when using redis', function () {
        // ARRANGE
        config(['cache.default' => 'redis']);

        Cache::shouldReceive('put')->once();
        Redis::shouldReceive('sadd')->once()->andReturn(1);

        $service = app(UserLastActivityService::class);
        $user = User::factory()->create(['last_activity_at' => null]);

        // ACT
        $service->touch($user);

        // ASSERT
        $user->refresh();
        expect($user->getRawOriginal('last_activity_at'))->toBeNull();
    });
});

describe('getLastActivity', function () {
    it('returns the DB value when not using the redis cache driver', function () {
        // ARRANGE
        $now = Carbon::now()->startOfSecond();
        Carbon::setTestNow($now);

        $service = app(UserLastActivityService::class);
        $user = User::factory()->create(['last_activity_at' => null]);
        $service->touch($user);

        // ACT
        $lastActivity = $service->getLastActivity($user->id);

        // ASSERT
        expect($lastActivity)->not->toBeNull();
        expect($lastActivity->timestamp)->toEqual($now->timestamp);
    });

    it('returns null when the user has no activity', function () {
        // ARRANGE
        $service = app(UserLastActivityService::class);
        $user = User::factory()->create(['last_activity_at' => null]);

        // ACT
        $lastActivity = $service->getLastActivity($user->id);

        // ASSERT
        expect($lastActivity)->toBeNull();
    });
});

describe('countOnline', function () {
    it('counts users active within the specified minutes', function () {
        // ARRANGE
        $now = Carbon::now()->startOfSecond();
        Carbon::setTestNow($now);

        $service = app(UserLastActivityService::class);

        User::factory()->create(['last_activity_at' => $now->clone()->subMinutes(5)]);
        User::factory()->create(['last_activity_at' => $now->clone()->subMinutes(15)]);
        User::factory()->create(['last_activity_at' => null]);

        // ASSERT
        expect($service->countOnline())->toEqual(1);
        expect($service->countOnline(20))->toEqual(2);
    });
});

describe('flushToDatabase', function () {
    it('returns 0 when not using the redis cache driver', function () {
        // ARRANGE
        $service = app(UserLastActivityService::class);

        // ACT
        $flushedCount = $service->flushToDatabase();

        // ASSERT
        expect($flushedCount)->toEqual(0);
    });

    it('bulk updates user records in the DB when using the redis cache driver', function () {
        // ARRANGE
        $now = Carbon::now()->startOfSecond();
        Carbon::setTestNow($now);

        config(['cache.default' => 'redis']);

        $users = User::factory()->count(3)->create(['last_activity_at' => null]);
        $userIds = $users->pluck('id')->map(fn ($id) => (string) $id)->all();

        Cache::shouldReceive('put')->times(3);
        Cache::shouldReceive('pull')
            ->times(3)
            ->andReturn($now->timestamp);

        Redis::shouldReceive('sadd')->times(3)->andReturn(1);
        Redis::shouldReceive('pipeline')
            ->once()
            ->andReturn([$userIds, 1]);

        $service = app(UserLastActivityService::class);

        foreach ($users as $user) {
            $service->touch($user);
        }

        // ACT
        $flushedCount = $service->flushToDatabase();

        // ASSERT
        expect($flushedCount)->toEqual(3);

        foreach ($users as $user) {
            $user->refresh();
            expect($user->getRawOriginal('last_activity_at'))->not->toBeNull();
        }
    });

    it('returns zero when there are no pending users', function () {
        // ARRANGE
        config(['cache.default' => 'redis']);

        Redis::shouldReceive('pipeline')
            ->once()
            ->andReturn([[], 0]);

        $service = app(UserLastActivityService::class);

        // ACT
        $flushedCount = $service->flushToDatabase();

        // ASSERT
        expect($flushedCount)->toEqual(0);
    });
});

describe('User model accessor integration', function () {
    it('transparently returns the last_activity_at value via an accessor', function () {
        // ARRANGE
        $now = Carbon::now()->startOfSecond();
        Carbon::setTestNow($now);

        $service = app(UserLastActivityService::class);
        $user = User::factory()->create(['last_activity_at' => null]);

        // ACT
        $service->touch($user);
        $user->refresh();

        // ASSERT
        expect($user->last_activity_at->timestamp)->toEqual($now->timestamp);
    });
});
