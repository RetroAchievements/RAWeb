<?php

declare(strict_types=1);

use App\Community\Enums\CommentableType;
use App\Models\Comment;
use App\Models\ModerationWatchlistTerm;
use App\Models\User;
use App\Support\Alerts\Jobs\SendAlertWebhookJob;
use App\Support\Alerts\WatchedTermAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    config(['services.discord.alerts_webhook.watched_term' => 'https://discord.com/api/webhooks/test']);
});

function makeComment(
    string $body = 'nothing to see here',
    ?int $userId = null,
    CommentableType $commentableType = CommentableType::Game,
): Comment {
    $userId ??= User::factory()->create()->id;

    return Comment::factory()->create([
        'body' => $body,
        'user_id' => $userId,
        'commentable_id' => 1,
        'commentable_type' => $commentableType,
    ]);
}

/**
 * @return list<WatchedTermAlert>
 */
function pushedWatchedTermAlerts(): array
{
    $alerts = [];

    Queue::assertPushed(SendAlertWebhookJob::class, function (SendAlertWebhookJob $job) use (&$alerts): bool {
        if ($job->alert instanceof WatchedTermAlert) {
            $alerts[] = $job->alert;
        }

        return true;
    });

    return $alerts;
}

it('given a new comment containing a watched term, it alerts naming that term and linking the comment', function () {
    // Arrange
    ModerationWatchlistTerm::create(['term' => 'watchedtool']);

    // Act
    $comment = makeComment(body: 'Just use WatchedTool for this one');

    // Assert
    $alerts = pushedWatchedTermAlerts();
    expect($alerts)->toHaveCount(1);
    expect($alerts[0]->matchedTerms)->toEqual(['watchedtool']);
    expect($alerts[0]->destinationUrl)->toEqual(route('comment.show', ['comment' => $comment->id]));
    expect($alerts[0]->location)->toEqual('a game comment');
});

it('given a new comment containing two watched terms, it sends one alert listing both', function () {
    // Arrange
    ModerationWatchlistTerm::create(['term' => 'watchedtool']);
    ModerationWatchlistTerm::create(['term' => 'secondtool']);

    // Act
    makeComment(body: 'watchedtool plus secondtool together');

    // Assert
    $alerts = pushedWatchedTermAlerts();
    expect($alerts)->toHaveCount(1);
    expect($alerts[0]->matchedTerms)->toEqualCanonicalizing(['watchedtool', 'secondtool']);
});

it('given a comment that splits the term with empty markup, it still alerts', function () {
    // Arrange
    ModerationWatchlistTerm::create(['term' => 'watchedtool']);

    // Act
    makeComment(body: 'grab watched[b][/b]tool here');

    // Assert
    expect(pushedWatchedTermAlerts())->toHaveCount(1);
});

it('given a new comment with no watched term, it alerts nothing', function () {
    // Arrange
    ModerationWatchlistTerm::create(['term' => 'watchedtool']);

    // Act
    makeComment(body: 'great set, thanks for the work');

    // Assert
    Queue::assertNothingPushed();
});
