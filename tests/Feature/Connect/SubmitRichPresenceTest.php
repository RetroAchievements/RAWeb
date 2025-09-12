<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\Achievement;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubmitRichPresenceTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    public function testDeveloperCanSubmitRichPresence(): void
    {
        // Arrange
        $user = User::factory()->create(['appToken' => Str::random(16)]);
        $user->assignRole(Role::DEVELOPER);
        $this->user = $user;

        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'RichPresencePatch' => 'Display:\nOriginal RP',
        ]);

        // Act
        $this->post(
            $this->apiUrl('submitrichpresence'),
            ['g' => $game->id, 'd' => 'Display:\nNew RP']
        )
            ->assertExactJson(['Success' => true]);

        // Assert
        $game->refresh();
        $this->assertEquals('Display:\nNew RP', $game->RichPresencePatch);
    }

    public function testJuniorDeveloperWithClaimCanSubmitRichPresence(): void
    {
        // Arrange
        $user = User::factory()->create(['appToken' => Str::random(16)]);
        $user->assignRole(Role::DEVELOPER_JUNIOR);
        $this->user = $user;

        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'RichPresencePatch' => 'Display:\nOriginal RP',
        ]);

        // ... create an active claim for the Junior Developer ...
        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);

        // Act
        $this->post(
            $this->apiUrl('submitrichpresence'),
            ['g' => $game->id, 'd' => 'Display:\nJunior RP']
        )
            ->assertExactJson(['Success' => true]);

        // Assert
        $game->refresh();
        $this->assertEquals('Display:\nJunior RP', $game->RichPresencePatch);
    }

    public function testJuniorDeveloperAsSoleAuthorCanSubmitRichPresence(): void
    {
        // Arrange
        $user = User::factory()->create(['appToken' => Str::random(16)]);
        $user->assignRole(Role::DEVELOPER_JUNIOR);
        $this->user = $user;

        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'RichPresencePatch' => 'Display:\nOriginal RP',
        ]);

        // ... make the Junior Developer the sole author of all achievements ...
        Achievement::factory()->count(3)->create([
            'GameID' => $game->id,
            'user_id' => $user->id,
        ]);

        // Act
        $this->post(
            $this->apiUrl('submitrichpresence'),
            ['g' => $game->id, 'd' => 'Display:\nSole Author RP']
        )
            ->assertExactJson(['Success' => true]);

        // Assert
        $game->refresh();
        $this->assertEquals('Display:\nSole Author RP', $game->RichPresencePatch);
    }

    public function testJuniorDeveloperWithoutPermissionCannotSubmitRichPresence(): void
    {
        // Arrange
        $user = User::factory()->create(['appToken' => Str::random(16)]);
        $user->assignRole(Role::DEVELOPER_JUNIOR);
        $this->user = $user;

        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'RichPresencePatch' => 'Display:\nOriginal RP',
        ]);

        // ... they have no active claim and they're not the sole achievement author ...

        // Act
        $this->post(
            $this->apiUrl('submitrichpresence'),
            ['g' => $game->id, 'd' => 'Display:\nUnauthorized RP']
        )
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
            ]);

        // Assert
        $game->refresh();
        $this->assertEquals('Display:\nOriginal RP', $game->RichPresencePatch); // !! unchanged
    }

    public function testJuniorDeveloperWithMixedAuthorshipCannotSubmitRichPresence(): void
    {
        // Arrange
        $juniorDev = User::factory()->create(['appToken' => Str::random(16)]);
        $juniorDev->assignRole(Role::DEVELOPER_JUNIOR);
        $this->user = $juniorDev;

        $otherDev = User::factory()->create();
        $otherDev->assignRole(Role::DEVELOPER);

        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'RichPresencePatch' => 'Display:\nOriginal RP',
        ]);

        // ... create achievements with mixed authorship ...
        Achievement::factory()->count(3)->create([
            'GameID' => $game->id,
            'user_id' => $juniorDev->id, // !! junior dev authored some achievements
        ]);
        Achievement::factory()->count(3)->create([
            'GameID' => $game->id,
            'user_id' => $otherDev->id, // !! but another dev also authored achievements
        ]);

        // Act
        $this->post(
            $this->apiUrl('submitrichpresence'),
            ['g' => $game->id, 'd' => 'Display:\nMixed Authorship RP']
        )
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
            ]);

        // Assert
        $game->refresh();
        $this->assertEquals('Display:\nOriginal RP', $game->RichPresencePatch); // !! unchanged
    }

    public function testRegularUserCannotSubmitRichPresence(): void
    {
        // Arrange
        $user = User::factory()->create(['appToken' => Str::random(16)]);
        $this->user = $user;

        // ... no developer role! ...

        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'RichPresencePatch' => 'Display:\nOriginal RP',
        ]);

        // Act
        $this->post(
            $this->apiUrl('submitrichpresence'),
            ['g' => $game->id, 'd' => 'Display:\nRegular User RP']
        )
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
            ]);

        // Assert
        $game->refresh();
        $this->assertEquals('Display:\nOriginal RP', $game->RichPresencePatch); // !! unchanged
    }

    public function testInvalidCredentialsCannotSubmitRichPresence(): void
    {
        // Arrange
        $user = User::factory()->create(['appToken' => Str::random(16)]);
        $user->assignRole(Role::DEVELOPER);
        $this->user = $user;

        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'RichPresencePatch' => 'Display:\nOriginal RP',
        ]);

        // Act
        $this->post(
            'dorequest.php?r=submitrichpresence',
            ['g' => $game->id, 'u' => $user->username, 't' => 'InvalidToken', 'd' => 'Display:\nInvalid RP']
        )
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
            ]);

        // Assert
        $game->refresh();
        $this->assertEquals('Display:\nOriginal RP', $game->RichPresencePatch); // !! unchanged
    }

    public function testSubmitUnchangedRichPresenceReturnsSuccessWithoutModifying(): void
    {
        // Arrange
        $user = User::factory()->create(['appToken' => Str::random(16)]);
        $user->assignRole(Role::DEVELOPER);
        $this->user = $user;

        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'RichPresencePatch' => 'Display:\nOriginal RP',
        ]);

        // Act
        $this->post(
            $this->apiUrl('submitrichpresence'),
            ['g' => $game->id, 'd' => 'Display:\nOriginal RP'] // !! submitting the exact same rich presence
        )
            ->assertExactJson(['Success' => true]);

        // Assert
        $game->refresh();
        $this->assertEquals('Display:\nOriginal RP', $game->RichPresencePatch); // !! unchanged
    }

    public function testSubmitEmptyRichPresenceClearsIt(): void
    {
        // Arrange
        $user = User::factory()->create(['appToken' => Str::random(16)]);
        $user->assignRole(Role::DEVELOPER);
        $this->user = $user;

        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'RichPresencePatch' => 'Display:\nOriginal RP',
        ]);

        // Act
        $this->post(
            $this->apiUrl('submitrichpresence'),
            ['g' => $game->id, 'd' => '']
        )
            ->assertExactJson(['Success' => true]);

        // Assert
        $game->refresh();
        $this->assertEquals('', $game->RichPresencePatch);
    }

    public function testMissingDataParameterReturnsError(): void
    {
        // Arrange
        $user = User::factory()->create(['appToken' => Str::random(16)]);
        $user->assignRole(Role::DEVELOPER);
        $this->user = $user;

        $system = System::factory()->create();
        $game = Game::factory()->create([
            'ConsoleID' => $system->id,
            'RichPresencePatch' => 'Display:\nOriginal RP',
        ]);

        // Act
        // ... don't send the 'd' parameter at all ...
        $this->post(
            $this->apiUrl('submitrichpresence'),
            ['g' => $game->id]
        )
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Error' => 'One or more required parameters is missing.',
            ]);

        // Assert
        $game->refresh();
        $this->assertEquals('Display:\nOriginal RP', $game->RichPresencePatch); // !! unchanged
    }

    public function testReturnsErrorForNonexistentGame(): void
    {
        // Arrange
        $user = User::factory()->create(['appToken' => Str::random(16)]);
        $user->assignRole(Role::DEVELOPER);
        $this->user = $user;

        // Assert
        $this->post(
            $this->apiUrl('submitrichpresence'),
            ['g' => 99999, 'd' => 'Display:\nNonexistent RP']
        )
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Status' => 404,
                'Code' => 'not_found',
                'Error' => 'Unknown game.',
            ]);
    }

    public function testMissingParametersReturnsError(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['appToken' => Str::random(16)]);
        $user->assignRole(Role::DEVELOPER);
        $this->user = $user;

        // Assert
        $this->post(
            $this->apiUrl('submitrichpresence'),
            ['d' => 'Display:\nMissing Game ID']
        )
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Error' => 'One or more required parameters is missing.',
            ]);
    }
}
