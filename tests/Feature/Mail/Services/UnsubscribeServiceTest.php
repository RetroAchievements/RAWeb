<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Services;

use App\Community\Enums\SubscriptionSubjectType;
use App\Enums\UserPreference;
use App\Mail\Data\CategoryUnsubscribeData;
use App\Mail\Data\GranularUnsubscribeData;
use App\Mail\Services\UnsubscribeService;
use App\Models\Achievement;
use App\Models\ForumTopic;
use App\Models\Game;
use App\Models\Subscription;
use App\Models\System;
use App\Models\User;
use App\Support\Cache\CacheKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UnsubscribeServiceTest extends TestCase
{
    use RefreshDatabase;

    private UnsubscribeService $service;
    private User $user;
    private System $system;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new UnsubscribeService();
        $this->user = User::factory()->create();
        $this->system = System::factory()->create(['ID' => 1, 'Name' => 'NES/Famicom']);
    }

    /**
     * Helper method to create a user with specific notification preferences.
     */
    private function createUserWithPreferences(int $websitePrefs): User
    {
        return User::factory()->create(['websitePrefs' => $websitePrefs]);
    }

    /**
     * Helper method to assert that a subscription exists with the expected state.
     */
    private function assertSubscriptionExists(
        int $userId,
        SubscriptionSubjectType $subjectType,
        int $subjectId,
        bool $state,
    ): void {
        $subscription = Subscription::where('user_id', $userId)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->first();

        $this->assertNotNull($subscription);
        $this->assertEquals($state, $subscription->state);
    }

    /**
     * Helper method to assert that a subscription does not exist.
     */
    private function assertSubscriptionDoesNotExist(
        int $userId,
        SubscriptionSubjectType $subjectType,
        int $subjectId,
    ): void {
        $exists = Subscription::where('user_id', $userId)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->exists();

        $this->assertFalse($exists);
    }

    /**
     * Helper method to check if a user preference bit is set.
     */
    private function assertUserPreferenceBit(User $user, int $preference, bool $shouldBeSet): void
    {
        $user->refresh();
        $isSet = ($user->websitePrefs & (1 << $preference)) !== 0;
        $this->assertEquals($shouldBeSet, $isSet);
    }

    /**
     * Helper method to generate a valid base64 encoded token.
     */
    private function generateValidGranularToken(int $userId, SubscriptionSubjectType $subjectType, int $subjectId): string
    {
        $data = new GranularUnsubscribeData($userId, $subjectType, $subjectId);

        return base64_encode($data->toJson());
    }

    /**
     * Helper method to generate a valid category token.
     */
    private function generateValidCategoryToken(int $userId, int $preference): string
    {
        $data = new CategoryUnsubscribeData($userId, $preference);

        return base64_encode($data->toJson());
    }

    public function testItGeneratesGranularUnsubscribeUrl(): void
    {
        // Arrange
        $forumTopic = ForumTopic::factory()->create();

        // Act
        $url = $this->service->generateGranularUrl(
            $this->user,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id
        );

        // Assert
        $this->assertStringContainsString('/unsubscribe/', $url);
        $this->assertStringContainsString('signature=', $url);
        $this->assertStringContainsString('expires=', $url);

        $parsedUrl = parse_url($url);
        $pathParts = explode('/', $parsedUrl['path']);
        $token = end($pathParts); // !! token is the last part of the path

        $decodedToken = base64_decode($token);
        $tokenData = json_decode($decodedToken, true);

        $this->assertEquals('granular', $tokenData['type']);
        $this->assertEquals($this->user->id, $tokenData['userId']);
        $this->assertEquals(SubscriptionSubjectType::ForumTopic->value, $tokenData['subjectType']);
        $this->assertEquals($forumTopic->id, $tokenData['subjectId']);
    }

    public function testItGeneratesCategoryUnsubscribeUrl(): void
    {
        // Act
        $url = $this->service->generateCategoryUrl(
            $this->user,
            UserPreference::EmailOn_ForumReply // !!
        );

        // Assert
        $this->assertStringContainsString('/unsubscribe/', $url);
        $this->assertStringContainsString('signature=', $url);
        $this->assertStringContainsString('expires=', $url);

        $parsedUrl = parse_url($url);
        $pathParts = explode('/', $parsedUrl['path']);
        $token = end($pathParts);

        $decodedToken = base64_decode($token);
        $tokenData = json_decode($decodedToken, true);

        $this->assertEquals('category', $tokenData['type']);
        $this->assertEquals($this->user->id, $tokenData['userId']);
        $this->assertEquals(UserPreference::EmailOn_ForumReply, $tokenData['preference']);
    }

    public function testItProcessesGranularUnsubscribeForForumTopic(): void
    {
        // Arrange
        $forumTopic = ForumTopic::factory()->create();
        $token = $this->generateValidGranularToken(
            $this->user->id,
            SubscriptionSubjectType::ForumTopic, // !!
            $forumTopic->id
        );

        // Act
        $result = $this->service->processUnsubscribe($token);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('unsubscribeSuccess-forumThread', $result['descriptionKey']);
        $this->assertNotNull($result['undoToken']);
        $this->assertInstanceOf(User::class, $result['user']);

        // ... verify a subscription record was created with state=false ...
        $this->assertSubscriptionExists(
            $this->user->id,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id,
            false // !!
        );
    }

    public function testItProcessesGranularUnsubscribeForGameWall(): void
    {
        // Arrange
        $game = Game::factory()->create([
            'Title' => 'Dragon Quest III',
            'ConsoleID' => $this->system->id,
        ]);
        $token = $this->generateValidGranularToken(
            $this->user->id,
            SubscriptionSubjectType::GameWall, // !!
            $game->id
        );

        // Act
        $result = $this->service->processUnsubscribe($token);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('unsubscribeSuccess-gameWall', $result['descriptionKey']);
        $this->assertEquals(['gameTitle' => 'Dragon Quest III'], $result['descriptionParams']);

        $this->assertSubscriptionExists(
            $this->user->id,
            SubscriptionSubjectType::GameWall,
            $game->id,
            false
        );
    }

    public function testItProcessesGranularUnsubscribeForAchievement(): void
    {
        // Arrange
        $game = Game::factory()->create(['ConsoleID' => $this->system->id]);
        $achievement = Achievement::factory()->create([
            'game_id' => $game->id,
            'title' => 'First Boss Defeated',
        ]);
        $token = $this->generateValidGranularToken(
            $this->user->id,
            SubscriptionSubjectType::Achievement,
            $achievement->id
        );

        // Act
        $result = $this->service->processUnsubscribe($token);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('unsubscribeSuccess-achievement', $result['descriptionKey']);
        $this->assertEquals(['achievementTitle' => 'First Boss Defeated'], $result['descriptionParams']);

        $this->assertSubscriptionExists(
            $this->user->id,
            SubscriptionSubjectType::Achievement,
            $achievement->id,
            false
        );
    }

    public function testItProcessesGranularUnsubscribeForUserWall(): void
    {
        // Arrange
        $targetUser = User::factory()->create(['User' => 'TestUser']);
        $token = $this->generateValidGranularToken(
            $this->user->id,
            SubscriptionSubjectType::UserWall, // !!
            $targetUser->id
        );

        // Act
        $result = $this->service->processUnsubscribe($token);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('unsubscribeSuccess-userWall', $result['descriptionKey']);
        $this->assertEquals(['userName' => 'TestUser'], $result['descriptionParams']);

        $this->assertSubscriptionExists(
            $this->user->id,
            SubscriptionSubjectType::UserWall,
            $targetUser->id,
            false
        );
    }

    public function testItUpdatesExistingSubscriptionWhenUnsubscribing(): void
    {
        // Arrange
        $forumTopic = ForumTopic::factory()->create();

        // ... create an existing subscription with state=true (explicit subscription) ...
        Subscription::create([
            'user_id' => $this->user->id,
            'subject_type' => SubscriptionSubjectType::ForumTopic,
            'subject_id' => $forumTopic->id,
            'state' => true, // !! subscribed
        ]);

        $token = $this->generateValidGranularToken(
            $this->user->id,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id
        );

        // Act
        $result = $this->service->processUnsubscribe($token);

        // Assert
        $this->assertTrue($result['success']);

        // ... verify the subscription record was updated with state=false ...
        $this->assertSubscriptionExists(
            $this->user->id,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id,
            false // !!
        );
    }

    public function testItProcessesCategoryUnsubscribeForForumReplies(): void
    {
        // Arrange
        $initialPrefs = (1 << UserPreference::EmailOn_ForumReply) | (1 << UserPreference::EmailOn_PrivateMessage);
        $user = $this->createUserWithPreferences($initialPrefs); // !! has both preferences enabled

        $token = $this->generateValidCategoryToken(
            $user->id,
            UserPreference::EmailOn_ForumReply
        );

        // Act
        $result = $this->service->processUnsubscribe($token);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('unsubscribeSuccess-allForumReplies', $result['descriptionKey']);

        // ... verify the forum reply bit was turned off ...
        $this->assertUserPreferenceBit($user, UserPreference::EmailOn_ForumReply, false);
        // ... but the private message bit remains on ...
        $this->assertUserPreferenceBit($user, UserPreference::EmailOn_PrivateMessage, true);
    }

    public function testItProcessesCategoryUnsubscribeForPrivateMessages(): void
    {
        // Arrange
        $initialPrefs = (1 << UserPreference::EmailOn_PrivateMessage) | (1 << UserPreference::EmailOn_Followed);
        $user = $this->createUserWithPreferences($initialPrefs);

        $token = $this->generateValidCategoryToken(
            $user->id,
            UserPreference::EmailOn_PrivateMessage // !!
        );

        // Act
        $result = $this->service->processUnsubscribe($token);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('unsubscribeSuccess-allPrivateMessages', $result['descriptionKey']);

        $this->assertUserPreferenceBit($user, UserPreference::EmailOn_PrivateMessage, false);
        $this->assertUserPreferenceBit($user, UserPreference::EmailOn_Followed, true);
    }

    public function testItHandlesInvalidTokenFormat(): void
    {
        // Arrange
        $invalidToken = 'not-a-valid-base64-json';

        // Act
        $result = $this->service->processUnsubscribe($invalidToken);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('invalid_token', $result['errorCode']);
    }

    public function testItHandlesInvalidTokenType(): void
    {
        // Arrange
        $invalidData = json_encode([
            'type' => 'invalid_type', // !!
            'userId' => $this->user->id,
        ]);
        $token = base64_encode($invalidData);

        // Act
        $result = $this->service->processUnsubscribe($token);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('invalid_type', $result['errorCode']);
    }

    public function testItHandlesNonExistentUser(): void
    {
        // Arrange
        $token = $this->generateValidGranularToken(
            99_999_999, // !! unknown user ID
            SubscriptionSubjectType::ForumTopic,
            1
        );

        // Act
        $result = $this->service->processUnsubscribe($token);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('user_not_found', $result['errorCode']);
    }

    public function testItGeneratesUndoToken(): void
    {
        // Arrange
        $data = new GranularUnsubscribeData(
            $this->user->id,
            SubscriptionSubjectType::ForumTopic,
            123
        );

        // Act
        $undoToken = $this->service->generateUndoToken($data);

        // Assert
        $this->assertNotEmpty($undoToken);
        $this->assertEquals(64, strlen($undoToken)); // 32 bytes hex = 64 chars

        // ... verify the token was cached ...
        $cacheKey = CacheKey::buildUnsubscribeUndoTokenCacheKey($undoToken);
        $this->assertTrue(Cache::has($cacheKey));

        $cachedData = Cache::get($cacheKey);
        $this->assertEquals($data->toJson(), $cachedData);
    }

    public function testItProcessesUndoForGranularUnsubscribe(): void
    {
        // Arrange
        $forumTopic = ForumTopic::factory()->create();

        Subscription::create([
            'user_id' => $this->user->id,
            'subject_type' => SubscriptionSubjectType::ForumTopic,
            'subject_id' => $forumTopic->id,
            'state' => false, // !! unsubscribed
        ]);

        $data = new GranularUnsubscribeData(
            $this->user->id,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id
        );
        $undoToken = $this->service->generateUndoToken($data);

        // Act
        $result = $this->service->processUndo($undoToken);

        // Assert
        $this->assertTrue($result['success']);

        $this->assertSubscriptionDoesNotExist(
            $this->user->id,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id
        );

        $cacheKey = CacheKey::buildUnsubscribeUndoTokenCacheKey($undoToken);
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function testItProcessesUndoForCategoryUnsubscribe(): void
    {
        // Arrange
        $initialPrefs = 0; // !! all preferences off
        $user = $this->createUserWithPreferences($initialPrefs);

        $data = new CategoryUnsubscribeData(
            $user->id,
            UserPreference::EmailOn_ForumReply
        );
        $undoToken = $this->service->generateUndoToken($data);

        // Act
        $result = $this->service->processUndo($undoToken);

        // Assert
        $this->assertTrue($result['success']);

        // ... verify the preference bit was turned back on ...
        $this->assertUserPreferenceBit($user, UserPreference::EmailOn_ForumReply, true);

        $cacheKey = CacheKey::buildUnsubscribeUndoTokenCacheKey($undoToken);
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function testItHandlesExpiredUndoToken(): void
    {
        // Arrange
        $expiredToken = bin2hex(random_bytes(32)); // !! token not in cache

        // Act
        $result = $this->service->processUndo($expiredToken);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('undo_expired', $result['errorCode']);
    }

    public function testItHandlesUndoForNonExistentUser(): void
    {
        // Arrange
        $data = new GranularUnsubscribeData(
            99_999_999, // !! unknown user ID
            SubscriptionSubjectType::ForumTopic,
            1
        );
        $undoToken = $this->service->generateUndoToken($data);

        // Act
        $result = $this->service->processUndo($undoToken);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('user_not_found', $result['errorCode']);
    }

    public function testItReturnsCorrectDescriptionsForAllGranularTypes(): void
    {
        // ForumTopic
        $forumToken = $this->generateValidGranularToken(
            $this->user->id,
            SubscriptionSubjectType::ForumTopic, // !!
            1
        );
        $forumResult = $this->service->processUnsubscribe($forumToken);
        $this->assertEquals('unsubscribeSuccess-forumThread', $forumResult['descriptionKey']);

        // GameWall
        $game = Game::factory()->create(['Title' => 'Test Game', 'ConsoleID' => $this->system->id]);
        $gameWallToken = $this->generateValidGranularToken(
            $this->user->id,
            SubscriptionSubjectType::GameWall, // !!
            $game->id
        );
        $gameWallResult = $this->service->processUnsubscribe($gameWallToken);
        $this->assertEquals('unsubscribeSuccess-gameWall', $gameWallResult['descriptionKey']);
        $this->assertEquals(['gameTitle' => 'Test Game'], $gameWallResult['descriptionParams']);

        // Achievement
        $achievement = Achievement::factory()->create([
            'game_id' => $game->id,
            'title' => 'Test Achievement',
        ]);
        $achievementToken = $this->generateValidGranularToken(
            $this->user->id,
            SubscriptionSubjectType::Achievement, // !!
            $achievement->id
        );
        $achievementResult = $this->service->processUnsubscribe($achievementToken);
        $this->assertEquals('unsubscribeSuccess-achievement', $achievementResult['descriptionKey']);
        $this->assertEquals(['achievementTitle' => 'Test Achievement'], $achievementResult['descriptionParams']);

        // UserWall
        $targetUser = User::factory()->create(['User' => 'TargetUser']);
        $userWallToken = $this->generateValidGranularToken(
            $this->user->id,
            SubscriptionSubjectType::UserWall, // !!
            $targetUser->id
        );
        $userWallResult = $this->service->processUnsubscribe($userWallToken);
        $this->assertEquals('unsubscribeSuccess-userWall', $userWallResult['descriptionKey']);
        $this->assertEquals(['userName' => 'TargetUser'], $userWallResult['descriptionParams']);

        // GameTickets
        $gameTicketsToken = $this->generateValidGranularToken(
            $this->user->id,
            SubscriptionSubjectType::GameTickets, // !!
            $game->id
        );
        $gameTicketsResult = $this->service->processUnsubscribe($gameTicketsToken);
        $this->assertEquals('unsubscribeSuccess-gameTickets', $gameTicketsResult['descriptionKey']);
        $this->assertEquals(['gameTitle' => 'Test Game'], $gameTicketsResult['descriptionParams']);

        // GameAchievements
        $gameAchievementsToken = $this->generateValidGranularToken(
            $this->user->id,
            SubscriptionSubjectType::GameAchievements, // !!
            $game->id
        );
        $gameAchievementsResult = $this->service->processUnsubscribe($gameAchievementsToken);
        $this->assertEquals('unsubscribeSuccess-gameAchievements', $gameAchievementsResult['descriptionKey']);
        $this->assertEquals(['gameTitle' => 'Test Game'], $gameAchievementsResult['descriptionParams']);
    }

    public function testItReturnsCorrectDescriptionsForAllCategoryTypes(): void
    {
        $testCases = [
            UserPreference::EmailOn_ActivityComment => 'unsubscribeSuccess-allActivityComments', // !!
            UserPreference::EmailOn_AchievementComment => 'unsubscribeSuccess-allAchievementComments',
            UserPreference::EmailOn_UserWallComment => 'unsubscribeSuccess-allUserWallComments',
            UserPreference::EmailOn_ForumReply => 'unsubscribeSuccess-allForumReplies',
            UserPreference::EmailOn_Followed => 'unsubscribeSuccess-allFollowerNotifications',
            UserPreference::EmailOn_PrivateMessage => 'unsubscribeSuccess-allPrivateMessages',
            UserPreference::EmailOn_TicketActivity => 'unsubscribeSuccess-allTicketActivity',
            UserPreference::EmailOff_DailyDigest => 'unsubscribeSuccess-dailyDigest',
        ];

        foreach ($testCases as $preference => $expectedKey) {
            // For inverted preferences like EmailOff_DailyDigest, start with the bit unset.
            // For regular preferences, start with the bit set.
            $isInverted = $preference === UserPreference::EmailOff_DailyDigest;
            $initialPrefs = $isInverted ? 0 : (1 << $preference);

            $user = $this->createUserWithPreferences($initialPrefs);
            $token = $this->generateValidCategoryToken($user->id, $preference);

            $result = $this->service->processUnsubscribe($token);

            $this->assertTrue($result['success']);
            $this->assertEquals($expectedKey, $result['descriptionKey']);
        }
    }

    public function testItHandlesDeletedEntitiesGracefully(): void
    {
        // Arrange
        $token = $this->generateValidGranularToken(
            $this->user->id,
            SubscriptionSubjectType::GameWall,
            999_999 // !! unknown game ID
        );

        // Act
        $result = $this->service->processUnsubscribe($token);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('unsubscribeSuccess-gameWall', $result['descriptionKey']);
        $this->assertEquals(['gameTitle' => 'Unknown Game'], $result['descriptionParams']); // !! fallback title

        $this->assertSubscriptionExists(
            $this->user->id,
            SubscriptionSubjectType::GameWall,
            999999,
            false
        );
    }

    public function testItPreservesOtherPreferenceBitsWhenUnsubscribingFromCategory(): void
    {
        // Arrange
        $initialPrefs = (1 << UserPreference::EmailOn_ForumReply)
            | (1 << UserPreference::EmailOn_PrivateMessage)
            | (1 << UserPreference::EmailOn_Followed)
            | (1 << UserPreference::Site_SuppressMatureContentWarning);
        $user = $this->createUserWithPreferences($initialPrefs); // !! multiple preferences enabled

        $token = $this->generateValidCategoryToken(
            $user->id,
            UserPreference::EmailOn_ForumReply
        );

        // Act
        $result = $this->service->processUnsubscribe($token);

        // Assert
        $this->assertTrue($result['success']);

        // ... verify only the forum reply bit was turned off ...
        $this->assertUserPreferenceBit($user, UserPreference::EmailOn_ForumReply, false); // !!
        $this->assertUserPreferenceBit($user, UserPreference::EmailOn_PrivateMessage, true);
        $this->assertUserPreferenceBit($user, UserPreference::EmailOn_Followed, true);
        $this->assertUserPreferenceBit($user, UserPreference::Site_SuppressMatureContentWarning, true);
    }

    public function testItCreatesUnsubscribeRecordForImplicitSubscriptions(): void
    {
        // Arrange
        $forumTopic = ForumTopic::factory()->create();

        $this->assertSubscriptionDoesNotExist(
            $this->user->id,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id
        );

        $token = $this->generateValidGranularToken(
            $this->user->id,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id
        );

        // Act
        $result = $this->service->processUnsubscribe($token);

        // Assert
        $this->assertTrue($result['success']);

        // ... verify an explicit unsubscribe record was created ...
        $this->assertSubscriptionExists(
            $this->user->id,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id,
            false // !! explicitly unsubscribed
        );
    }

    public function testUndoRemovesExplicitUnsubscribeRecord(): void
    {
        // Arrange
        $forumTopic = ForumTopic::factory()->create();

        // ... create an explicit unsubscribe record ...
        Subscription::create([
            'user_id' => $this->user->id,
            'subject_type' => SubscriptionSubjectType::ForumTopic,
            'subject_id' => $forumTopic->id,
            'state' => false, // !! unsubscribed
        ]);

        $data = new GranularUnsubscribeData(
            $this->user->id,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id
        );
        $undoToken = $this->service->generateUndoToken($data);

        // Act
        $result = $this->service->processUndo($undoToken);

        // Assert
        $this->assertTrue($result['success']);

        // ... verify the record was completely removed (reverting to implicit subscription state) ...
        $this->assertSubscriptionDoesNotExist(
            $this->user->id,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id
        );
    }

    public function testUndoRestoresExplicitSubscriptionAfterUnsubscribe(): void
    {
        // Arrange
        $forumTopic = ForumTopic::factory()->create();

        // ... create an explicit subscription for the user ...
        Subscription::create([
            'user_id' => $this->user->id,
            'subject_type' => SubscriptionSubjectType::ForumTopic,
            'subject_id' => $forumTopic->id,
            'state' => true, // !! explicitly subscribed
        ]);

        // Act
        // ... unsubscribe ...
        $unsubscribeToken = $this->generateValidGranularToken(
            $this->user->id,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id
        );
        $unsubscribeResult = $this->service->processUnsubscribe($unsubscribeToken);

        // ... verify the unsubscribe worked ...
        $this->assertTrue($unsubscribeResult['success']);
        $this->assertSubscriptionExists(
            $this->user->id,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id,
            false // !! now unsubscribed
        );

        // ... undo ...
        $undoToken = $unsubscribeResult['undoToken'];
        $undoResult = $this->service->processUndo($undoToken);

        // Assert
        $this->assertTrue($undoResult['success']);
        $this->assertSubscriptionExists(
            $this->user->id,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id,
            true // !! explicitly subscribed again
        );
    }

    public function testItProcessesUnsubscribeWithoutGeneratingUndoToken(): void
    {
        // Arrange
        $forumTopic = ForumTopic::factory()->create();
        $token = $this->generateValidGranularToken(
            $this->user->id,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id
        );

        // Act
        $result = $this->service->processUnsubscribe(
            $token,
            shouldGenerateUndoToken: false // !!
        );

        // Assert
        $this->assertTrue($result['success']);
        $this->assertNull($result['undoToken']); // !! undo token should be null
        $this->assertEquals('unsubscribeSuccess-forumThread', $result['descriptionKey']);

        // ... verify the unsubscribe still worked ...
        $this->assertSubscriptionExists(
            $this->user->id,
            SubscriptionSubjectType::ForumTopic,
            $forumTopic->id,
            false
        );
    }

    public function testItProcessesCategoryUnsubscribeWithoutGeneratingUndoToken(): void
    {
        // Arrange
        $initialPrefs = (1 << UserPreference::EmailOn_ForumReply);
        $user = $this->createUserWithPreferences($initialPrefs);
        $token = $this->generateValidCategoryToken(
            $user->id,
            UserPreference::EmailOn_ForumReply
        );

        // Act
        $result = $this->service->processUnsubscribe(
            $token,
            shouldGenerateUndoToken: false // !!
        );

        // Assert
        $this->assertTrue($result['success']);
        $this->assertNull($result['undoToken']); // !! undo token should be null
        $this->assertEquals('unsubscribeSuccess-allForumReplies', $result['descriptionKey']);

        // ... verify the unsubscribe still worked ...
        $this->assertUserPreferenceBit($user, UserPreference::EmailOn_ForumReply, false);
    }

    public function testItProcessesCategoryUnsubscribeForDailyDigest(): void
    {
        // Arrange
        $initialPrefs = 0; // unset because this is an inverted preference
        $user = $this->createUserWithPreferences($initialPrefs);

        $token = $this->generateValidCategoryToken(
            $user->id,
            UserPreference::EmailOff_DailyDigest
        );

        // Act
        $result = $this->service->processUnsubscribe($token);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('unsubscribeSuccess-dailyDigest', $result['descriptionKey']);

        // ... for inverted preferences, unsubscribing means setting the bit ...
        $this->assertUserPreferenceBit($user, UserPreference::EmailOff_DailyDigest, true);
    }

    public function testItProcessesUndoForDailyDigestCategoryUnsubscribe(): void
    {
        // Arrange
        $initialPrefs = (1 << UserPreference::EmailOff_DailyDigest); // start with the bit set
        $user = $this->createUserWithPreferences($initialPrefs);

        $data = new CategoryUnsubscribeData(
            $user->id,
            UserPreference::EmailOff_DailyDigest
        );
        $undoToken = $this->service->generateUndoToken($data);

        // Act
        $result = $this->service->processUndo($undoToken);

        // Assert
        $this->assertTrue($result['success']);

        // ... for inverted preferences, undo means clearing the bit ...
        $this->assertUserPreferenceBit($user, UserPreference::EmailOff_DailyDigest, false);

        $cacheKey = CacheKey::buildUnsubscribeUndoTokenCacheKey($undoToken);
        $this->assertFalse(Cache::has($cacheKey));
    }
}
