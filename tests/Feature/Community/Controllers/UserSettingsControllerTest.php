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
            'Password' => Hash::make('oldPassword123'),
            'appToken' => 'foo',
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
        $this->assertTrue(Hash::check('newPassword123', $user->Password));
        $this->assertTrue($user->appToken !== 'foo');
    }

    public function testUpdatePasswordWithWrongCurrentPassword(): void
    {
        // Arrange
        $this->withoutMiddleware();

        /** @var User $user */
        $user = User::factory()->create([
            'Password' => Hash::make('oldPassword123'),
            'appToken' => 'foo',
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
            'User' => 'MyUsername',
            'Password' => Hash::make('oldPassword123'),
            'appToken' => 'foo',
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
            'EmailAddress' => 'foo@bar.com',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.settings.email.update'), [
                'newEmail' => 'bar@baz.com',
            ]);

        // Assert
        $response->assertStatus(200);

        $user = $user->fresh();
        $this->assertEquals('bar@baz.com', $user->EmailAddress);
        $this->assertEquals(Permissions::Unregistered, (int) $user->getAttribute('Permissions'));
        $this->assertNull($user->email_verified_at);
    }

    public function testUpdateUsername(): void
    {
        // Arrange
        $this->withoutMiddleware();

        /** @var User $user */
        $user = User::factory()->create([
            'User' => 'Scott',
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
            'User' => 'scott',
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
            'Motto' => '',
            'UserWallActive' => false,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.settings.profile.update'), [
                'motto' => 'New motto',
                'userWallActive' => true,
            ]);

        // Assert
        $response->assertStatus(200);

        $user = $user->fresh();
        $this->assertEquals('New motto', $user->Motto);
        $this->assertEquals(true, $user->UserWallActive);
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
            'websitePrefs' => 1111,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.settings.preferences.update'), [
                'websitePrefs' => 2222,
            ]);

        // Assert
        $response->assertStatus(200);

        $user = $user->fresh();
        $this->assertEquals(2222, $user->websitePrefs);
    }

    public function testResetWebApiKey(): void
    {
        // Arrange
        $this->withoutMiddleware();

        /** @var User $user */
        $user = User::factory()->create([
            'APIKey' => 'foo',
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
            'appToken' => 'foo',
        ]);

        // Act
        $response = $this->actingAs($user)->deleteJson(route('api.settings.keys.connect.destroy'));

        // Assert
        $response->assertStatus(200)->assertJson(['success' => true]);

        $user = $user->fresh();
        $this->assertNotEquals('foo', $user->appToken);
    }
}
