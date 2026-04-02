<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Community\Actions\CreateLookingForGroupInviteAction;
use App\Community\Actions\CreateLookingForGroupPostAction;
use App\Community\Actions\RespondToLookingForGroupInviteAction;
use App\Community\Actions\CancelLookingForGroupInviteAction;
use App\Community\Enums\LookingForGroupInviteStatus;
use App\Models\Game;
use App\Models\LookingForGroupInvite;
use App\Models\LookingForGroupPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LookingForGroupInvitesTest extends JsonApiResourceTestCase
{
    protected function resourceType(): string
    {
        return 'looking-for-group-invites';
    }

    protected function resourceEndpoint(): string
    {
        return '/api/v2/looking-for-group-invites';
    }

    protected function createResource(): LookingForGroupInvite
    {
        /** @var User $creator */
        $creator = User::factory()->create();
        /** @var User $sender */
        $sender = User::where('web_api_key', 'test-key')->first()
            ?? User::factory()->create(['web_api_key' => 'test-key']);
        /** @var User $recipient */
        $recipient = User::factory()->create();
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

        return (new CreateLookingForGroupInviteAction())->execute($post, $sender, $recipient, 'Want to join?');
    }

    public function testItRequiresAuthentication(): void
    {
        $this->createResource();

        $response = $this->jsonApi('v2')
            ->expects('looking-for-group-invites')
            ->get($this->resourceEndpoint());

        $response->assertUnauthorized();
    }

    public function testItListsLookingForGroupInvites(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        $invite = $this->createResource();

        $response = $this->jsonApi('v2')
            ->expects('looking-for-group-invites')
            ->withHeader('X-API-Key', 'test-key')
            ->get($this->resourceEndpoint());

        $response->assertFetchedMany([
            ['type' => 'looking-for-group-invites', 'id' => (string) $invite->id],
        ]);
    }

    public function testItCreatesLookingForGroupInvite(): void
    {
        /** @var User $creator */
        $creator = User::factory()->create();
        /** @var User $sender */
        $sender = User::factory()->create(['web_api_key' => 'test-key']);
        /** @var User $recipient */
        $recipient = User::factory()->create();
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
            ->expects('looking-for-group-invites')
            ->withHeader('X-API-Key', 'test-key')
            ->withJson([
                'data' => [
                    'type' => 'looking-for-group-invites',
                    'attributes' => [
                        'message' => 'Want to join our group?',
                    ],
                    'relationships' => [
                        'lookingForGroupPost' => [
                            'data' => [
                                'type' => 'looking-for-group-posts',
                                'id' => (string) $post->id,
                            ],
                        ],
                        'recipient' => [
                            'data' => [
                                'type' => 'users',
                                'id' => $recipient->ulid,
                            ],
                        ],
                    ],
                ],
            ])
            ->post($this->resourceEndpoint());

        $response->assertCreated();
        $this->assertDatabaseHas('looking_for_group_invites', [
            'sender_user_id' => $sender->id,
            'recipient_user_id' => $recipient->id,
            'looking_for_group_post_id' => $post->id,
            'message' => 'Want to join our group?',
            'status' => LookingForGroupInviteStatus::Pending->value,
        ]);
    }

    public function testItRejectsInviteToSelf(): void
    {
        /** @var User $creator */
        $creator = User::factory()->create();
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'test-key']);
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
            ->expects('looking-for-group-invites')
            ->withHeader('X-API-Key', 'test-key')
            ->withJson([
                'data' => [
                    'type' => 'looking-for-group-invites',
                    'attributes' => [
                        'message' => 'Invite to self.',
                    ],
                    'relationships' => [
                        'lookingForGroupPost' => [
                            'data' => [
                                'type' => 'looking-for-group-posts',
                                'id' => (string) $post->id,
                            ],
                        ],
                        'recipient' => [
                            'data' => [
                                'type' => 'users',
                                'id' => $user->ulid,
                            ],
                        ],
                    ],
                ],
            ])
            ->post($this->resourceEndpoint());

        $response->assertStatus(422);
        $response->assertJsonPath('errors.0.detail', 'You cannot invite yourself to an LFG post.');
    }

    public function testItAcceptsLookingForGroupInvite(): void
    {
        /** @var User $creator */
        $creator = User::factory()->create();
        /** @var User $sender */
        $sender = User::factory()->create();
        /** @var User $recipient */
        $recipient = User::factory()->create(['web_api_key' => 'test-key']);
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

        $invite = (new CreateLookingForGroupInviteAction())->execute($post, $sender, $recipient, 'Join us?');

        $response = $this->jsonApi('v2')
            ->expects('looking-for-group-invites')
            ->withHeader('X-API-Key', 'test-key')
            ->withJson([
                'data' => [
                    'type' => 'looking-for-group-invites',
                    'id' => (string) $invite->id,
                    'attributes' => [
                        'status' => 'accepted',
                    ],
                ],
            ])
            ->patch("{$this->resourceEndpoint()}/{$invite->id}");

        $response->assertSuccessful();
        $this->assertDatabaseHas('looking_for_group_invites', [
            'id' => $invite->id,
            'status' => LookingForGroupInviteStatus::Accepted->value,
            'responded_at' => now(),
        ]);
    }

    public function testItDeclinesLookingForGroupInvite(): void
    {
        /** @var User $creator */
        $creator = User::factory()->create();
        /** @var User $sender */
        $sender = User::factory()->create();
        /** @var User $recipient */
        $recipient = User::factory()->create(['web_api_key' => 'test-key']);
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

        $invite = (new CreateLookingForGroupInviteAction())->execute($post, $sender, $recipient, 'Join us?');

        $response = $this->jsonApi('v2')
            ->expects('looking-for-group-invites')
            ->withHeader('X-API-Key', 'test-key')
            ->withJson([
                'data' => [
                    'type' => 'looking-for-group-invites',
                    'id' => (string) $invite->id,
                    'attributes' => [
                        'status' => 'declined',
                    ],
                ],
            ])
            ->patch("{$this->resourceEndpoint()}/{$invite->id}");

        $response->assertSuccessful();
        $this->assertDatabaseHas('looking_for_group_invites', [
            'id' => $invite->id,
            'status' => LookingForGroupInviteStatus::Declined->value,
            'responded_at' => now(),
        ]);
    }

    public function testItReturnsCorrectAttributes(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'test-key']);
        $invite = $this->createResource();

        $response = $this->jsonApi('v2')
            ->expects('looking-for-group-invites')
            ->withHeader('X-API-Key', 'test-key')
            ->get("{$this->resourceEndpoint()}/{$invite->id}");

        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');

        $this->assertEquals('Want to join?', $attributes['message']);
        $this->assertEquals('pending', $attributes['status']);
        $this->assertArrayHasKey('sentAt', $attributes);
        $this->assertNull($attributes['respondedAt']);
    }
}
