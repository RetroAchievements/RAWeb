<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Observers;

use App\Community\Enums\ArticleType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Models\UserDelayedSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentObserverTest extends TestCase
{
    use RefreshDatabase;

    public function testDeletingCommentUpdatesFirstUpdateIdToNextComment(): void
    {
        // Arrange
        $subscriber = User::factory()->create();
        $commenter = User::factory()->create();

        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);
        $achievement = Achievement::factory()->create(['GameID' => $game->id]);

        // ... create two comments from the commenter ...
        $firstComment = Comment::factory()->create([
            'ArticleType' => ArticleType::Achievement,
            'ArticleID' => $achievement->id,
            'user_id' => $commenter->id,
        ]);
        $secondComment = Comment::factory()->create([
            'ArticleType' => ArticleType::Achievement,
            'ArticleID' => $achievement->id,
            'user_id' => $commenter->id,
        ]);

        // ... create a delayed subscription pointing to the first comment ...
        UserDelayedSubscription::create([
            'user_id' => $subscriber->id,
            'subject_type' => SubscriptionSubjectType::Achievement,
            'subject_id' => $achievement->id,
            'first_update_id' => $firstComment->ID,
        ]);

        // Act
        $firstComment->delete();

        // Assert
        $this->assertDatabaseHas('user_delayed_subscriptions', [
            'user_id' => $subscriber->id,
            'subject_type' => SubscriptionSubjectType::Achievement->value,
            'subject_id' => $achievement->id,
            'first_update_id' => $secondComment->ID,
        ]);
    }

    public function testDeletingCommentDeletesSubscriptionWhenNoNewerCommentsExist(): void
    {
        // Arrange
        $subscriber = User::factory()->create();
        $commenter = User::factory()->create();

        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);
        $achievement = Achievement::factory()->create(['GameID' => $game->id]);

        // ... create only one comment ...
        $onlyComment = Comment::factory()->create([
            'ArticleType' => ArticleType::Achievement,
            'ArticleID' => $achievement->id,
            'user_id' => $commenter->id,
        ]);

        // ... create a delayed subscription pointing to this comment ...
        UserDelayedSubscription::create([
            'user_id' => $subscriber->id,
            'subject_type' => SubscriptionSubjectType::Achievement,
            'subject_id' => $achievement->id,
            'first_update_id' => $onlyComment->ID,
        ]);

        // Act
        $onlyComment->delete();

        // Assert
        $this->assertDatabaseMissing('user_delayed_subscriptions', [
            'user_id' => $subscriber->id,
            'subject_type' => SubscriptionSubjectType::Achievement->value,
            'subject_id' => $achievement->id,
        ]);
    }
}
