<?php

declare(strict_types=1);

use App\Models\User;
use App\Platform\Services\UserLastActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('touch', function () {
    it('records activity in the cache and tracks users for the flush-to-DB', function () {
        // ARRANGE
        $now = Carbon::now()->startOfSecond();
        Carbon::setTestNow($now);

        $service = app(UserLastActivityService::class);
        $user = User::factory()->create(['last_activity_at' => null]);

        // ACT
        $service->touch($user);

        // ASSERT
        // ... activity should be retrievable from the cache ...
        $lastActivity = $service->getLastActivity($user->id);
        expect($lastActivity)->not->toBeNull();
        expect($lastActivity->timestamp)->toEqual($now->timestamp);

        // ... the user should be tracked in a pending list for the flush ...
        $flushedCount = $service->flushToDatabase();
        expect($flushedCount)->toEqual(1);

        // ... the db should now have the timestamp ...
        $user->refresh();
        $dbTimestamp = $user->getRawOriginal('last_activity_at');
        expect($dbTimestamp)->not->toBeNull();
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
});

describe('getLastActivity', function () {
    it('returns a cached value when one is present', function () {
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

    it('falls back to the DB when cache is empty (or down)', function () {
        // ARRANGE
        $pastTime = Carbon::now()->subHours(2)->startOfSecond();
        $service = app(UserLastActivityService::class);

        $user = User::factory()->create();
        $user->forceFill(['last_activity_at' => $pastTime])->saveQuietly();

        // ACT
        $lastActivity = $service->getLastActivity($user->id);

        // ASSERT
        expect($lastActivity->timestamp)->toEqual($pastTime->timestamp);
    });

    it('still returns null when the user has no activity', function () {
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
    it('bulk updates the DB and clears the cache', function () {
        // ARRANGE
        $now = Carbon::now()->startOfSecond();
        Carbon::setTestNow($now);

        $service = app(UserLastActivityService::class);
        $users = User::factory()->count(3)->create(['last_activity_at' => null]);

        foreach ($users as $user) {
            $service->touch($user);
        }

        // ACT
        $flushedCount = $service->flushToDatabase();

        // ASSERT
        expect($flushedCount)->toEqual(3);

        foreach ($users as $user) {
            $user->refresh();
            $dbTimestamp = $user->getRawOriginal('last_activity_at');
            expect($dbTimestamp)->not->toBeNull();
        }

        // ... a flush returning 0 indicates the cache is cleared ...
        expect($service->flushToDatabase())->toEqual(0);
    });

    it('returns zero when there is nothing to flush', function () {
        // ARRANGE
        $service = app(UserLastActivityService::class);

        // ACT
        $flushedCount = $service->flushToDatabase();

        // ASSERT
        expect($flushedCount)->toEqual(0);
    });
});

describe('User model accessor integration', function () {
    it('transparently returns the cached or DB value via an accessor', function () {
        // ARRANGE
        $now = Carbon::now()->startOfSecond();
        Carbon::setTestNow($now);

        $service = app(UserLastActivityService::class);
        $user = User::factory()->create(['last_activity_at' => null]);

        // ACT
        $service->touch($user);

        // ASSERT
        // ... accessor should return the cached value ...
        expect($user->last_activity_at->timestamp)->toEqual($now->timestamp);

        // ... after a flush, the accessor should return the DB value ...
        $service->flushToDatabase();
        $user->refresh();
        expect($user->last_activity_at->timestamp)->toEqual($now->timestamp);
    });
});
