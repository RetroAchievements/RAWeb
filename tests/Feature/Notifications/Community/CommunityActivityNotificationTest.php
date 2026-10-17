<?php

declare(strict_types=1);

use App\Community\Enums\CommentableType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Enums\UserPreference;
use App\Models\Comment;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\Game;
use App\Models\PlayerSession;
use App\Models\Subscription;
use App\Models\System;
use App\Models\User;
use App\Notifications\Community\CommunityActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('renders newlines in the comment preview as line breaks rather than literal br tags', function () {
    // Arrange
    $user = User::factory()->create();

    $notification = new CommunityActivityNotification(
        activityId: 1,
        activityCommenterDisplayName: 'Commenter',
        commentableType: CommentableType::Forum,
        articleTitle: 'Test Topic',
        urlTarget: 'https://example.com',
        payload: "First line.\r\nSecond line.\r\n\r\nNew paragraph.",
    );

    // Act
    $html = (string) $notification->toMail($user)->render();

    // Assert
    expect($html)->not->toContain('&lt;br');
    expect($html)->toMatch('/First line\.<br\s*\/?>\s*Second line\./');
    expect($html)->toMatch('/<p[^>]*>New paragraph\.<\/p>/');
});

it('does not include literal br tags in forum reply emails', function () {
    // Arrange
    Notification::fake();

    $author = User::factory()->create();
    $subscriber = User::factory()->create([
        'email' => 'subscriber@example.com',
        'last_activity_at' => now(),
        'preferences_bitfield' => 1 << UserPreference::EmailOn_ForumReply,
    ]);

    $topic = ForumTopic::factory()->create(['author_id' => $author->id]);
    Subscription::create([
        'subject_type' => SubscriptionSubjectType::ForumTopic,
        'subject_id' => $topic->id,
        'user_id' => $subscriber->id,
        'state' => true,
    ]);

    $comment = ForumTopicComment::factory()->create([
        'forum_topic_id' => $topic->id,
        'author_id' => $author->id,
        'body' => "First line.\r\nSecond line.",
    ]);

    // Act
    notifyUsersAboutForumActivity($topic, $author, $comment);

    // Assert
    Notification::assertSentTo(
        $subscriber,
        CommunityActivityNotification::class,
        function (CommunityActivityNotification $notification) use ($subscriber) {
            $html = (string) $notification->toMail($subscriber)->render();

            return !str_contains($html, '&lt;br')
                && preg_match('/First line\.<br\s*\/?>\s*Second line\./', $html) === 1;
        }
    );
});

it('does not include literal br tags in game wall comment emails', function () {
    // Arrange
    Notification::fake();

    $system = System::factory()->create();
    $game = Game::factory()->create(['system_id' => $system->id]);

    // ... the commenter must be trusted for the comment body to be included in the email ...
    $commenter = User::factory()->create(['created_at' => now()->subDays(2)]);
    PlayerSession::factory()->create([
        'user_id' => $commenter->id,
        'game_id' => $game->id,
        'duration' => 10,
    ]);

    $subscriber = User::factory()->create([
        'email' => 'subscriber@example.com',
        'last_activity_at' => now(),
        'preferences_bitfield' => 1 << UserPreference::EmailOn_AchievementComment,
    ]);
    Subscription::create([
        'subject_type' => SubscriptionSubjectType::GameWall,
        'subject_id' => $game->id,
        'user_id' => $subscriber->id,
        'state' => true,
    ]);

    $comment = Comment::factory()->create([
        'commentable_type' => CommentableType::Game,
        'commentable_id' => $game->id,
        'user_id' => $commenter->id,
        'body' => "First line.\r\nSecond line.",
    ]);

    // Act
    informAllSubscribersAboutActivity(CommentableType::Game, $game->id, $commenter, $comment->id);

    // Assert
    Notification::assertSentTo(
        $subscriber,
        CommunityActivityNotification::class,
        function (CommunityActivityNotification $notification) use ($subscriber) {
            $html = (string) $notification->toMail($subscriber)->render();

            return !str_contains($html, '&lt;br')
                && preg_match('/First line\.<br\s*\/?>\s*Second line\./', $html) === 1;
        }
    );
});
