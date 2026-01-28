<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumTopicCommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testRedirectsToForumTopicPageWithAnchor(): void
    {
        // Arrange
        $user = User::factory()->create();
        $forumTopic = ForumTopic::factory()->create();

        $comment = ForumTopicComment::factory()->create([
            'forum_topic_id' => $forumTopic->id,
            'author_id' => $user->id,
            'is_authorized' => true,
        ]);

        // Act
        $response = $this->get(route('forum-topic-comment.show', ['comment' => $comment]));

        // Assert
        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringContainsString("forums/topic/{$forumTopic->id}", $location);
        $this->assertStringContainsString("#{$comment->id}", $location);
    }

    public function testExcludesQueryParamsForFirstPageComments(): void
    {
        // Arrange
        $user = User::factory()->create();
        $forumTopic = ForumTopic::factory()->create();

        $comment = ForumTopicComment::factory()->create([
            'forum_topic_id' => $forumTopic->id,
            'author_id' => $user->id,
            'is_authorized' => true,
            'created_at' => now()->subDays(100),
        ]);
        for ($i = 0; $i < 14; $i++) {
            ForumTopicComment::factory()->create([
                'forum_topic_id' => $forumTopic->id,
                'author_id' => $user->id,
                'is_authorized' => true,
                'created_at' => now()->subDays(50 - $i),
            ]);
        }

        // Act
        $response = $this->get(route('forum-topic-comment.show', ['comment' => $comment]));

        // Assert
        $response->assertRedirect();
        $location = $response->headers->get('Location');

        // ... page 1 comments should not have any query params - just the anchor ...
        $this->assertStringNotContainsString('?', $location);
        $this->assertStringContainsString("#{$comment->id}", $location);
    }

    public function testUsesCommentQueryParamForSecondPageComments(): void
    {
        // Arrange
        $user = User::factory()->create();
        $forumTopic = ForumTopic::factory()->create();

        // ... fill page 1 ...
        for ($i = 0; $i < 15; $i++) {
            ForumTopicComment::factory()->create([
                'forum_topic_id' => $forumTopic->id,
                'author_id' => $user->id,
                'is_authorized' => true,
                'created_at' => now()->subDays(100 - $i),
            ]);
        }

        $targetComment = ForumTopicComment::factory()->create([
            'forum_topic_id' => $forumTopic->id,
            'author_id' => $user->id,
            'is_authorized' => true,
            'created_at' => now()->subDays(50),
        ]);

        // Act
        $response = $this->get(route('forum-topic-comment.show', ['comment' => $targetComment]));

        // Assert
        $response->assertRedirect();
        $location = $response->headers->get('Location');

        // ... page 2+ comments should use the comment query param ...
        $this->assertStringContainsString("comment={$targetComment->id}", $location);
        $this->assertStringContainsString("#{$targetComment->id}", $location);
    }

    public function testReturns404ForSoftDeletedComment(): void
    {
        // Arrange
        $user = User::factory()->create();
        $forumTopic = ForumTopic::factory()->create();

        $comment = ForumTopicComment::factory()->create([
            'forum_topic_id' => $forumTopic->id,
            'author_id' => $user->id,
            'is_authorized' => true,
        ]);

        $comment->delete();

        // Act
        $response = $this->get(route('forum-topic-comment.show', ['comment' => $comment->id]));

        // Assert
        $response->assertNotFound();
    }
}
