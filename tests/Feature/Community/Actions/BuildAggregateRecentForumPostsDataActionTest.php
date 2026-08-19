<?php

declare(strict_types=1);

use App\Community\Actions\BuildAggregateRecentForumPostsDataAction;
use App\Enums\Permissions;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function aggregateTopic(array $attributes = []): ForumTopic
{
    return ForumTopic::factory()->create(array_merge([
        'required_permissions' => 0,
        'author_id' => User::factory()->create()->id,
    ], $attributes));
}

function aggregatePost(ForumTopic $topic, User $author, string $body, string $createdAt): ForumTopicComment
{
    $comment = ForumTopicComment::factory()->create([
        'forum_topic_id' => $topic->id,
        'author_id' => $author->id,
        'body' => $body,
        'is_authorized' => true,
        'created_at' => $createdAt,
    ]);

    $topic->latest_comment_id = $comment->id;
    $topic->save();

    return $comment;
}

function aggregateTitles(mixed $result): array
{
    return array_map(fn ($topic) => $topic->title, $result->items);
}

it('given masked authors and no masked posts present, topics are ordered by their newest post', function () {
    // ARRANGE
    $author = User::factory()->create();
    $unrelatedMaskedUser = User::factory()->create();

    $olderTopic = aggregateTopic(['title' => 'older topic']);
    $newerTopic = aggregateTopic(['title' => 'newer topic']);

    aggregatePost($olderTopic, $author, 'older', '2026-08-01 10:00:00');
    aggregatePost($newerTopic, $author, 'newer', '2026-08-02 10:00:00');

    // ACT
    $result = (new BuildAggregateRecentForumPostsDataAction())->execute(
        permissions: Permissions::Registered,
        page: 1,
        maskedAuthorIds: [$unrelatedMaskedUser->id],
    );

    // ASSERT
    expect(aggregateTitles($result))->toEqual(['newer topic', 'older topic']);
});

it('given a topic whose newest post is masked and whose previous post is stale, it sinks below fresher topics', function () {
    // ARRANGE
    $masked = User::factory()->create();
    $visible = User::factory()->create();

    $staleTopic = aggregateTopic(['title' => 'stale topic']);
    $freshTopic = aggregateTopic(['title' => 'fresh topic']);

    aggregatePost($staleTopic, $visible, 'six months old', '2026-02-01 10:00:00');
    aggregatePost($freshTopic, $visible, 'yesterday', '2026-08-11 10:00:00');
    aggregatePost($staleTopic, $masked, 'masked reply today', '2026-08-12 10:00:00');

    // ACT
    $result = (new BuildAggregateRecentForumPostsDataAction())->execute(
        permissions: Permissions::Registered,
        page: 1,
        maskedAuthorIds: [$masked->id],
    );

    // ASSERT
    expect(aggregateTitles($result))->toEqual(['fresh topic', 'stale topic']);
});

it('given a topic whose newest post is masked and whose previous post is recent, it shows the previous post', function () {
    // ARRANGE
    $masked = User::factory()->create();
    $visible = User::factory()->create();

    $topic = aggregateTopic(['title' => 'a topic']);

    aggregatePost($topic, $visible, 'the visible reply', '2026-08-11 10:00:00');
    aggregatePost($topic, $masked, 'the masked reply', '2026-08-12 10:00:00');

    // ACT
    $result = (new BuildAggregateRecentForumPostsDataAction())->execute(
        permissions: Permissions::Registered,
        page: 1,
        maskedAuthorIds: [$masked->id],
    );

    // ASSERT
    expect($result->items)->toHaveCount(1);
    expect($result->items[0]->latestComment->body)->toEqual('the visible reply');
});

it('given a topic where every post is masked, the topic is absent', function () {
    // ARRANGE
    $masked = User::factory()->create();
    $visible = User::factory()->create();

    $maskedTopic = aggregateTopic(['title' => 'all masked']);
    $visibleTopic = aggregateTopic(['title' => 'visible topic']);

    aggregatePost($maskedTopic, $masked, 'masked one', '2026-08-11 10:00:00');
    aggregatePost($maskedTopic, $masked, 'masked two', '2026-08-12 10:00:00');
    aggregatePost($visibleTopic, $visible, 'visible', '2026-08-10 10:00:00');

    // ACT
    $result = (new BuildAggregateRecentForumPostsDataAction())->execute(
        permissions: Permissions::Registered,
        page: 1,
        maskedAuthorIds: [$masked->id],
    );

    // ASSERT
    expect(aggregateTitles($result))->toEqual(['visible topic']);
});

it('given a topic started by a masked author, it is absent even when other folks reply to it', function () {
    // ARRANGE
    $masked = User::factory()->create();
    $visible = User::factory()->create();

    $maskedTopic = ForumTopic::factory()->create([
        'required_permissions' => 0,
        'author_id' => $masked->id,
        'title' => 'started by a blocked author',
    ]);
    $visibleTopic = ForumTopic::factory()->create([
        'required_permissions' => 0,
        'author_id' => $visible->id,
        'title' => 'started by anyone else',
    ]);

    aggregatePost($maskedTopic, $visible, 'a reply from someone else', '2026-08-12 10:00:00');
    aggregatePost($visibleTopic, $visible, 'an ordinary post', '2026-08-11 10:00:00');

    // ACT
    $result = (new BuildAggregateRecentForumPostsDataAction())->execute(
        permissions: Permissions::Registered,
        page: 1,
        maskedAuthorIds: [$masked->id],
    );

    // ASSERT
    expect(aggregateTitles($result))->toEqual(['started by anyone else']);
});

it('given a topic started by a masked author, the pagination total does not count it', function () {
    // ARRANGE
    $masked = User::factory()->create();
    $visible = User::factory()->create();

    $maskedTopic = ForumTopic::factory()->create([
        'required_permissions' => 0,
        'author_id' => $masked->id,
        'title' => 'started by a blocked author',
    ]);
    $visibleTopic = aggregateTopic(['title' => 'started by anyone else']);

    aggregatePost($maskedTopic, $visible, 'a reply', '2026-08-12 10:00:00');
    aggregatePost($visibleTopic, $visible, 'an ordinary post', '2026-08-11 10:00:00');

    // ACT
    $result = (new BuildAggregateRecentForumPostsDataAction())->execute(
        permissions: Permissions::Registered,
        page: 1,
        maskedAuthorIds: [$masked->id],
    );

    // ASSERT
    expect($result->total)->toEqual(1);
});
