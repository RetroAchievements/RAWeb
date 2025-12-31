<?php

declare(strict_types=1);

namespace Tests\Feature\Legacy;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function testLegacyDbStatement(): void
    {
        User::factory()->count(3)->create();

        legacyDbStatement('UPDATE users SET username = ? WHERE id = ?', ['John', 1]);
        /** @var User $user */
        $user = User::find(1);
        $this->assertSame('John', $user->username);

        legacyDbStatement('DELETE FROM users WHERE id = ?', [3]);
        $this->assertSame(2, User::count());

        User::find(2)->delete();
        $this->assertSame(1, User::count());
    }

    public function testLegacyDbFetchReturnsNullIfNothingWasFound(): void
    {
        $result = legacyDbFetch('SELECT * FROM users WHERE id = 1');
        $this->assertNull($result);
    }

    public function testLegacyDbFetchReturnsAssociativeArray(): void
    {
        User::factory()->createOne();
        $result = legacyDbFetch('SELECT * FROM users WHERE id = 1');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }

    public function testLegacyDbFetchAllReturnsEmptyCollectionIfNothingWasFound(): void
    {
        $result = legacyDbFetchAll('SELECT * FROM users');
        $this->assertTrue($result->isEmpty());
    }

    public function testLegacyDbFetchAll(): void
    {
        User::factory()->count(3)->create();
        $result = legacyDbFetchAll('SELECT * FROM users');
        $this->assertCount(3, $result);
        $this->assertIsIterable($result[0]);
        $this->assertArrayHasKey('id', $result[0]);
    }

    public function testSqliteDiffStatements(): void
    {
        // Freeze time
        Carbon::setTestNow(Carbon::now()->startOfSecond());

        User::factory()->create([
            // active for 10 minutes
            'created_at' => Carbon::now()->subMinutes(10),
            // 10 minutes remaining
            'updated_at' => Carbon::now()->addMonths(3),
        ]);

        $passed = diffMinutesPassedStatement('created_at', 'MinutesPassed');
        $remaining = diffMinutesRemainingStatement('updated_at', 'MinutesRemaining');

        $result = legacyDbFetch("
            SELECT
                username,
                created_at,
                updated_at,
                $remaining,
                $passed
            FROM users u
            LIMIT 1
        ");

        $this->assertEquals(10, $result['MinutesPassed']);
        $this->assertEquals(Carbon::now()->addMonths(3)->diffInMinutes(Carbon::now(), true), $result['MinutesRemaining']);
    }
}
