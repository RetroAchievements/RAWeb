<?php

declare(strict_types=1);

use App\Enums\OAuthScope;
use App\Models\OAuthClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('feature.oauth', true);
});

it('registers a confidential application and reveals the secret once', function () {
    // ARRANGE
    /** @var User $user */
    $user = User::factory()->create(['email_verified_at' => now(), 'created_at' => now()->subMonths(1)]);
    $this->actingAs($user);

    // ACT
    $response = $this->postJson(route('api.settings.applications.store'), [
        'name' => 'Achievement Companion',
        'redirectUris' => ['https://companion.example/callback'],
        'type' => 'confidential',
        'enableDeviceFlow' => true,
    ]);

    // ASSERT
    $response->assertSuccessful()->assertJsonStructure(['id', 'secret']);

    $client = OAuthClient::query()->findOrFail($response->json('id'));
    expect(Hash::check($response->json('secret'), $client->getRawOriginal('secret')))->toBeTrue();
    expect($client->grant_types)->toContain('urn:ietf:params:oauth:grant-type:device_code');
});

it('rejects unsafe redirect URIs', function () {
    // ARRANGE
    /** @var User $user */
    $user = User::factory()->create(['email_verified_at' => now(), 'created_at' => now()->subMonths(1)]);
    $this->actingAs($user);

    // ACT
    $response = $this->postJson(route('api.settings.applications.store'), [
        'name' => 'Unsafe Redirect Client',
        'redirectUris' => ['javascript:alert(1)#fragment'],
        'type' => 'confidential',
    ]);

    // ASSERT
    $response->assertUnprocessable()->assertJsonValidationErrors(['redirectUris.0']);
});

it('registers a public application with a custom scheme redirect URI', function () {
    // ARRANGE
    /** @var User $user */
    $user = User::factory()->create(['email_verified_at' => now(), 'created_at' => now()->subMonths(1)]);
    $this->actingAs($user);

    // ACT
    $response = $this->postJson(route('api.settings.applications.store'), [
        'name' => 'Native Emulator',
        'redirectUris' => ['myapp://oauth/callback'],
        'type' => 'public',
    ]);

    // ASSERT
    $response->assertSuccessful();

    $client = OAuthClient::query()->findOrFail($response->json('id'));
    expect($client->confidential())->toBeFalse();
});

it('rejects a custom scheme redirect URI for a confidential application', function () {
    // ARRANGE
    /** @var User $user */
    $user = User::factory()->create(['email_verified_at' => now(), 'created_at' => now()->subMonths(1)]);
    $this->actingAs($user);

    // ACT
    $response = $this->postJson(route('api.settings.applications.store'), [
        'name' => 'Server Integration',
        'redirectUris' => ['myapp://oauth/callback'],
        'type' => 'confidential',
    ]);

    // ASSERT
    $response->assertUnprocessable()->assertJsonValidationErrors(['redirectUris.0']);
});

it('keeps confidential redirect validation when an update tries to pass a public type', function () {
    // ARRANGE
    /** @var User $user */
    $user = User::factory()->create(['email_verified_at' => now(), 'created_at' => now()->subMonths(1)]);
    $client = OAuthClient::factory()->create([
        'owner_type' => $user->getMorphClass(),
        'owner_id' => $user->id,
    ]);
    $this->actingAs($user);

    // ACT
    $response = $this->putJson(route('api.settings.applications.update', ['client' => $client->id]), [
        'name' => 'Still Confidential',
        'redirectUris' => ['myapp://oauth/callback'],
        'type' => 'public',
    ]);

    // ASSERT
    $response->assertUnprocessable()->assertJsonValidationErrors(['redirectUris.0']);
});

it('rejects registration with a validation error when the user is at their quota', function () {
    // ARRANGE
    config()->set('oauth.max_applications_per_user', 1);

    /** @var User $user */
    $user = User::factory()->create(['email_verified_at' => now(), 'created_at' => now()->subMonths(1)]);
    OAuthClient::factory()->create(['owner_type' => $user->getMorphClass(), 'owner_id' => $user->id]);
    $this->actingAs($user);

    // ACT
    $response = $this->postJson(route('api.settings.applications.store'), [
        'name' => 'One Too Many',
        'redirectUris' => ['https://example.com/callback'],
        'type' => 'confidential',
    ]);

    // ASSERT
    $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
    expect(OAuthClient::query()->active()->ownedBy($user)->count())->toEqual(1);
});

it('forbids registration for an unverified user', function () {
    // ARRANGE
    /** @var User $user */
    $user = User::factory()->create(['email_verified_at' => null]);
    $this->actingAs($user);

    // ACT
    $response = $this->postJson(route('api.settings.applications.store'), [
        'name' => 'Unverified Client',
        'redirectUris' => ['https://example.com/callback'],
        'type' => 'confidential',
    ]);

    // ASSERT
    $response->assertForbidden();
});

it('forbids registration for a fresh account', function () {
    // ARRANGE
    /** @var User $user */
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $this->actingAs($user);

    // ACT
    $response = $this->postJson(route('api.settings.applications.store'), [
        'name' => 'Fresh Account Client',
        'redirectUris' => ['https://example.com/callback'],
        'type' => 'confidential',
    ]);

    // ASSERT
    $response->assertForbidden();
});

it('forbids registration when the OAuth feature is disabled', function () {
    // ARRANGE
    config()->set('feature.oauth', false);

    /** @var User $user */
    $user = User::factory()->create(['email_verified_at' => now(), 'created_at' => now()->subMonths(1)]);
    $this->actingAs($user);

    // ACT
    $response = $this->postJson(route('api.settings.applications.store'), [
        'name' => 'Disabled Feature Client',
        'redirectUris' => ['https://example.com/callback'],
        'type' => 'confidential',
    ]);

    // ASSERT
    $response->assertForbidden();
});

it('updates an application owned by the user', function () {
    // ARRANGE
    /** @var User $user */
    $user = User::factory()->create(['email_verified_at' => now(), 'created_at' => now()->subMonths(1)]);
    $client = OAuthClient::factory()->create([
        'owner_type' => $user->getMorphClass(),
        'owner_id' => $user->id,
    ]);
    $this->actingAs($user);

    // ACT
    $response = $this->putJson(route('api.settings.applications.update', ['client' => $client->id]), [
        'name' => 'Renamed Client',
        'redirectUris' => ['https://renamed.example/callback'],
    ]);

    // ASSERT
    $response->assertSuccessful()->assertJsonFragment(['name' => 'Renamed Client']);
    expect($client->refresh()->redirect_uris)->toEqual(['https://renamed.example/callback']);
});

it('forbids updating an application owned by someone else', function () {
    // ARRANGE
    /** @var User $owner */
    $owner = User::factory()->create();
    /** @var User $otherUser */
    $otherUser = User::factory()->create(['email_verified_at' => now(), 'created_at' => now()->subMonths(1)]);
    $client = OAuthClient::factory()->create([
        'owner_type' => $owner->getMorphClass(),
        'owner_id' => $owner->id,
    ]);
    $this->actingAs($otherUser);

    // ACT
    $response = $this->putJson(route('api.settings.applications.update', ['client' => $client->id]), [
        'name' => 'Hijacked Client',
        'redirectUris' => ['https://evil.example/callback'],
    ]);

    // ASSERT
    $response->assertForbidden();
});

it('regenerates the secret of a confidential application', function () {
    // ARRANGE
    /** @var User $user */
    $user = User::factory()->create(['email_verified_at' => now(), 'created_at' => now()->subMonths(1)]);
    $client = OAuthClient::factory()->create([
        'owner_type' => $user->getMorphClass(),
        'owner_id' => $user->id,
    ]);
    $originalSecret = $client->getRawOriginal('secret');
    $this->actingAs($user);

    // ACT
    $response = $this->postJson(route('api.settings.applications.regenerate-secret', ['client' => $client->id]));

    // ASSERT
    $response->assertSuccessful()->assertJsonStructure(['id', 'secret']);
    expect($client->refresh()->getRawOriginal('secret'))->not->toEqual($originalSecret);
});

it('refuses to regenerate the secret of a public application', function () {
    // ARRANGE
    /** @var User $user */
    $user = User::factory()->create(['email_verified_at' => now(), 'created_at' => now()->subMonths(1)]);
    $client = OAuthClient::factory()->public()->create([
        'owner_type' => $user->getMorphClass(),
        'owner_id' => $user->id,
    ]);
    $this->actingAs($user);

    // ACT
    $response = $this->postJson(route('api.settings.applications.regenerate-secret', ['client' => $client->id]));

    // ASSERT
    $response->assertUnprocessable();
});

it('deactivates an application and revokes its tokens', function () {
    // ARRANGE
    /** @var User $user */
    $user = User::factory()->create(['email_verified_at' => now(), 'created_at' => now()->subMonths(1)]);
    $client = OAuthClient::factory()->create([
        'owner_type' => $user->getMorphClass(),
        'owner_id' => $user->id,
    ]);
    $accessToken = Passport::token()->forceCreate([
        'id' => 'access-token-1',
        'user_id' => $user->id,
        'client_id' => $client->id,
        'scopes' => [OAuthScope::Read->value],
        'revoked' => false,
    ]);
    Passport::refreshToken()->forceCreate([
        'id' => 'refresh-token-1',
        'access_token_id' => $accessToken->id,
        'revoked' => false,
    ]);
    $this->actingAs($user);

    // ACT
    $response = $this->deleteJson(route('api.settings.applications.destroy', ['client' => $client->id]));

    // ASSERT
    $response->assertSuccessful()->assertJson(['success' => true]);
    expect($client->refresh()->revoked)->toBeTrue();
    expect((bool) $accessToken->refresh()->revoked)->toBeTrue();
    expect((bool) Passport::refreshToken()->newQuery()->findOrFail('refresh-token-1')->revoked)->toBeTrue();
});
