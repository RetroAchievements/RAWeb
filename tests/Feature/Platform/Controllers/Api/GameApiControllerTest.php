<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Controllers\Api;

use App\Models\Forum;
use App\Models\ForumTopic;
use App\Models\Game;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturnsCorrectJsonResponse(): void
    {
        // Arrange
        $activeGameSystem = System::factory()->create(['ID' => 1, 'name' => 'NES/Famicom', 'name_short' => 'NES', 'active' => true]);
        $inactiveGameSystem = System::factory()->create(['ID' => 2, 'name' => 'PlayStation 5', 'name_short' => 'PS5', 'active' => false]);

        /** @var Game $gameOne */
        $gameOne = Game::factory()->create(['title' => 'AAAAAAA', 'achievements_published' => 50, 'system_id' => $activeGameSystem->id]);
        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create(['title' => 'BBBBBBB', 'achievements_published' => 50, 'system_id' => $activeGameSystem->id]);
        /** @var Game $gameThree */
        $gameThree = Game::factory()->create(['title' => 'CCCCCCC [Subset - Bonus]', 'achievements_published' => 50, 'system_id' => $activeGameSystem->id]);

        // Event, hub, and inactive system games should all be excluded from the "All Games" list.
        Game::factory()->create(['title' => 'CCCCCCC', 'achievements_published' => 50, 'system_id' => System::Events]);
        Game::factory()->create(['title' => 'DDDDDDD', 'achievements_published' => 50, 'system_id' => System::Hubs]);
        Game::factory()->create(['title' => 'EEEEEEE', 'achievements_published' => 50, 'system_id' => $inactiveGameSystem->id]);

        // Act
        $response = $this->get(route('api.game.index'));

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'currentPage',
                'lastPage',
                'perPage',
                'items',
            ])
            ->assertJsonCount(3, 'items')
            ->assertJson([
                'items' => [
                    [
                        'game' => [
                            'title' => $gameOne->title,
                            'system' => [
                                'id' => $gameOne->system->id,
                            ],
                        ],
                    ],
                    [
                        'game' => [
                            'title' => $gameTwo->title,
                            'system' => [
                                'id' => $gameTwo->system->id,
                            ],
                        ],
                    ],
                    [
                        'game' => [
                            'title' => $gameThree->title,
                            'system' => [
                                'id' => $gameThree->system->id,
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function testGenerateOfficialForumTopicCreatesTopicAndLinksToGame(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $user->assignRole(Role::DEVELOPER);
        $this->actingAs($user);

        $system = System::factory()->create(['ID' => 1, 'Name' => 'Sega Genesis/Mega Drive']);
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Sonic the Hedgehog',
            'forum_topic_id' => null, // !! no existing topic
        ]);

        Forum::factory()->create(['id' => 10]); // need a forum to put the new topic in

        // Act
        $response = $this->postJson(route('api.game.forum-topic.create', $game));

        // Assert
        $response->assertOk();

        $game->refresh();
        $this->assertGreaterThan(0, $game->forum_topic_id);

        $topic = ForumTopic::find($game->forum_topic_id);
        $this->assertNotNull($topic);
        $this->assertEquals($game->title, $topic->title);
        $this->assertEquals($user->id, $topic->author_id);
    }

    public function testGenerateOfficialForumTopicRequiresAuthorization(): void
    {
        // Arrange
        $this->seed(RolesTableSeeder::class);

        $user = User::factory()->create(['websitePrefs' => 63, 'UnreadMessageCount' => 0]);
        $user->assignRole(Role::DEVELOPER_JUNIOR); // !! jrdevs shouldn't be able to do this
        $this->actingAs($user);

        $system = System::factory()->create(['ID' => 1, 'Name' => 'Sega Genesis/Mega Drive']);
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Sonic the Hedgehog',
            'forum_topic_id' => null, // !! no existing topic
        ]);

        Forum::factory()->create(['id' => 10]); // need a forum to put the new topic in

        // Act
        $response = $this->postJson(route('api.game.forum-topic.create', $game));

        // Assert
        $response->assertForbidden();
    }
}
