<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Enums\Permissions;
use App\Models\Forum;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserForumTopicCommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturnsExpectedInertiaPayload(): void
    {
        // Arrange
        $targetUser = User::factory()->create(['username' => 'Scott']);
        $forum = Forum::factory()->create();

        $topic = ForumTopic::factory()->create([
            'author_id' => $targetUser->id,
            'forum_id' => $forum->id,
            'title' => 'Test Topic',
            'required_permissions' => Permissions::Unregistered,
        ]);

        $comment = ForumTopicComment::factory()->create([
            'forum_topic_id' => $topic->id,
            'author_id' => $targetUser->id,
            'body' => 'This is a test forum post',
        ]);

        // Act
        $response = $this->get(route('user.posts.index', ['user' => $targetUser]));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('user/[user]/posts')
            ->where('targetUser.displayName', 'Scott')
            ->has('paginatedTopics.items', 1)
            ->where('paginatedTopics.items.0.id', $topic->id)
            ->where('paginatedTopics.items.0.title', 'Test Topic')
            ->where('paginatedTopics.items.0.latestComment.id', $comment->id)
            ->where('paginatedTopics.items.0.latestComment.body', 'This is a test forum post')
        );
    }

    public function testIndexExcludesRestrictedUnauthorizedAndDeletedTopicPosts(): void
    {
        // Arrange
        $targetUser = User::factory()->create(['username' => 'Scott']);
        $forum = Forum::factory()->create();

        $visibleTopic = ForumTopic::factory()->create([
            'author_id' => $targetUser->id,
            'forum_id' => $forum->id,
            'required_permissions' => Permissions::Unregistered,
        ]);
        $visibleComment = ForumTopicComment::factory()->create([
            'forum_topic_id' => $visibleTopic->id,
            'author_id' => $targetUser->id,
            'body' => 'Visible post',
        ]);

        $restrictedTopic = ForumTopic::factory()->create([
            'author_id' => $targetUser->id,
            'forum_id' => $forum->id,
            'required_permissions' => Permissions::Moderator,
        ]);
        ForumTopicComment::factory()->create([
            'forum_topic_id' => $restrictedTopic->id,
            'author_id' => $targetUser->id,
            'body' => 'Restricted post',
        ]);

        $unauthorizedTopic = ForumTopic::factory()->create([
            'author_id' => $targetUser->id,
            'forum_id' => $forum->id,
            'required_permissions' => Permissions::Unregistered,
        ]);
        ForumTopicComment::factory()->create([
            'forum_topic_id' => $unauthorizedTopic->id,
            'author_id' => $targetUser->id,
            'body' => 'Unauthorized post',
            'authorized_at' => null,
            'is_authorized' => false,
        ]);

        $deletedTopic = ForumTopic::factory()->create([
            'author_id' => $targetUser->id,
            'forum_id' => $forum->id,
            'required_permissions' => Permissions::Unregistered,
        ]);
        ForumTopicComment::factory()->create([
            'forum_topic_id' => $deletedTopic->id,
            'author_id' => $targetUser->id,
            'body' => 'Deleted topic post',
        ]);
        $deletedTopic->delete();

        $deletedCommentTopic = ForumTopic::factory()->create([
            'author_id' => $targetUser->id,
            'forum_id' => $forum->id,
            'required_permissions' => Permissions::Unregistered,
        ]);
        $deletedComment = ForumTopicComment::factory()->create([
            'forum_topic_id' => $deletedCommentTopic->id,
            'author_id' => $targetUser->id,
            'body' => 'Deleted comment post',
        ]);
        $deletedComment->delete();

        // Act
        $response = $this->get(route('user.posts.index', ['user' => $targetUser]));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('paginatedTopics.items', 1)
            ->where('paginatedTopics.items.0.id', $visibleTopic->id)
            ->where('paginatedTopics.items.0.latestComment.id', $visibleComment->id)
            ->where('paginatedTopics.items.0.latestComment.body', 'Visible post')
        );
    }

    public function testIndexIncludesPostsAuthorizedByTimestamp(): void
    {
        // Arrange
        $targetUser = User::factory()->create(['username' => 'Scott']);
        $forum = Forum::factory()->create();

        $topic = ForumTopic::factory()->create([
            'author_id' => $targetUser->id,
            'forum_id' => $forum->id,
            'required_permissions' => Permissions::Unregistered,
            'title' => 'Authorized By Timestamp',
        ]);

        $comment = ForumTopicComment::factory()->create([
            'forum_topic_id' => $topic->id,
            'author_id' => $targetUser->id,
            'body' => 'Authorized by timestamp',
            'is_authorized' => false,
            'authorized_at' => Carbon::now()->subMinute(),
        ]);

        // Act
        $response = $this->get(route('user.posts.index', ['user' => $targetUser]));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('paginatedTopics.items', 1)
            ->where('paginatedTopics.items.0.id', $topic->id)
            ->where('paginatedTopics.items.0.title', 'Authorized By Timestamp')
            ->where('paginatedTopics.items.0.latestComment.id', $comment->id)
            ->where('paginatedTopics.items.0.latestComment.body', 'Authorized by timestamp')
        );
    }
}
