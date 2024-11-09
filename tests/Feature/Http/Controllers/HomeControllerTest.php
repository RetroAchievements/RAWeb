<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\AchievementSetClaim;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\Game;
use App\Models\News;
use App\Models\StaticData;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $logPath;
    protected string $backupLogPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logPath = storage_path('logs/playersonline.log');
        $this->backupLogPath = storage_path('logs/playersonline_backup.log');

        // Rename the log file if it exists.
        if (file_exists($this->logPath)) {
            rename($this->logPath, $this->backupLogPath);
        }
    }

    protected function tearDown(): void
    {
        // Restore the original log file.
        if (file_exists($this->backupLogPath)) {
            rename($this->backupLogPath, $this->logPath);
        }

        parent::tearDown();
    }

    public function testItRendersWithEmptyDatabase(): void
    {
        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertOk();
    }

    public function testItCorrectlySendsStaticDataProps(): void
    {
        // Arrange
        $staticData = StaticData::factory()->create();

        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('staticData.numGames', $staticData->NumGames)
            ->where('staticData.numAchievements', $staticData->NumAchievements)
            ->where('staticData.numHardcoreMasteryAwards', $staticData->num_hardcore_mastery_awards)
            ->where('staticData.numHardcoreGameBeatenAwards', $staticData->num_hardcore_game_beaten_awards)
            ->where('staticData.numRegisteredUsers', $staticData->NumRegisteredUsers)
            ->where('staticData.numAwarded', $staticData->NumAwarded)
            ->where('staticData.totalPointsEarned', $staticData->TotalPointsEarned)
        );
    }

    public function testItCorrectlySendsMostRecentMasteryProps(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
        ]);
        $player = User::factory()->create();

        StaticData::factory()->create([
            'last_game_hardcore_mastered_game_id' => $game->id,
            'last_game_hardcore_mastered_user_id' => $player->id,
        ]);

        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('mostRecentGameMastered.game.id', $game->id)
            ->where('mostRecentGameMastered.game.title', $game->title)
            ->where('mostRecentGameMastered.game.badgeUrl', $game->badgeUrl)

            ->where('mostRecentGameMastered.game.system.name', $system->name)
            ->where('mostRecentGameMastered.game.system.iconUrl', $system->iconUrl)

            ->where('mostRecentGameMastered.user.displayName', $player->username)
            ->where('mostRecentGameMastered.user.avatarUrl', $player->avatarUrl)
        );
    }

    public function testItCorrectlySendsMostRecentGameBeatenProps(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
        ]);
        $player = User::factory()->create();

        StaticData::factory()->create([
            'last_game_hardcore_beaten_game_id' => $game->id,
            'last_game_hardcore_beaten_user_id' => $player->id,
        ]);

        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('mostRecentGameBeaten.game.id', $game->id)
            ->where('mostRecentGameBeaten.game.title', $game->title)
            ->where('mostRecentGameBeaten.game.badgeUrl', $game->badgeUrl)

            ->where('mostRecentGameBeaten.game.system.name', $system->name)
            ->where('mostRecentGameBeaten.game.system.iconUrl', $system->iconUrl)

            ->where('mostRecentGameBeaten.user.displayName', $player->username)
            ->where('mostRecentGameBeaten.user.avatarUrl', $player->avatarUrl)
        );
    }

    public function testItCorrectlySendsAchievementOfTheWeekProps(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
        ]);

        // TODO use event achievements
        $achievement = Achievement::factory()->create([
            'ID' => 9,
            'Title' => 'That Was Easy',
            'GameID' => $game->id,
        ]);

        StaticData::factory()->create([
            'Event_AOTW_AchievementID' => $achievement->id, // TODO use event achievements
            'Event_AOTW_ForumID' => 14029,
        ]);

        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('achievementOfTheWeek.id', $achievement->id)
            ->where('achievementOfTheWeek.title', $achievement->title)
            ->where('achievementOfTheWeek.description', $achievement->description)

            ->where('achievementOfTheWeek.game.id', $game->id)
            ->where('achievementOfTheWeek.game.title', $game->title)
            ->where('achievementOfTheWeek.game.badgeUrl', $game->badgeUrl)

            ->where('achievementOfTheWeek.game.system.name', $system->name)
            ->where('achievementOfTheWeek.game.system.iconUrl', $system->iconUrl)

            ->where('staticData.eventAotwForumId', 14029)
        );
    }

    public function testItCorrectlySendsRecentNewsProps(): void
    {
        // Arrange
        $authorOne = User::factory()->create();
        $authorTwo = User::factory()->create();
        $authorThree = User::factory()->create();

        News::factory()->create(['user_id' => $authorOne->id]);
        News::factory()->create(['user_id' => $authorTwo->id]);
        News::factory()->create(['user_id' => $authorThree->id]);

        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->has('recentNews', 3)
        );
    }

    public function testItReturnsAnEmptyCollectionForCompletedClaimsWhenThereAreNone(): void
    {
        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->has('completedClaims', 0)
        );
    }

    public function testItSendsSingleCompletedClaimCorrectly(): void
    {
        // Arrange
        $system = System::factory()->create(['active' => true]);
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'title' => 'Sonic the Hedgehog',
        ]);
        $user = User::factory()->create([
            'User' => 'Scott',
        ]);

        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'ClaimType' => ClaimType::Primary,
            'SetType' => ClaimSetType::NewSet,
            'Status' => ClaimStatus::Complete,
            'Finished' => now(),
            'Created' => now()->subDay(),
        ]);

        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->has('completedClaims', 1)

            ->where('completedClaims.0.setType', ClaimSetType::NewSet)

            ->where('completedClaims.0.game.id', $game->id)
            ->where('completedClaims.0.game.title', $game->title)
            ->where('completedClaims.0.game.badgeUrl', $game->badge_url)
            ->where('completedClaims.0.game.system.name', $system->name)

            ->where('completedClaims.0.users.0.displayName', $user->display_name)
            ->where('completedClaims.0.users.0.avatarUrl', $user->avatar_url)
        );
    }

    public function testItKeepsOnlyOldestCompletedClaimForDuplicateUserGamePairs(): void
    {
        // Arrange
        $system = System::factory()->create(['active' => true]);
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'title' => 'Sonic the Hedgehog',
        ]);
        $user = User::factory()->create([
            'User' => 'Scott',
        ]);

        // Create two claims by the same user for the same game.
        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'ClaimType' => ClaimType::Primary,
            'SetType' => ClaimSetType::NewSet,
            'Status' => ClaimStatus::Complete,
            'Finished' => now()->subDays(2), // older claim
            'Created' => now()->subDays(3),
        ]);

        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'ClaimType' => ClaimType::Primary,
            'SetTYpe' => ClaimSetType::NewSet,
            'Status' => ClaimStatus::Complete,
            'Finished' => now()->subDay(), // newer claim
            'Created' => now()->subDays(2),
        ]);

        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->has('completedClaims', 1)

            ->where('completedClaims.0.setType', ClaimSetType::NewSet)

            ->where('completedClaims.0.game.id', $game->id)
            ->where('completedClaims.0.game.title', $game->title)
            ->where('completedClaims.0.game.badgeUrl', $game->badge_url)
            ->where('completedClaims.0.game.system.name', $system->name)

            ->where('completedClaims.0.users.0.displayName', $user->display_name)
            ->where('completedClaims.0.users.0.avatarUrl', $user->avatar_url)
        );
    }

    public function testItGroupsCollaborativeCompletedClaims(): void
    {
        // Arrange
        $system = System::factory()->create(['active' => true]);
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'title' => 'Sonic the Hedgehog',
        ]);
        $userOne = User::factory()->create([
            'User' => 'Scott',
        ]);
        $userTwo = User::factory()->create([
            'User' => 'SporyTike',
        ]);

        // Both users claim the same game.
        AchievementSetClaim::factory()->create([
            'user_id' => $userOne->id,
            'game_id' => $game->id,
            'ClaimType' => ClaimType::Primary,
            'Status' => ClaimStatus::Complete,
            'Finished' => now()->subHour(),
            'Created' => now()->subHours(3),
        ]);
        AchievementSetClaim::factory()->create([
            'user_id' => $userTwo->id,
            'game_id' => $game->id,
            'ClaimType' => ClaimType::Collaboration,
            'Status' => ClaimStatus::Complete,
            'Finished' => now()->subHours(2),
            'Created' => now()->subDay(),
        ]);

        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->has('completedClaims', 1)

            ->where('completedClaims.0.game.id', $game->id)
            ->where('completedClaims.0.game.title', $game->title)
            ->where('completedClaims.0.game.badgeUrl', $game->badge_url)

            ->where('completedClaims.0.game.system.name', $system->name)

            ->has('completedClaims.0.users', 2)

            ->where('completedClaims.0.users.0.displayName', $userOne->display_name)
            ->where('completedClaims.0.users.0.avatarUrl', $userOne->avatar_url)
            ->where('completedClaims.0.users.1.displayName', $userTwo->display_name)
            ->where('completedClaims.0.users.1.avatarUrl', $userTwo->avatar_url)
        );
    }

    public function testItReturnsEmptyCurrentlyOnlineDataWhenLogFileDoesNotExist(): void
    {
        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('currentlyOnline.logEntries', array_fill(0, 48, 0))
            ->where('currentlyOnline.numCurrentPlayers', 0)
            ->where('currentlyOnline.allTimeHighPlayers', 0)
            ->where('currentlyOnline.allTimeHighDate', null)
        );
    }

    public function testItCorectlyHandlesRealLogFileData(): void
    {
        // Arrange
        $logEntries = [
            2487, 2335, 2193, 1963, 1869, 1765, 1676, 1531, 1538, 1583, 1555, 1579,
            1636, 1807, 1881, 2007, 2097, 2222, 2437, 2458, 2534, 2536, 2679, 2731,
            2838, 2803, 2862, 2913, 2998, 3037, 3041, 3031, 3063, 3084, 2996, 2956,
            2914, 2845, 2945, 2882, 2800, 2750, 2666, 2508, 2331, 2177, 2022, 1873,
        ];
        file_put_contents($this->logPath, implode("\n", $logEntries));

        User::factory()->count(3)->create(['LastLogin' => now()->subMinutes(5)]);

        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('currentlyOnline.logEntries', $logEntries)
            ->where('currentlyOnline.numCurrentPlayers', 3)
            ->where('currentlyOnline.allTimeHighPlayers', max($logEntries))
            ->has('currentlyOnline.allTimeHighDate')
        );
    }

    public function testItReturnsAnEmptyCollectionForNewClaimsWhenThereAreNone(): void
    {
        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->has('newClaims', 0)
        );
    }

    public function testItSendsSingleNewClaimCorrectly(): void
    {
        // Arrange
        $system = System::factory()->create(['active' => true]);
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'title' => 'Sonic the Hedgehog',
        ]);
        $user = User::factory()->create([
            'User' => 'Scott',
        ]);

        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'ClaimType' => ClaimType::Primary,
            'SetType' => ClaimSetType::NewSet,
            'Status' => ClaimStatus::Active,
            'Finished' => now(),
            'Created' => now()->subDay(),
        ]);

        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->has('completedClaims', 0)
            ->has('newClaims', 1)

            ->where('newClaims.0.setType', ClaimSetType::NewSet)

            ->where('newClaims.0.game.id', $game->id)
            ->where('newClaims.0.game.title', $game->title)
            ->where('newClaims.0.game.badgeUrl', $game->badge_url)
            ->where('newClaims.0.game.system.name', $system->name)

            ->where('newClaims.0.users.0.displayName', $user->display_name)
            ->where('newClaims.0.users.0.avatarUrl', $user->avatar_url)
        );
    }

    public function testItKeepsOnlyOldestNewClaimForDuplicateUserGamePairs(): void
    {
        // Arrange
        $system = System::factory()->create(['active' => true]);
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'title' => 'Sonic the Hedgehog',
        ]);
        $user = User::factory()->create([
            'User' => 'Scott',
        ]);

        // Create two claims by the same user for the same game.
        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'ClaimType' => ClaimType::Primary,
            'SetType' => ClaimSetType::NewSet,
            'Status' => ClaimStatus::Active,
            'Finished' => now()->subDays(2), // older claim
            'Created' => now()->subDays(3),
        ]);

        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'ClaimType' => ClaimType::Primary,
            'SetTYpe' => ClaimSetType::NewSet,
            'Status' => ClaimStatus::Active,
            'Finished' => now()->subDay(), // newer claim
            'Created' => now()->subDays(2),
        ]);

        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->has('newClaims', 1)

            ->where('newClaims.0.setType', ClaimSetType::NewSet)

            ->where('newClaims.0.game.id', $game->id)
            ->where('newClaims.0.game.title', $game->title)
            ->where('newClaims.0.game.badgeUrl', $game->badge_url)
            ->where('newClaims.0.game.system.name', $system->name)

            ->where('newClaims.0.users.0.displayName', $user->display_name)
            ->where('newClaims.0.users.0.avatarUrl', $user->avatar_url)
        );
    }

    public function testItGroupsCollaborativeNewClaims(): void
    {
        // Arrange
        $system = System::factory()->create(['active' => true]);
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'title' => 'Sonic the Hedgehog',
        ]);
        $userOne = User::factory()->create([
            'User' => 'Scott',
        ]);
        $userTwo = User::factory()->create([
            'User' => 'SporyTike',
        ]);

        // Both users claim the same game.
        AchievementSetClaim::factory()->create([
            'user_id' => $userOne->id,
            'game_id' => $game->id,
            'ClaimType' => ClaimType::Primary,
            'Status' => ClaimStatus::Active,
            'Finished' => now()->subHour(),
            'Created' => now()->subHours(3),
        ]);
        AchievementSetClaim::factory()->create([
            'user_id' => $userTwo->id,
            'game_id' => $game->id,
            'ClaimType' => ClaimType::Collaboration,
            'Status' => ClaimStatus::Active,
            'Finished' => now()->subHours(2),
            'Created' => now()->subDay(),
        ]);

        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->has('newClaims', 1)

            ->where('newClaims.0.game.id', $game->id)
            ->where('newClaims.0.game.title', $game->title)
            ->where('newClaims.0.game.badgeUrl', $game->badge_url)

            ->where('newClaims.0.game.system.name', $system->name)

            ->has('newClaims.0.users', 2)

            ->where('newClaims.0.users.0.displayName', $userOne->display_name)
            ->where('newClaims.0.users.0.avatarUrl', $userOne->avatar_url)
            ->where('newClaims.0.users.1.displayName', $userTwo->display_name)
            ->where('newClaims.0.users.1.avatarUrl', $userTwo->avatar_url)
        );
    }

    public function testItReturnsAnEmptyCollectionForForumPostsWhenThereAreNone(): void
    {
        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->has('recentForumPosts', 0)
        );
    }

    public function testItSendsSingleForumPostCorrectly(): void
    {
        // Arrange
        $user = User::factory()->create(['User' => 'Scott']);

        $topic = ForumTopic::factory()->create([
            'Title' => 'Test Topic',
            'RequiredPermissions' => Permissions::Unregistered,
        ]);

        $comment = ForumTopicComment::factory()->create([
            'ForumTopicID' => $topic->id,
            'author_id' => $user->id,
            'Authorised' => true,
            'Payload' => 'This is a test forum post with enough content to test truncation This is a test forum post with enough content to test truncation This is a test forum post with enough content to test truncation This is a test forum post with enough content to test truncation.',
        ]);

        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->has('recentForumPosts', 1)

            ->where('recentForumPosts.0.id', $topic->id)
            ->where('recentForumPosts.0.title', $topic->title)

            ->where('recentForumPosts.0.commentCount24h', null)
            ->where('recentForumPosts.0.commentCount7d', null)

            ->where('recentForumPosts.0.latestComment.id', $comment->id)
            ->where('recentForumPosts.0.latestComment.body', 'This is a test forum post with enough content to test truncation This is a test forum post with enou...')
        );
    }

    public function testItFiltersForumPostsByUserPermissions(): void
    {
        // Arrange
        $user = User::factory()->create(['User' => 'Scott']);

        $publicTopic = ForumTopic::factory()->create([
            'RequiredPermissions' => Permissions::Unregistered,
        ]);
        ForumTopicComment::factory()->create([
            'ForumTopicID' => $publicTopic->id,
            'author_id' => $user->id,
            'Authorised' => true,
        ]);

        $privateTopic = ForumTopic::factory()->create([
            'RequiredPermissions' => Permissions::Moderator,
        ]);
        ForumTopicComment::factory()->create([
            'ForumTopicID' => $privateTopic->id,
            'author_id' => $user->id,
            'Authorised' => true,
        ]);

        // Act
        $response = $this->get(route('demo.home')); // we're currently an unregistered guest

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->has('recentForumPosts', 1)
            ->where('recentForumPosts.0.id', $publicTopic->id)
        );
    }

    public function testItFiltersUnauthorizedForumPosts(): void
    {
        // Arrange
        $user = User::factory()->create(['User' => 'Scott']);

        $topic = ForumTopic::factory()->create([
            'RequiredPermissions' => Permissions::Unregistered,
        ]);
        ForumTopicComment::factory()->create([
            'ForumTopicID' => $topic->id,
            'author_id' => $user->id,
            'Authorised' => false, // !!
        ]);

        // Act
        $response = $this->get(route('demo.home'));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->has('recentForumPosts', 0)
        );
    }
}
