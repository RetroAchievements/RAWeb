<?php

declare(strict_types=1);

namespace Tests\Feature\Legacy;

use App\Site\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function testLegacyDbStatement(): void
    {
        User::factory()->count(3)->create();

        legacyDbStatement('UPDATE UserAccounts SET User = ? WHERE ID = ?', ['John', 1]);
        /** @var User $user */
        $user = User::find(1);
        $this->assertSame('John', $user->User);

        legacyDbStatement('DELETE FROM UserAccounts WHERE ID = ?', [3]);
        $this->assertSame(2, User::count());

        User::find(2)->delete();
        $this->assertSame(1, User::count());
    }

    public function testLegacyDbFetchReturnsNullIfNothingWasFound(): void
    {
        $result = legacyDbFetch('SELECT * FROM UserAccounts WHERE ID = 1');
        $this->assertNull($result);
    }

    public function testLegacyDbFetchReturnsAssociativeArray(): void
    {
        User::factory()->createOne();
        $result = legacyDbFetch('SELECT * FROM UserAccounts WHERE ID = 1');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('ID', $result);
    }

    public function testLegacyDbFetchAllReturnsEmptyCollectionIfNothingWasFound(): void
    {
        $result = legacyDbFetchAll('SELECT * FROM UserAccounts');
        $this->assertIsIterable($result);
        $this->assertEmpty($result);
    }

    public function testLegacyDbFetchAll(): void
    {
        User::factory()->count(3)->create();
        $result = legacyDbFetchAll('SELECT * FROM UserAccounts');
        $this->assertIsIterable($result);
        $this->assertCount(3, $result);
        $this->assertIsIterable($result[0]);
        $this->assertArrayHasKey('ID', $result[0]);
    }

    public function testSqliteDiffStatements(): void
    {
        User::factory()->create([
            // active for 10 minutes
            'Created' => Carbon::now()->subMinutes(10),
            // 10 minutes remaining
            'Updated' => Carbon::now()->addMonths(3),
        ]);

        $passed = diffMinutesPassedStatement('Created', 'MinutesPassed');
        $remaining = diffMinutesRemainingStatement('Updated', 'MinutesRemaining');

        $result = legacyDbFetch("
            SELECT
                User,
                Created,
                Updated,
                $remaining,
                $passed
            FROM UserAccounts u
            LIMIT 1
        ");

        $this->assertEquals(10, $result['MinutesPassed']);
        $this->assertEquals(Carbon::now()->addMonths(3)->diffInRealMinutes(Carbon::now()), $result['MinutesRemaining']);
    }
}
