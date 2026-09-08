<?php

declare(strict_types=1);

use App\Community\Actions\BuildThinRecentForumPostsDataAction;
use App\Enums\Permissions;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function thinTopic(array $attributes = []): ForumTopic
{
    return ForumTopic::factory()->create(array_merge([
        'required_permissions' => 0,
        'author_id' => User::factory()->create()->id,
    ], $attributes));
}

function thinPost(ForumTopic $topic, User $author, string $body, string $createdAt): ForumTopicComment
{
    return ForumTopicComment::factory()->create([
        'forum_topic_id' => $topic->id,
        'author_id' => $author->id,
        'body' => $body,
        'is_authorized' => true,
        'created_at' => $createdAt,
    ]);
}

it('given no masked authors, every recent post is returned', function () {
    // ARRANGE
    $author = User::factory()->create();
    $topic = thinTopic();

    thinPost($topic, $author, 'older post', '2026-08-01 10:00:00');
    thinPost($topic, $author, 'newer post', '2026-08-02 10:00:00');

    // ACT
    $result = (new BuildThinRecentForumPostsDataAction())->execute(permissions: Permissions::Registered);

    // ASSERT
    expect($result)->toHaveCount(2);
});

it('given the newest post is by a masked author, the next visible post leads', function () {
    // ARRANGE
    $masked = User::factory()->create();
    $visible = User::factory()->create();
    $topic = thinTopic();

    thinPost($topic, $visible, 'visible post', '2026-08-01 10:00:00');
    thinPost($topic, $masked, 'masked post', '2026-08-02 10:00:00');

    // ACT
    $result = (new BuildThinRecentForumPostsDataAction())->execute(
        permissions: Permissions::Registered,
        maskedAuthorIds: [$masked->id],
    );

    // ASSERT
    expect($result)->toHaveCount(1);
    expect($result->first()->latestComment->body)->toEqual('visible post');
});

it('given a masked author, none of their posts are returned', function () {
    // ARRANGE
    $masked = User::factory()->create();
    $visible = User::factory()->create();
    $topic = thinTopic();

    thinPost($topic, $masked, 'masked one', '2026-08-01 10:00:00');
    thinPost($topic, $masked, 'masked two', '2026-08-02 10:00:00');
    thinPost($topic, $visible, 'visible one', '2026-08-03 10:00:00');

    // ACT
    $result = (new BuildThinRecentForumPostsDataAction())->execute(
        permissions: Permissions::Registered,
        maskedAuthorIds: [$masked->id],
    );

    // ASSERT
    expect($result)->toHaveCount(1);
    expect($result->first()->latestComment->body)->toEqual('visible one');
});

it('given a topic started by a masked author, none of its posts appear even from other authors', function () {
    // ARRANGE
    $masked = User::factory()->create();
    $visible = User::factory()->create();

    $maskedTopic = ForumTopic::factory()->create([
        'required_permissions' => 0,
        'author_id' => $masked->id,
    ]);
    $visibleTopic = ForumTopic::factory()->create([
        'required_permissions' => 0,
        'author_id' => $visible->id,
    ]);

    thinPost($maskedTopic, $visible, 'a reply inside a blocked topic', '2026-08-02 10:00:00');
    thinPost($visibleTopic, $visible, 'an ordinary post', '2026-08-01 10:00:00');

    // ACT
    $result = (new BuildThinRecentForumPostsDataAction())->execute(
        permissions: Permissions::Registered,
        maskedAuthorIds: [$masked->id],
    );

    // ASSERT
    expect($result)->toHaveCount(1);
    expect($result->first()->latestComment->body)->toEqual('an ordinary post');
});
