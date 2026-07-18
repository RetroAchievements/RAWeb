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

        $row = User::withTrashed()
            ->selectRaw("username, created_at, updated_at, $remaining, $passed")
            ->limit(1)
            ->toBase()
            ->first();
        $result = (array) $row;

        $this->assertEquals(10, $result['MinutesPassed']);
        $this->assertEquals(Carbon::now()->addMonths(3)->diffInMinutes(Carbon::now(), true), $result['MinutesRemaining']);
    }
}
