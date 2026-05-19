<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ForumTopicControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testStoreDoesNotAuthorizeGuests(): void
    {
        // Arrange
        $category = ForumCategory::factory()->create();
        $forum = Forum::factory()->create(['forum_category_id' => $category->id]);

        // Act
        $response = $this->get(route('forum-topic.create', [
            'category' => $category,
            'forum' => $forum,
        ]));

        // Assert
        $response->assertRedirect(route('login'));
    }

    public function testStoreAuthorizesRegisteredUsers(): void
    {
        // Arrange
        $category = ForumCategory::factory()->create();
        $forum = Forum::factory()->create(['forum_category_id' => $category->id]);

        $user = User::factory()->create([
            'preferences_bitfield' => 63,
            'unread_messages' => 0,
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        // Act
        $response = $this->get(route('forum-topic.create', [
            'category' => $category,
            'forum' => $forum,
        ]));

        // Assert
        $response->assertInertia(fn (Assert $page) => $page
            ->where('forum.id', $forum->id)
            ->where('forum.title', $forum->title)

            ->where('forum.category.id', $category->id)
            ->where('forum.category.title', $category->title)
        );
    }

    public function testShowDeniesAccessToUnauthorizedUsers(): void
    {
        // Arrange
        $author = User::factory()->create();

        $topic = ForumTopic::factory()->create(['author_id' => $author->id, 'required_permissions' => 4]); // !! high permission requirement
        ForumTopicComment::factory()->create([
            'forum_topic_id' => $topic->id,
            'author_id' => $author->id,
            'is_authorized' => true,
        ]);

        $user = User::factory()->create([
            'preferences_bitfield' => 63,
            'unread_messages' => 0,
            'email_verified_at' => now(),
            'Permissions' => 1, // !! low permissions
        ]);

        // Act
        $response = $this->actingAs($user)->get(route('forum-topic.show', $topic));

        // Assert
        $response->assertForbidden();
    }

    public function testShowDisplaysTopicForAuthorizedUsers(): void
    {
        // Arrange
        $author = User::factory()->create();

        $topic = ForumTopic::factory()->create(['author_id' => $author->id, 'required_permissions' => 0]); // !! high permission requirement
        ForumTopicComment::factory()->create([
            'forum_topic_id' => $topic->id,
            'author_id' => $author->id,
            'is_authorized' => true,
        ]);

        $user = User::factory()->create([
            'preferences_bitfield' => 63,
            'unread_messages' => 0,
            'email_verified_at' => now(),
            'Permissions' => 1, // !! low permissions
        ]);

        // Act
        $response = $this->actingAs($user)->get(route('forum-topic.show', $topic));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('forumTopic')
            ->has('paginatedForumTopicComments')
        );
    }

    public function testShowIncludesSentByForHistoricalTeamAccountPosts(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        $admin = User::factory()->create([
            'preferences_bitfield' => 63,
            'unread_messages' => 0,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole(Role::ADMINISTRATOR);

        $radminAccount = User::factory()->create(['username' => 'RAdmin']);
        $originalAuthor = User::factory()->create();

        $topic = ForumTopic::factory()->create(['required_permissions' => 0]);

        ForumTopicComment::factory()->create([
            'forum_topic_id' => $topic->id,
            'author_id' => $radminAccount->id,
            'sent_by_id' => $originalAuthor->id,
            'is_authorized' => true,
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('forum-topic.show', $topic));

        // Assert
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('paginatedForumTopicComments.items.0.sentBy')
            ->where('paginatedForumTopicComments.items.0.sentBy.displayName', $originalAuthor->display_name)
        );
    }
}
