<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Community\Actions\CreateLookingForGroupPostAction;
use App\Community\Enums\LookingForGroupStatus;
use App\Models\Game;
use App\Models\LookingForGroupPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LookingForGroupPostsTest extends JsonApiResourceTestCase
{
    protected function resourceType(): string
    {
        return 'looking-for-group-posts';
    }

    protected function resourceEndpoint(): string
    {
        return '/api/v2/looking-for-group-posts';
    }

    protected function createResource(): LookingForGroupPost
    {
        /** @var User $creator */
        $creator = User::where('web_api_key', 'test-key')->first()
            ?? User::factory()->create(['web_api_key' => 'test-key']);
        /** @var Game $game */
        $game = Game::factory()->create();

        return (new CreateLookingForGroupPostAction())->execute(
            $creator,
            $game,
            'Looking for players',
            'Need 2 more players for raid',
            4,
            null,
            null
        );
    }

    public function testItRequiresAuthentication(): void
    {
        $this->createResource();

        $response = $this->jsonApi('v2')
            ->expects('looking-for-group-posts')
            ->get($this->resourceEndpoint());

        $response->assertUnauthorized();
    }

    public function testItListsLookingForGroupPosts(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        $post = $this->createResource();

        $response = $this->jsonApi('v2')
            ->expects('looking-for-group-posts')
            ->withHeader('X-API-Key', 'test-key')
            ->get($this->resourceEndpoint());

        $response->assertFetchedMany([
            ['type' => 'looking-for-group-posts', 'id' => (string) $post->id],
        ]);
    }

    public function testItCreatesLookingForGroupPost(): void
    {
        /** @var User $creator */
        $creator = User::factory()->create(['web_api_key' => 'test-key']);
        /** @var Game $game */
        $game = Game::factory()->create();

        $response = $this->jsonApi('v2')
            ->expects('looking-for-group-posts')
            ->withHeader('X-API-Key', 'test-key')
            ->withJson([
                'data' => [
                    'type' => 'looking-for-group-posts',
                    'attributes' => [
                        'title' => 'Need players for co-op',
                        'note' => 'Looking for experienced players',
                        'maxPlayers' => 4,
                    ],
                    'relationships' => [
                        'game' => [
                            'data' => [
                                'type' => 'games',
                                'id' => (string) $game->id,
                            ],
                        ],
                    ],
                ],
            ])
            ->post($this->resourceEndpoint());

        $response->assertCreated();
        $this->assertDatabaseHas('looking_for_group_posts', [
            'creator_user_id' => $creator->id,
            'game_id' => $game->id,
            'title' => 'Need players for co-op',
            'note' => 'Looking for experienced players',
            'max_players' => 4,
            'status' => LookingForGroupStatus::Active->value,
        ]);
    }

    public function testItUpdatesLookingForGroupPost(): void
    {
        /** @var User $creator */
        $creator = User::factory()->create(['web_api_key' => 'test-key']);
        /** @var Game $game */
        $game = Game::factory()->create();

        $post = (new CreateLookingForGroupPostAction())->execute(
            $creator,
            $game,
            'Original title',
            'Original note',
            4,
            null,
            null
        );

        $response = $this->jsonApi('v2')
            ->expects('looking-for-group-posts')
            ->withHeader('X-API-Key', 'test-key')
            ->withJson([
                'data' => [
                    'type' => 'looking-for-group-posts',
                    'id' => (string) $post->id,
                    'attributes' => [
                        'title' => 'Updated title',
                        'note' => 'Updated note',
                    ],
                ],
            ])
            ->patch("{$this->resourceEndpoint()}/{$post->id}");

        $response->assertSuccessful();
        $this->assertDatabaseHas('looking_for_group_posts', [
            'id' => $post->id,
            'title' => 'Updated title',
            'note' => 'Updated note',
        ]);
    }

    public function testItChangesPostStatus(): void
    {
        /** @var User $creator */
        $creator = User::factory()->create(['web_api_key' => 'test-key']);
        /** @var Game $game */
        $game = Game::factory()->create();

        $post = (new CreateLookingForGroupPostAction())->execute(
            $creator,
            $game,
            'Looking for players',
            null,
            4,
            null,
            null
        );

        $response = $this->jsonApi('v2')
            ->expects('looking-for-group-posts')
            ->withHeader('X-API-Key', 'test-key')
            ->withJson([
                'data' => [
                    'type' => 'looking-for-group-posts',
                    'id' => (string) $post->id,
                    'attributes' => [
                        'status' => 'cancelled',
                    ],
                ],
            ])
            ->patch("{$this->resourceEndpoint()}/{$post->id}");

        $response->assertSuccessful();
        $this->assertDatabaseHas('looking_for_group_posts', [
            'id' => $post->id,
            'status' => LookingForGroupStatus::Cancelled->value,
        ]);
    }

    public function testItReturnsCorrectAttributes(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        $post = $this->createResource();

        $response = $this->jsonApi('v2')
            ->expects('looking-for-group-posts')
            ->withHeader('X-API-Key', 'test-key')
            ->get("{$this->resourceEndpoint()}/{$post->id}");

        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');

        $this->assertEquals('Looking for players', $attributes['title']);
        $this->assertEquals('Need 2 more players for raid', $attributes['note']);
        $this->assertEquals(4, $attributes['maxPlayers']);
        $this->assertEquals('active', $attributes['status']);
        $this->assertArrayHasKey('createdAt', $attributes);
    }
}
