<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Actions;

use App\Community\Actions\SendDailyDigestAction;
use App\Community\Enums\CommentableType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Jobs\SendDailyDigestJob;
use App\Mail\DailyDigestMail;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\System;
use App\Models\User;
use App\Models\UserDelayedSubscription;
use App\Platform\Enums\GameScreenshotRejectionReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendDailyDigestActionTest extends TestCase
{
    use RefreshDatabase;

    public function testHandlesDeletedFirstCommentByFindingNextOne(): void
    {
        // Arrange
        Mail::fake();

        $subscriber = User::factory()->create([
            'email' => 'test@example.com',
            'last_activity_at' => now()->subDays(1),
        ]);
        $commenter = User::factory()->create();

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        // ... create two comments, then delete the first one ...
        $firstComment = Comment::factory()->create([
            'commentable_type' => CommentableType::Achievement,
            'commentable_id' => $achievement->id,
            'user_id' => $commenter->id,
        ]);
        $secondComment = Comment::factory()->create([
            'commentable_type' => CommentableType::Achievement,
            'commentable_id' => $achievement->id,
            'user_id' => $commenter->id,
        ]);

        // ... the subscription points to the first comment ...
        $firstCommentId = $firstComment->id;
        $firstComment->delete(); // !!

        UserDelayedSubscription::create([
            'user_id' => $subscriber->id,
            'subject_type' => SubscriptionSubjectType::Achievement,
            'subject_id' => $achievement->id,
            'first_update_id' => $firstCommentId,
        ]);

        // Act
        (new SendDailyDigestAction())->execute($subscriber);

        // Assert
        Mail::assertQueued(DailyDigestMail::class, function ($mail) use ($subscriber) {
            return $mail->hasTo($subscriber->email);
        });
    }

    public function testSkipsEmailForInactiveUsers(): void
    {
        // Arrange
        Mail::fake();

        $inactiveUser = User::factory()->create([
            'email' => 'inactive@example.com',
            'last_activity_at' => now()->subDays(91),
        ]);
        $commenter = User::factory()->create();

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $comment = Comment::factory()->create([
            'commentable_type' => CommentableType::Achievement,
            'commentable_id' => $achievement->id,
            'user_id' => $commenter->id,
        ]);

        UserDelayedSubscription::create([
            'user_id' => $inactiveUser->id,
            'subject_type' => SubscriptionSubjectType::Achievement,
            'subject_id' => $achievement->id,
            'first_update_id' => $comment->id,
        ]);

        // Act
        (new SendDailyDigestAction())->execute($inactiveUser);

        // Assert
        Mail::assertNothingQueued();
    }

    public function testSkipsNotificationWhenDeletedCommentHasNoSuccessors(): void
    {
        // Arrange
        Mail::fake();

        $subscriber = User::factory()->create([
            'email' => 'test@example.com',
            'last_activity_at' => now()->subDays(1),
        ]);
        $commenter = User::factory()->create();

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        // ... create and immediately delete the only comment ...
        $onlyComment = Comment::factory()->create([
            'commentable_type' => CommentableType::Achievement,
            'commentable_id' => $achievement->id,
            'user_id' => $commenter->id,
        ]);
        $commentId = $onlyComment->id;
        $onlyComment->delete();

        UserDelayedSubscription::create([
            'user_id' => $subscriber->id,
            'subject_type' => SubscriptionSubjectType::Achievement,
            'subject_id' => $achievement->id,
            'first_update_id' => $commentId,
        ]);

        // Act
        (new SendDailyDigestAction())->execute($subscriber);

        // Assert
        // ... no email should be sent since there are no valid comments ...
        Mail::assertNothingQueued();
    }

    public function testExcludesServerCommentsFromDigest(): void
    {
        // Arrange
        Mail::fake();

        $serverUser = User::factory()->create(['id' => Comment::SYSTEM_USER_ID]);

        $subscriber = User::factory()->create([
            'email' => 'test@example.com',
            'last_activity_at' => now()->subDays(1),
        ]);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $serverComment = Comment::factory()->create([
            'commentable_type' => CommentableType::Achievement,
            'commentable_id' => $achievement->id,
            'user_id' => Comment::SYSTEM_USER_ID,
        ]);

        UserDelayedSubscription::create([
            'user_id' => $subscriber->id,
            'subject_type' => SubscriptionSubjectType::Achievement,
            'subject_id' => $achievement->id,
            'first_update_id' => $serverComment->id,
        ]);

        // Act
        (new SendDailyDigestAction())->execute($subscriber);

        // Assert
        Mail::assertNothingQueued();
    }

    public function testCommandDispatchesDigestJobForScreenshotDecisionOnlyNotifications(): void
    {
        // Arrange
        Queue::fake();

        $subscriber = User::factory()->create([
            'email' => 'test@example.com',
            'last_activity_at' => now()->subDays(1),
        ]);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $screenshot = GameScreenshot::factory()->for($game)->rejected()->create([
            'rejection_reason' => GameScreenshotRejectionReason::WrongGame,
        ]);

        UserDelayedSubscription::create([
            'user_id' => $subscriber->id,
            'subject_type' => SubscriptionSubjectType::GameScreenshotDecision,
            'subject_id' => $screenshot->id,
            'first_update_id' => $screenshot->id,
        ]);

        // Act
        $this->artisan('ra:community:send-daily-digest')
            ->assertExitCode(0);

        // Assert
        Queue::assertPushedOn('summary-emails', SendDailyDigestJob::class);
        Queue::assertPushed(SendDailyDigestJob::class, 1);
    }

    public function testIncludesApprovedScreenshotDecisionMetadataInDigest(): void
    {
        // Arrange
        Mail::fake();

        $subscriber = User::factory()->create([
            'email' => 'test@example.com',
            'last_activity_at' => now()->subDays(1),
        ]);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $screenshot = GameScreenshot::factory()->for($game)->create();

        UserDelayedSubscription::create([
            'user_id' => $subscriber->id,
            'subject_type' => SubscriptionSubjectType::GameScreenshotDecision,
            'subject_id' => $screenshot->id,
            'first_update_id' => $screenshot->id,
        ]);

        // Act
        (new SendDailyDigestAction())->execute($subscriber);

        // Assert
        Mail::assertQueued(DailyDigestMail::class, function ($mail) use ($subscriber) {
            $this->assertTrue($mail->hasTo($subscriber->email));
            $this->assertCount(1, $mail->notificationItems);
            $this->assertSame(SubscriptionSubjectType::GameScreenshotDecision->value, $mail->notificationItems[0]['type']);
            $this->assertSame('approved', $mail->notificationItems[0]['status']);

            return true;
        });
    }

    public function testIncludesRejectedScreenshotDecisionMetadataInDigest(): void
    {
        // Arrange
        Mail::fake();

        $subscriber = User::factory()->create([
            'email' => 'test@example.com',
            'last_activity_at' => now()->subDays(1),
        ]);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $screenshot = GameScreenshot::factory()->for($game)->rejected()->create([
            'rejection_reason' => GameScreenshotRejectionReason::WrongGame,
        ]);

        UserDelayedSubscription::create([
            'user_id' => $subscriber->id,
            'subject_type' => SubscriptionSubjectType::GameScreenshotDecision,
            'subject_id' => $screenshot->id,
            'first_update_id' => $screenshot->id,
        ]);

        // Act
        (new SendDailyDigestAction())->execute($subscriber);

        // Assert
        Mail::assertQueued(DailyDigestMail::class, function ($mail) {
            $this->assertCount(1, $mail->notificationItems);
            $this->assertSame('rejected', $mail->notificationItems[0]['status']);
            $this->assertSame('Wrong Game', $mail->notificationItems[0]['rejectionReason']);
            $this->assertArrayNotHasKey('rejectionNotes', $mail->notificationItems[0]);

            return true;
        });
    }

    public function testIncludesRejectionNotesForSingleRejectedScreenshotInDigest(): void
    {
        // Arrange
        Mail::fake();

        $subscriber = User::factory()->create([
            'email' => 'test@example.com',
            'last_activity_at' => now()->subDays(1),
        ]);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $screenshot = GameScreenshot::factory()->for($game)->rejected()->create([
            'rejection_reason' => GameScreenshotRejectionReason::WrongGame,
            'rejection_notes' => 'screenshot is from Sonic 2',
        ]);

        UserDelayedSubscription::create([
            'user_id' => $subscriber->id,
            'subject_type' => SubscriptionSubjectType::GameScreenshotDecision,
            'subject_id' => $screenshot->id,
            'first_update_id' => $screenshot->id,
        ]);

        // Act
        (new SendDailyDigestAction())->execute($subscriber);

        // Assert
        Mail::assertQueued(DailyDigestMail::class, function ($mail) {
            $this->assertCount(1, $mail->notificationItems);
            $this->assertSame('rejected', $mail->notificationItems[0]['status']);
            $this->assertSame('Wrong Game', $mail->notificationItems[0]['rejectionReason']);
            $this->assertSame('screenshot is from Sonic 2', $mail->notificationItems[0]['rejectionNotes']);

            return true;
        });
    }

    public function testAggregatesMultipleScreenshotDecisionsForTheSameGameInDigest(): void
    {
        // Arrange
        Mail::fake();

        $subscriber = User::factory()->create([
            'email' => 'test@example.com',
            'last_activity_at' => now()->subDays(1),
        ]);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $approvedScreenshotA = GameScreenshot::factory()->for($game)->create();
        $approvedScreenshotB = GameScreenshot::factory()->for($game)->create();
        $rejectedScreenshot = GameScreenshot::factory()->for($game)->rejected()->create([
            'rejection_reason' => GameScreenshotRejectionReason::WrongGame,
            'rejection_notes' => 'screenshot is from Sonic 2',
        ]);

        foreach ([$approvedScreenshotA, $approvedScreenshotB, $rejectedScreenshot] as $screenshot) {
            UserDelayedSubscription::create([
                'user_id' => $subscriber->id,
                'subject_type' => SubscriptionSubjectType::GameScreenshotDecision,
                'subject_id' => $screenshot->id,
                'first_update_id' => $screenshot->id,
            ]);
        }

        // Act
        (new SendDailyDigestAction())->execute($subscriber);

        // Assert
        Mail::assertQueued(DailyDigestMail::class, function ($mail) {
            $this->assertCount(1, $mail->notificationItems);
            $this->assertSame(3, $mail->notificationItems[0]['count']);
            $this->assertSame(1, $mail->notificationItems[0]['gameCount']);
            $this->assertSame(2, $mail->notificationItems[0]['approvedCount']);
            $this->assertSame(1, $mail->notificationItems[0]['rejectedCount']);
            $this->assertSame('Wrong Game', $mail->notificationItems[0]['rejectionReasonSummary']);
            $this->assertSame(
                [['reason' => 'Wrong Game', 'notes' => 'screenshot is from Sonic 2']],
                $mail->notificationItems[0]['rejectedItems'],
            );

            return true;
        });
    }

    public function testAggregatedScreenshotDigestExposesPerRejectionNotes(): void
    {
        // Arrange
        Mail::fake();

        $subscriber = User::factory()->create([
            'email' => 'test@example.com',
            'last_activity_at' => now()->subDays(1),
        ]);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $approvedScreenshot = GameScreenshot::factory()->for($game)->create();
        $rejectedWithNotes = GameScreenshot::factory()->for($game)->rejected()->create([
            'rejection_reason' => GameScreenshotRejectionReason::WrongGame,
            'rejection_notes' => 'screenshot is from Sonic 2',
        ]);
        $rejectedWithoutNotes = GameScreenshot::factory()->for($game)->rejected()->create([
            'rejection_reason' => GameScreenshotRejectionReason::Duplicate,
            'rejection_notes' => null,
        ]);

        foreach ([$approvedScreenshot, $rejectedWithNotes, $rejectedWithoutNotes] as $screenshot) {
            UserDelayedSubscription::create([
                'user_id' => $subscriber->id,
                'subject_type' => SubscriptionSubjectType::GameScreenshotDecision,
                'subject_id' => $screenshot->id,
                'first_update_id' => $screenshot->id,
            ]);
        }

        // Act
        (new SendDailyDigestAction())->execute($subscriber);

        // Assert
        Mail::assertQueued(DailyDigestMail::class, function ($mail) {
            $this->assertCount(1, $mail->notificationItems);
            $this->assertSame(2, $mail->notificationItems[0]['rejectedCount']);
            $this->assertSame(
                [
                    ['reason' => 'Wrong Game', 'notes' => 'screenshot is from Sonic 2'],
                    ['reason' => 'Duplicate', 'notes' => null],
                ],
                $mail->notificationItems[0]['rejectedItems'],
            );

            return true;
        });
    }

    public function testAggregatesMultipleScreenshotDecisionsAcrossGamesInDigest(): void
    {
        // Arrange
        Mail::fake();

        $subscriber = User::factory()->create([
            'email' => 'test@example.com',
            'last_activity_at' => now()->subDays(1),
        ]);

        $system = System::factory()->create();
        $gameA = Game::factory()->create(['system_id' => $system->id]);
        $gameB = Game::factory()->create(['system_id' => $system->id]);

        $approvedScreenshotA = GameScreenshot::factory()->for($gameA)->create();
        $rejectedScreenshotA = GameScreenshot::factory()->for($gameA)->rejected()->create([
            'rejection_reason' => GameScreenshotRejectionReason::WrongGame,
        ]);
        $rejectedScreenshotB = GameScreenshot::factory()->for($gameB)->rejected()->create([
            'rejection_reason' => GameScreenshotRejectionReason::Duplicate,
        ]);

        foreach ([$approvedScreenshotA, $rejectedScreenshotA, $rejectedScreenshotB] as $screenshot) {
            UserDelayedSubscription::create([
                'user_id' => $subscriber->id,
                'subject_type' => SubscriptionSubjectType::GameScreenshotDecision,
                'subject_id' => $screenshot->id,
                'first_update_id' => $screenshot->id,
            ]);
        }

        // Act
        (new SendDailyDigestAction())->execute($subscriber);

        // Assert
        Mail::assertQueued(DailyDigestMail::class, function ($mail) {
            $this->assertCount(2, $mail->notificationItems);
            $this->assertSame(2, $mail->notificationItems[0]['count']);
            $this->assertSame(1, $mail->notificationItems[0]['gameCount']);
            $this->assertSame(1, $mail->notificationItems[0]['approvedCount']);
            $this->assertSame(1, $mail->notificationItems[0]['rejectedCount']);
            $this->assertSame('Wrong Game', $mail->notificationItems[0]['rejectionReasonSummary']);
            $this->assertSame(1, $mail->notificationItems[1]['count']);
            $this->assertSame('rejected', $mail->notificationItems[1]['status']);
            $this->assertSame('Duplicate', $mail->notificationItems[1]['rejectionReason']);

            return true;
        });
    }

    public function testRendersRejectedScreenshotDecisionCopyInDigestMail(): void
    {
        // Arrange
        $subscriber = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $mail = new DailyDigestMail($subscriber, [[
            'type' => SubscriptionSubjectType::GameScreenshotDecision->value,
            'title' => 'Super Mario Bros. (NES)',
            'link' => 'https://example.com/game/1',
            'count' => 1,
            'status' => 'rejected',
            'rejectionReason' => 'Wrong Game',
        ]]);

        // Act
        $rendered = $mail->render();

        // Assert
        $this->assertStringContainsString('Super Mario Bros. (NES)', $rendered);
        $this->assertStringContainsString('was rejected: Wrong Game.', $rendered);
    }

    public function testRendersAggregatedSingleGameScreenshotDecisionCopyInDigestMail(): void
    {
        // Arrange
        $subscriber = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $mail = new DailyDigestMail($subscriber, [[
            'type' => SubscriptionSubjectType::GameScreenshotDecision->value,
            'title' => 'Super Mario Bros. (NES)',
            'link' => 'https://example.com/game/1',
            'count' => 20,
            'gameCount' => 1,
            'approvedCount' => 18,
            'rejectedCount' => 2,
            'reviewedCount' => 0,
            'rejectionReasonSummary' => 'Wrong Game x2',
        ]]);

        // Act
        $rendered = $mail->render();

        // Assert
        $this->assertStringContainsString('Your 20 screenshot submissions for', $rendered);
        $this->assertStringContainsString('Super Mario Bros. (NES)', $rendered);
        $this->assertStringContainsString('were reviewed: 18 approved, 2 rejected (Wrong Game x2).', $rendered);
    }

    public function testRendersMultiplePerGameScreenshotDecisionBlurbsInDigestMail(): void
    {
        // Arrange
        $subscriber = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $mail = new DailyDigestMail($subscriber, [
            [
                'type' => SubscriptionSubjectType::GameScreenshotDecision->value,
                'title' => 'Super Mario Bros. (NES)',
                'link' => 'https://example.com/game/1',
                'count' => 20,
                'gameCount' => 1,
                'approvedCount' => 18,
                'rejectedCount' => 2,
                'reviewedCount' => 0,
                'rejectionReasonSummary' => 'Wrong Game x2',
            ],
            [
                'type' => SubscriptionSubjectType::GameScreenshotDecision->value,
                'title' => 'Sonic the Hedgehog (Genesis/Mega Drive)',
                'link' => 'https://example.com/game/2',
                'count' => 7,
                'gameCount' => 1,
                'approvedCount' => 3,
                'rejectedCount' => 4,
                'reviewedCount' => 0,
                'rejectionReasonSummary' => 'Incorrect Type x2, Duplicate x2',
            ],
        ]);

        // Act
        $rendered = $mail->render();

        // Assert
        $this->assertStringContainsString('Your 20 screenshot submissions for', $rendered);
        $this->assertStringContainsString('Super Mario Bros. (NES)', $rendered);
        $this->assertStringContainsString('were reviewed: 18 approved, 2 rejected (Wrong Game x2).', $rendered);
        $this->assertStringContainsString('Your 7 screenshot submissions for', $rendered);
        $this->assertStringContainsString('Sonic the Hedgehog (Genesis/Mega Drive)', $rendered);
        $this->assertStringContainsString('were reviewed: 3 approved, 4 rejected (Incorrect Type x2, Duplicate x2).', $rendered);
        $this->assertStringNotContainsString('across 2 games', $rendered);
    }

    public function testRendersRejectionNotesForSingleRejectedScreenshotInDigestMail(): void
    {
        // Arrange
        $subscriber = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $mail = new DailyDigestMail($subscriber, [[
            'type' => SubscriptionSubjectType::GameScreenshotDecision->value,
            'title' => 'Super Mario Bros. (NES)',
            'link' => 'https://example.com/game/1',
            'count' => 1,
            'status' => 'rejected',
            'rejectionReason' => 'Wrong Game',
            'rejectionNotes' => 'screenshot is from Sonic 2',
        ]]);

        // Act
        $rendered = $mail->render();

        // Assert
        $this->assertStringContainsString('Super Mario Bros. (NES)', $rendered);
        $this->assertStringContainsString('Wrong Game', $rendered);
        $this->assertStringContainsString('screenshot is from Sonic 2', $rendered);
    }

    public function testRendersAggregatedRejectedItemsListInDigestMail(): void
    {
        // Arrange
        $subscriber = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $mail = new DailyDigestMail($subscriber, [[
            'type' => SubscriptionSubjectType::GameScreenshotDecision->value,
            'title' => 'Super Mario Bros. (NES)',
            'link' => 'https://example.com/game/1',
            'count' => 3,
            'gameCount' => 1,
            'approvedCount' => 1,
            'rejectedCount' => 2,
            'reviewedCount' => 0,
            'rejectionReasonSummary' => 'Wrong Game, Duplicate',
            'rejectedItems' => [
                ['reason' => 'Wrong Game', 'notes' => 'screenshot is from Sonic 2'],
                ['reason' => 'Duplicate', 'notes' => null],
            ],
        ]]);

        // Act
        $rendered = $mail->render();

        // Assert
        $this->assertStringContainsString('were reviewed: 1 approved, 2 rejected (Wrong Game, Duplicate).', $rendered);
        $this->assertStringContainsString('Rejected (Wrong Game)', $rendered);
        $this->assertStringContainsString('screenshot is from Sonic 2', $rendered);
        $this->assertStringContainsString('Rejected (Duplicate)', $rendered);
    }
}
