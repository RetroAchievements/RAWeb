<?php

declare(strict_types=1);

use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
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

    $this->author = User::factory()->create();
    $this->topic = ForumTopic::factory()->create();
});

function makeForumPost(
    string $body,
    User $author,
    ForumTopic $topic,
    ?int $sentById = null,
): ForumTopicComment {
    return ForumTopicComment::factory()->create([
        'forum_topic_id' => $topic->id,
        'body' => $body,
        'author_id' => $author->id,
        'sent_by_id' => $sentById,
    ]);
}

/**
 * @return list<WatchedTermAlert>
 */
function pushedForumAlerts(): array
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

it('given a new forum post containing a watched term, it alerts', function (string $body) {
    // Arrange
    ModerationWatchlistTerm::create(['term' => 'watchedtool']);

    // Act
    makeForumPost($body, $this->author, $this->topic);

    // Assert
    $alerts = pushedForumAlerts();
    expect($alerts)->toHaveCount(1);
    expect($alerts[0]->matchedTerms)->toEqual(['watchedtool']);
})->with([
    'plain' => 'grab watchedtool for this',
    'split by markup' => 'grab watched[b][/b]tool for this',
]);

it('given a clean post edited to add a watched term, it alerts for that term', function () {
    // Arrange
    ModerationWatchlistTerm::create(['term' => 'watchedtool']);
    $post = makeForumPost('anyone got tips for this game', $this->author, $this->topic);

    // Act
    $post->update(['body' => 'anyone got tips, I used watchedtool']);

    // Assert
    $alerts = pushedForumAlerts();
    expect($alerts)->toHaveCount(1);
    expect($alerts[0]->matchedTerms)->toEqual(['watchedtool']);
});

it('given an already-matching post edited elsewhere, it alerts nothing further', function () {
    // Arrange
    ModerationWatchlistTerm::create(['term' => 'watchedtool']);
    $post = makeForumPost('I used watchedtool here', $this->author, $this->topic);
    expect(pushedForumAlerts())->toHaveCount(1);

    // Act
    $post->update(['body' => 'I used watchedtool here, fixed a typo']);

    // Assert
    expect(pushedForumAlerts())->toHaveCount(1);
});

it('given an already-matching post edited to add a second term, then both alerts carry the right terms', function () {
    // Arrange
    ModerationWatchlistTerm::create(['term' => 'watchedtool']);
    ModerationWatchlistTerm::create(['term' => 'secondtool']);
    $post = makeForumPost('I used watchedtool here', $this->author, $this->topic);

    // Act
    $post->update(['body' => 'I used watchedtool and secondtool here']);

    // Assert
    $alerts = pushedForumAlerts();
    expect($alerts)->toHaveCount(2);
    expect($alerts[0]->matchedTerms)->toEqual(['watchedtool']);
    expect($alerts[1]->matchedTerms)->toEqual(['secondtool']);
});

it('given a post edited by someone other than its author, the alert names the editor', function () {
    // Arrange
    ModerationWatchlistTerm::create(['term' => 'watchedtool']);
    $moderator = User::factory()->create();
    $post = makeForumPost('anyone got tips for this game', $this->author, $this->topic);

    // Act
    $post->update([
        'body' => 'anyone got tips, watchedtool works',
        'edited_by_id' => $moderator->id,
    ]);

    // Assert
    $alerts = pushedForumAlerts();
    expect($alerts)->toHaveCount(1);
    expect($alerts[0]->user->id)->toEqual($moderator->id);
});

it('given a post a moderator edited earlier, the alert names the author who added the term', function () {
    // Arrange
    ModerationWatchlistTerm::create(['term' => 'watchedtool']);
    $moderator = User::factory()->create();
    $post = makeForumPost('anyone got tips for this game', $this->author, $this->topic);

    // ... have a moderator edit the post ...
    $post->update(['body' => 'anyone got tips for this game?', 'edited_by_id' => $moderator->id]);

    // Act
    $post->update(['body' => 'anyone got tips, watchedtool works']);

    // Assert
    $alerts = pushedForumAlerts();
    expect($alerts)->toHaveCount(1);
    expect($alerts[0]->user->id)->toEqual($this->author->id);
});

it('given a post submitted on behalf of a team account, the alert names the submitter', function () {
    // Arrange
    ModerationWatchlistTerm::create(['term' => 'watchedtool']);
    $teamAccount = User::factory()->create();

    // Act
    makeForumPost('grab watchedtool', $teamAccount, $this->topic, sentById: $this->author->id);

    // Assert
    $alerts = pushedForumAlerts();
    expect($alerts)->toHaveCount(1);
    expect($alerts[0]->user->id)->toEqual($this->author->id);
});
