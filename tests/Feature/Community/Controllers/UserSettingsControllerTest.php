<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Enums\Permissions;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testUpdatePassword(): void
    {
        // Arrange
        $this->withoutMiddleware();

        /** @var User $user */
        $user = User::factory()->create([
            'password' => Hash::make('oldPassword123'),
            'connect_token' => 'foo',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.settings.password.update'), [
                'currentPassword' => 'oldPassword123',
                'newPassword' => 'newPassword123',
            ]);

        // Assert
        $response->assertStatus(200)->assertJson(['success' => true]);

        $user = $user->fresh();
        $this->assertTrue(Hash::check('newPassword123', $user->password));
        $this->assertTrue($user->connect_token !== 'foo');
    }

    public function testUpdatePasswordWithWrongCurrentPassword(): void
    {
        // Arrange
        $this->withoutMiddleware();

        /** @var User $user */
        $user = User::factory()->create([
            'password' => Hash::make('oldPassword123'),
            'connect_token' => 'foo',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.settings.password.update'), [
                'currentPassword' => '12345678',
                'newPassword' => 'newPassword123',
            ]);

        // Assert
        $response->assertStatus(422)->assertJson(['message' => 'Incorrect credentials.']);
    }

    public function testUpdatePasswordAsUsername(): void
    {
        // Arrange
        $this->withoutMiddleware();

        /** @var User $user */
        $user = User::factory()->create([
            'username' => 'MyUsername',
            'password' => Hash::make('oldPassword123'),
            'connect_token' => 'foo',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.settings.password.update'), [
                'currentPassword' => 'oldPassword123',
                'newPassword' => 'MyUsername',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson(['message' => 'Your password must be different from your username.']);
    }

    public function testUpdateEmail(): void
    {
        // Arrange
        $this->withoutMiddleware();

        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'foo@bar.com',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.settings.email.update'), [
                'newEmail' => 'bar@baz.com',
            ]);

        // Assert
        $response->assertStatus(200);

        $user = $user->fresh();
        $this->assertEquals('bar@baz.com', $user->email);
        $this->assertEquals(Permissions::Unregistered, (int) $user->getAttribute('Permissions'));
        $this->assertNull($user->email_verified_at);
    }

    public function testUpdateUsername(): void
    {
        // Arrange
        $this->withoutMiddleware();

        /** @var User $user */
        $user = User::factory()->create([
            'username' => 'Scott',
            'display_name' => 'Scott',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson(route('api.settings.name-change-request.store'), [
                'newDisplayName' => 'Scott123456712',
            ]);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('user_usernames', [
            'user_id' => $user->id,
            'username' => 'Scott123456712',
            'approved_at' => null,
        ]);
    }

    public function testUpdateUsernameWithOnlyCapitalizationChange(): void
    {
        // Arrange
        $this->withoutMiddleware();

        /** @var User $user */
        $user = User::factory()->create([
            'username' => 'scott',
            'display_name' => 'scott',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson(route('api.settings.name-change-request.store'), [
                'newDisplayName' => 'Scott',
            ]);

        // Assert
        $response->assertStatus(200);

        $user = $user->fresh();
        $this->assertEquals('Scott', $user->display_name); // instantly makes the update

        $this->assertDatabaseMissing('user_usernames', [ // does not make an approval request record
            'user_id' => $user->id,
            'username' => 'Scott',
        ]);
    }

    public function testUpdateProfile(): void
    {
        // Arrange
        $this->withoutMiddleware();

        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
            'motto' => '',
            'is_user_wall_active' => false,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.settings.profile.update'), [
                'isUserWallActive' => true,
                'motto' => 'New motto',
            ]);

        // Assert
        $response->assertStatus(200);

        $user = $user->fresh();
        $this->assertEquals('New motto', $user->motto);
        $this->assertEquals(true, $user->is_user_wall_active);
    }

    public function testUpdateLocale(): void
    {
        // Arrange
        $this->withoutMiddleware();

        /** @var User $user */
        $user = User::factory()->create([
            'locale' => 'en_US',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.settings.locale.update'), [
                'locale' => 'pt_BR',
            ]);

        // Assert
        $response->assertStatus(200);

        $user = $user->fresh();
        $this->assertEquals('pt_BR', $user->locale);
    }

    public function testUpdateLocaleWithInvalidLocale(): void
    {
        // Arrange
        $this->withoutMiddleware();

        /** @var User $user */
        $user = User::factory()->create([
            'locale' => 'en_US',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.settings.locale.update'), [
                'locale' => 'KLINGON',
            ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['locale']);
    }

    public function testUpdatePreferences(): void
    {
        // Arrange
        $this->withoutMiddleware();

        /** @var User $user */
        $user = User::factory()->create([
            'preferences_bitfield' => 1111,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.settings.preferences.update'), [
                'preferencesBitfield' => 2222,
            ]);

        // Assert
        $response->assertStatus(200);

        $user = $user->fresh();
        $this->assertEquals(2222, $user->preferences_bitfield);
    }

    public function testResetWebApiKey(): void
    {
        // Arrange
        $this->withoutMiddleware();

        /** @var User $user */
        $user = User::factory()->create([
            'web_api_key' => 'foo',
        ]);

        // Act
        $response = $this->actingAs($user)->deleteJson(route('api.settings.keys.web.destroy'));

        // Assert
        $response->assertStatus(200)->assertJsonStructure(['newKey']);
        $this->assertNotEquals('foo', $response->json('newKey'));
    }

    public function testResetConnectApiKey(): void
    {
        // Arrange
        $this->withoutMiddleware();

        /** @var User $user */
        $user = User::factory()->create([
            'connect_token' => 'foo',
        ]);

        // Act
        $response = $this->actingAs($user)->deleteJson(route('api.settings.keys.connect.destroy'));

        // Assert
        $response->assertStatus(200)->assertJson(['success' => true]);

        $user = $user->fresh();
        $this->assertNotEquals('foo', $user->connect_token);
    }
}
