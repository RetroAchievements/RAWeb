<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Services;

use App\Community\Enums\UserRelationship;
use App\Models\User;
use App\Models\UserRelation;
use App\Platform\Enums\PlayerStatType;
use App\Platform\Services\FollowedUserLeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowedUserLeaderboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testItReturnsTheCorrectStructure(): void
    {
        // Arrange
        $user = User::factory()->create();
        $followedUser = User::factory()->create();
        UserRelation::create([
            'user_id' => $user->id,
            'User' => $user->User,
            'related_user_id' => $followedUser->id,
            'Friend' => $followedUser->User,
            'Friendship' => UserRelationship::Following,
        ]);

        // Act
        $service = new FollowedUserLeaderboardService();
        $followedUserStats = $service->buildFollowedUserStats($user);

        // Assert
        $this->assertArrayHasKey('statsDaily', $followedUserStats);
        $this->assertArrayHasKey('statsWeekly', $followedUserStats);
        $this->assertArrayHasKey('statsAllTime', $followedUserStats);
    }

    public function testItReturnsEmptyIfNoFollowedUsers(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $service = new FollowedUserLeaderboardService();
        $followedUserStats = $service->buildFollowedUserStats($user);

        // Assert
        $this->assertEmpty($followedUserStats['statsDaily']);
        $this->assertEmpty($followedUserStats['statsWeekly']);
    }

    public function testItBuildsFollowedUserStats(): void
    {
        // Arrange
        $user = User::factory()->create(['RAPoints' => 125]);
        $followedUsers = User::factory()->count(3)->create();

        // Have $user follow all the $followedUsers.
        foreach ($followedUsers as $followedUser) {
            UserRelation::create([
                'user_id' => $user->id,
                'User' => $user->User,
                'related_user_id' => $followedUser->id,
                'Friend' => $followedUser->User,
                'Friendship' => UserRelationship::Following,
            ]);
        }

        // Set up some stats.
        $stats = [
            ['day' => 50, 'week' => 70, 'alltime' => 150],
            ['day' => 30, 'week' => 40, 'alltime' => 120],
            ['day' => 10, 'week' => 120, 'alltime' => 130],
        ];
        foreach ($followedUsers as $index => $followedUser) {
            $followedUser->playerStats()->create([
                'type' => PlayerStatType::PointsHardcoreDay,
                'value' => $stats[$index]['day'],
            ]);
            $followedUser->playerStats()->create([
                'type' => PlayerStatType::PointsHardcoreWeek,
                'value' => $stats[$index]['week'],
            ]);
            $followedUser->update([
                'RAPoints' => $stats[$index]['alltime'],
            ]);
        }

        // Act
        $service = new FollowedUserLeaderboardService();
        $followedUserStats = $service->buildFollowedUserStats($user);

        // Assert
        $sortedDayPoints = array_column($followedUserStats['statsDaily'], 'points_hardcore');
        $sortedWeekPoints = array_column($followedUserStats['statsWeekly'], 'points_hardcore');
        $sortedAllTimePoints = array_column($followedUserStats['statsAllTime'], 'points_hardcore');

        $this->assertEquals([50, 30, 10], $sortedDayPoints);
        $this->assertEquals([120, 70, 40], $sortedWeekPoints);
        $this->assertEquals([150, 130, 125, 120], $sortedAllTimePoints);
    }
}
