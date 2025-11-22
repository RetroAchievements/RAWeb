<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers\Api;

use App\Community\Enums\NewsCategory;
use App\Models\News;
use App\Models\User;
use App\Models\Viewable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewableApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testItRequiresAuthentication(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(); // we're only creating this because the News factory crashes without a user
        $news = News::factory()->create();

        // Act
        $response = $this->postJson(route('api.viewable.store', [
            'viewableType' => 'news',
            'viewableId' => $news->id,
        ]));

        // Assert
        $response->assertStatus(401);
    }

    public function testItRejectsInvalidViewableType(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->postJson(route('api.viewable.store', [
            'viewableType' => 'invalid_type',
            'viewableId' => 1,
        ]));

        // Assert
        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid viewable type']);
    }

    public function testItRejectsNonExistentViewable(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->postJson(route('api.viewable.store', [
            'viewableType' => 'news',
            'viewableId' => 99999,
        ]));

        // Assert
        $response->assertStatus(404)
            ->assertJson(['error' => 'Viewable not found']);
    }

    public function testItMarksRegularNewsAsViewed(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();
        $news = News::factory()->create(['category' => null]);

        // Act
        $response = $this->actingAs($user)->postJson(route('api.viewable.store', [
            'viewableType' => 'news',
            'viewableId' => $news->id,
        ]));

        // Assert
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertTrue($news->wasViewedBy($user));
        $this->assertEquals(1, Viewable::where('user_id', $user->id)->count());
    }

    public function testItTracksSiteReleaseNotesWithLatestOnly(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();

        $releaseNote1 = News::factory()->create([
            'category' => NewsCategory::SiteReleaseNotes,
            'title' => 'Release Note 1',
        ]);
        $releaseNote2 = News::factory()->create([
            'category' => NewsCategory::SiteReleaseNotes,
            'title' => 'Release Note 2',
        ]);

        // Act
        // ... mark first release note as viewed ...
        $response1 = $this->actingAs($user)->postJson(route('api.viewable.store', [
            'viewableType' => 'site_release_note',
            'viewableId' => $releaseNote1->id,
        ]));

        // ... verify the first release note is marked as viewed ...
        $response1->assertStatus(200)->assertJson(['success' => true]);
        $this->assertEquals(1, Viewable::where('user_id', $user->id)->count());
        $this->assertTrue($releaseNote1->wasViewedBy($user));

        // ... mark the second release note as viewed ...
        $response2 = $this->actingAs($user)->postJson(route('api.viewable.store', [
            'viewableType' => 'site_release_note',
            'viewableId' => $releaseNote2->id,
        ]));

        // Assert
        $response2->assertStatus(200)->assertJson(['success' => true]);

        $this->assertEquals(1, Viewable::where('user_id', $user->id)->count()); // still just 1
        $this->assertFalse($releaseNote1->wasViewedBy($user)); // old one deleted
        $this->assertTrue($releaseNote2->wasViewedBy($user)); // new one created

        $viewable = Viewable::where('user_id', $user->id)->first();
        $this->assertEquals($releaseNote2->id, $viewable->viewable_id);
        $this->assertEquals('site_release_note', $viewable->viewable_type);
    }

    public function testItDoesNotDeleteRegularNewsWhenViewingMultiple(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();

        $news1 = News::factory()->create(['category' => null]);
        $news2 = News::factory()->create(['category' => null]);

        // Act
        // ... mark both as viewed ...
        $this->actingAs($user)->postJson(route('api.viewable.store', [
            'viewableType' => 'news',
            'viewableId' => $news1->id,
        ]));
        $this->actingAs($user)->postJson(route('api.viewable.store', [
            'viewableType' => 'news',
            'viewableId' => $news2->id,
        ]));

        // Assert
        // ... both viewables exist (nothing was deleted) ...
        $this->assertEquals(2, Viewable::where('user_id', $user->id)->count());
        $this->assertTrue($news1->wasViewedBy($user));
        $this->assertTrue($news2->wasViewedBy($user));
    }
}
