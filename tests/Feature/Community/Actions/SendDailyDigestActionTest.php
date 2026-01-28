<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Actions;

use App\Community\Actions\SendDailyDigestAction;
use App\Community\Enums\CommentableType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Mail\DailyDigestMail;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Models\UserDelayedSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
}
