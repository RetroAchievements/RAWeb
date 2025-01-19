<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\User;
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
            'websitePrefs' => 63,
            'UnreadMessageCount' => 0,
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
}
