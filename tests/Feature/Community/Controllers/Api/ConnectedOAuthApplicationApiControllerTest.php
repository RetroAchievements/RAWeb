<?php

declare(strict_types=1);

use App\Community\Actions\RecordOAuthGrantAction;
use App\Enums\OAuthScope;
use App\Models\OAuthClient;
use App\Models\OAuthGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('feature.oauth', true);
});

function createOAuthAccessToken(User $user, OAuthClient $client, string $id): object
{
    return Passport::token()->forceCreate([
        'id' => $id,
        'user_id' => $user->id,
        'client_id' => $client->id,
        'scopes' => [OAuthScope::Read->value],
        'revoked' => false,
    ]);
}

function createOAuthRefreshToken(string $id, string $accessTokenId): void
{
    Passport::refreshToken()->forceCreate([
        'id' => $id,
        'access_token_id' => $accessTokenId,
        'revoked' => false,
    ]);
}

it('revokes the grant and the callers tokens', function () {
    // ARRANGE
    /** @var User $user */
    $user = User::factory()->create();
    $client = OAuthClient::factory()->create();
    $grant = app(RecordOAuthGrantAction::class)->execute($user, (string) $client->id, [OAuthScope::Read->value]);

    $accessToken = createOAuthAccessToken($user, $client, 'access-token-1');
    createOAuthRefreshToken('refresh-token-1', 'access-token-1');

    $this->actingAs($user);

    // ACT
    $response = $this->deleteJson(
        route('api.settings.connected-applications.destroy', ['client' => $client->id])
    );

    // ASSERT
    $response->assertSuccessful()->assertJson(['success' => true]);
    expect($grant->refresh()->revoked_at)->not->toBeNull();
    expect((bool) $accessToken->refresh()->revoked)->toBeTrue();
    expect((bool) Passport::refreshToken()->newQuery()->findOrFail('refresh-token-1')->revoked)->toBeTrue();
});

it('leaves other users tokens for the same application alone', function () {
    // ARRANGE
    /** @var User $user */
    $user = User::factory()->create();
    /** @var User $otherUser */
    $otherUser = User::factory()->create();
    $client = OAuthClient::factory()->create();

    app(RecordOAuthGrantAction::class)->execute($user, (string) $client->id, [OAuthScope::Read->value]);
    app(RecordOAuthGrantAction::class)->execute($otherUser, (string) $client->id, [OAuthScope::Read->value]);

    createOAuthAccessToken($user, $client, 'access-token-1');
    $otherAccessToken = createOAuthAccessToken($otherUser, $client, 'access-token-2');

    $this->actingAs($user);

    // ACT
    $this->deleteJson(route('api.settings.connected-applications.destroy', ['client' => $client->id]));

    // ASSERT
    expect((bool) $otherAccessToken->refresh()->revoked)->toBeFalse();
    expect($client->refresh()->revoked)->toBeFalse();
    expect(OAuthGrant::query()->whereBelongsTo($otherUser)->firstOrFail()->revoked_at)->toBeNull();
});

it('still revokes while the OAuth feature is disabled', function () {
    // ARRANGE
    /** @var User $user */
    $user = User::factory()->create();
    $client = OAuthClient::factory()->create();
    $grant = app(RecordOAuthGrantAction::class)->execute($user, (string) $client->id, [OAuthScope::Read->value]);

    config()->set('feature.oauth', false);
    $this->actingAs($user);

    // ACT
    $response = $this->deleteJson(
        route('api.settings.connected-applications.destroy', ['client' => $client->id])
    );

    // ASSERT
    $response->assertSuccessful();
    expect($grant->refresh()->revoked_at)->not->toBeNull();
});

it('returns not found for a grant the caller does not hold', function () {
    // ARRANGE
    /** @var User $user */
    $user = User::factory()->create();
    /** @var User $otherUser */
    $otherUser = User::factory()->create();
    $client = OAuthClient::factory()->create();

    app(RecordOAuthGrantAction::class)->execute($otherUser, (string) $client->id, [OAuthScope::Read->value]);

    $this->actingAs($user);

    // ACT
    $response = $this->deleteJson(
        route('api.settings.connected-applications.destroy', ['client' => $client->id])
    );

    // ASSERT
    $response->assertNotFound();
    expect(OAuthGrant::query()->whereBelongsTo($otherUser)->firstOrFail()->revoked_at)->toBeNull();
});

it('reopens the existing row when a grant is recorded again', function () {
    // ARRANGE
    /** @var User $user */
    $user = User::factory()->create();
    $client = OAuthClient::factory()->create();
    $action = app(RecordOAuthGrantAction::class);

    // ACT
    $grant = $action->execute($user, (string) $client->id, [OAuthScope::Read->value]);
    $grant->update(['revoked_at' => now()]);
    $reopenedGrant = $action->execute($user, (string) $client->id, [OAuthScope::Read->value]);

    // ASSERT
    expect($reopenedGrant->id)->toEqual($grant->id);
    expect($reopenedGrant->revoked_at)->toBeNull();
});
