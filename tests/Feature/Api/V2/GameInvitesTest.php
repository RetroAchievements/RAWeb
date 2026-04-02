<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Community\Actions\CreateGameInviteAction;
use App\Community\Actions\RespondToGameInviteAction;
use App\Community\Actions\CancelGameInviteAction;
use App\Models\Game;
use App\Models\GameInvite;
use App\Models\User;
use App\Platform\Enums\GameInviteStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GameInvitesTest extends JsonApiResourceTestCase
{
    protected function resourceType(): string
    {
        return 'game-invites';
    }

    protected function resourceEndpoint(): string
    {
        return '/api/v2/game-invites';
    }

    protected function createResource(): GameInvite
    {
        /** @var User $sender */
        // Find existing user with API key, or create one if it doesn't exist
        $sender = User::where('web_api_key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')->first()
            ?? User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        /** @var User $recipient */
        $recipient = User::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create();

        return (new CreateGameInviteAction())->execute($sender, $recipient, $game, 'Test game invite.');
    }

    public function testItRequiresAuthentication(): void
    {
        // Arrange
        $this->createResource();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-invites')
            ->get($this->resourceEndpoint());

        // Assert
        $response->assertUnauthorized();
    }

    public function testItListsGameInvites(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        $invite = $this->createResource();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-invites')
            ->withHeader('X-API-Key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')
            ->get($this->resourceEndpoint());

        // Assert
        $response->assertFetchedMany([
            ['type' => 'game-invites', 'id' => (string) $invite->id],
        ]);
    }

    public function testItOnlyReturnsInvitesForAuthenticatedUser(): void
    {
        // Arrange
        /** @var User $user1 */
        $user1 = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        /** @var User $user2 */
        $user2 = User::factory()->create(['web_api_key' => 'other-key']);
        /** @var User $user3 */
        $user3 = User::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create();

        $invite1 = (new CreateGameInviteAction())->execute($user1, $user3, $game, 'Invite 1');
        $invite2 = (new CreateGameInviteAction())->execute($user3, $user2, $game, 'Invite 2');

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-invites')
            ->withHeader('X-API-Key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')
            ->get($this->resourceEndpoint());

        // Assert
        $response->assertFetchedMany([
            ['type' => 'game-invites', 'id' => (string) $invite1->id],
        ]);
        $response->assertJsonMissing(['id' => (string) $invite2->id]);
    }

    public function testItFiltersByStatus(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        /** @var User $other */
        $other = User::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create();

        $pendingInvite = (new CreateGameInviteAction())->execute($user, $other, $game, 'Pending');
        $acceptedInvite = (new CreateGameInviteAction())->execute($other, $user, $game, 'Accepted');
        (new RespondToGameInviteAction())->execute($acceptedInvite, $user, GameInviteStatus::Accepted);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-invites')
            ->withHeader('X-API-Key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')
            ->get($this->resourceEndpoint() . '?filter[status]=pending');

        // Assert
        $response->assertFetchedMany([
            ['type' => 'game-invites', 'id' => (string) $pendingInvite->id],
        ]);
        $response->assertJsonMissing(['id' => (string) $acceptedInvite->id]);
    }

    public function testItFiltersByRole(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        /** @var User $other */
        $other = User::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create();

        $sentInvite = (new CreateGameInviteAction())->execute($user, $other, $game, 'Sent');
        $receivedInvite = (new CreateGameInviteAction())->execute($other, $user, $game, 'Received');

        // Act - filter by sent
        $response = $this->jsonApi('v2')
            ->expects('game-invites')
            ->withHeader('X-API-Key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')
            ->get($this->resourceEndpoint() . '?filter[role]=sent');

        // Assert
        $response->assertFetchedMany([
            ['type' => 'game-invites', 'id' => (string) $sentInvite->id],
        ]);
        $response->assertJsonMissing(['id' => (string) $receivedInvite->id]);
    }

    public function testItCreatesGameInvite(): void
    {
        // Arrange
        /** @var User $sender */
        $sender = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        /** @var User $recipient */
        $recipient = User::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-invites')
            ->withHeader('X-API-Key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')
            ->withJson([
                'data' => [
                    'type' => 'game-invites',
                    'attributes' => [
                        'message' => 'Want to play this game together?',
                    ],
                    'relationships' => [
                        'game' => [
                            'data' => [
                                'type' => 'games',
                                'id' => (string) $game->id,
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

        // Assert
        $response->assertCreated();
        $this->assertDatabaseHas('game_invites', [
            'sender_user_id' => $sender->id,
            'recipient_user_id' => $recipient->id,
            'game_id' => $game->id,
            'message' => 'Want to play this game together?',
            'status' => GameInviteStatus::Pending->value,
        ]);
    }

    public function testItRejectsInviteToSelf(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        /** @var Game $game */
        $game = Game::factory()->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-invites')
            ->withHeader('X-API-Key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')
            ->withJson([
                'data' => [
                    'type' => 'game-invites',
                    'attributes' => [
                        'message' => 'Invite to self.',
                    ],
                    'relationships' => [
                        'game' => [
                            'data' => [
                                'type' => 'games',
                                'id' => (string) $game->id,
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

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('errors.0.detail', 'You cannot invite yourself to a game.');
    }

    public function testItAcceptsGameInvite(): void
    {
        // Arrange
        /** @var User $sender */
        $sender = User::factory()->create();
        /** @var User $recipient */
        $recipient = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        /** @var Game $game */
        $game = Game::factory()->create();

        $invite = (new CreateGameInviteAction())->execute($sender, $recipient, $game, 'Play together?');

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-invites')
            ->withHeader('X-API-Key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')
            ->withJson([
                'data' => [
                    'type' => 'game-invites',
                    'id' => (string) $invite->id,
                    'attributes' => [
                        'status' => 'accepted',
                    ],
                ],
            ])
            ->patch("{$this->resourceEndpoint()}/{$invite->id}");

        // Assert
        $response->assertSuccessful();
        $this->assertDatabaseHas('game_invites', [
            'id' => $invite->id,
            'status' => GameInviteStatus::Accepted->value,
            'responded_at' => now(),
        ]);
    }

    public function testItDeclinesGameInvite(): void
    {
        // Arrange
        /** @var User $sender */
        $sender = User::factory()->create();
        /** @var User $recipient */
        $recipient = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        /** @var Game $game */
        $game = Game::factory()->create();

        $invite = (new CreateGameInviteAction())->execute($sender, $recipient, $game, 'Play together?');

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-invites')
            ->withHeader('X-API-Key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')
            ->withJson([
                'data' => [
                    'type' => 'game-invites',
                    'id' => (string) $invite->id,
                    'attributes' => [
                        'status' => 'declined',
                    ],
                ],
            ])
            ->patch("{$this->resourceEndpoint()}/{$invite->id}");

        // Assert
        $response->assertSuccessful();
        $this->assertDatabaseHas('game_invites', [
            'id' => $invite->id,
            'status' => GameInviteStatus::Declined->value,
            'responded_at' => now(),
        ]);
    }

    public function testItCancelsGameInvite(): void
    {
        // Arrange
        /** @var User $sender */
        $sender = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        /** @var User $recipient */
        $recipient = User::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create();

        $invite = (new CreateGameInviteAction())->execute($sender, $recipient, $game, 'Play together?');

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-invites')
            ->withHeader('X-API-Key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')
            ->withJson([
                'data' => [
                    'type' => 'game-invites',
                    'id' => (string) $invite->id,
                    'attributes' => [
                        'status' => 'canceled',
                    ],
                ],
            ])
            ->patch("{$this->resourceEndpoint()}/{$invite->id}");

        // Assert
        $response->assertSuccessful();
        $this->assertDatabaseHas('game_invites', [
            'id' => $invite->id,
            'status' => GameInviteStatus::Canceled->value,
            'responded_at' => now(),
        ]);
    }

    public function testItPreventsInvalidStatusTransitions(): void
    {
        // Arrange
        /** @var User $sender */
        $sender = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        /** @var User $recipient */
        $recipient = User::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create();

        $invite = (new CreateGameInviteAction())->execute($sender, $recipient, $game, 'Play together?');
        (new RespondToGameInviteAction())->execute($invite, $recipient, GameInviteStatus::Accepted);

        // Act - try to accept again
        $response = $this->jsonApi('v2')
            ->expects('game-invites')
            ->withHeader('X-API-Key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')
            ->withJson([
                'data' => [
                    'type' => 'game-invites',
                    'id' => (string) $invite->id,
                    'attributes' => [
                        'status' => 'accepted',
                    ],
                ],
            ])
            ->patch("{$this->resourceEndpoint()}/{$invite->id}");

        // Assert
        $response->assertStatus(403);
        $response->assertJsonPath('errors.0.title', 'You cannot modify this game invite.');
    }

    public function testItReturnsCorrectAttributes(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['web_api_key' => 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6']);
        $invite = $this->createResource();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-invites')
            ->withHeader('X-API-Key', 'wmrNeX9IawcBY1rwZZTCXh8EYpIuAyD6')
            ->get("{$this->resourceEndpoint()}/{$invite->id}");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.attributes');

        $this->assertEquals('Test game invite.', $attributes['message']);
        $this->assertEquals('pending', $attributes['status']);
        $this->assertArrayHasKey('sentAt', $attributes);
        $this->assertArrayHasKey('expiresAt', $attributes);
        $this->assertNull($attributes['respondedAt']);
    }
}
