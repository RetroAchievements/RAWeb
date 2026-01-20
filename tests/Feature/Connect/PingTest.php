<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\PlayerSession;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\AssociateAchievementSetToGameAction;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class PingTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testPing(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var Game $game */
        $game = $this->seedGame();
        $gameHash = $game->hashes->first();

        $this->user->rich_presence_game_id = $game->id;
        $this->user->save();

        // this API requires POST
        $this->post('dorequest.php', $this->apiParams('ping', ['g' => $game->id, 'm' => 'Doing good', 'x' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
            ]);

        // player session resumed
        $playerSession = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
        ])->first();
        $this->assertModelExists($playerSession);
        $this->assertEquals(1, $playerSession->duration);
        $this->assertEquals('Doing good', $playerSession->rich_presence);
        $this->assertEquals($gameHash->id, $playerSession->game_hash_id);

        /** @var User $user1 */
        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($game->id, $user1->rich_presence_game_id);
        $this->assertEquals('Doing good', $user1->rich_presence);

        // string sent by GET will not update user's rich presence message
        $this->get($this->apiUrl('ping', ['g' => $game->id, 'm' => 'Doing better', 'x' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
            ]);

        // player session resumed
        $playerSession2 = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
        ])->first();
        $this->assertEquals($playerSession->id, $playerSession2->id);
        $this->assertEquals(1, $playerSession2->duration);
        $this->assertEquals('Doing good', $playerSession2->rich_presence);

        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($game->id, $user1->rich_presence_game_id);
        $this->assertEquals('Doing good', $user1->rich_presence);

        // invalid UTF-8 should be sanitized
        $this->post('dorequest.php', $this->apiParams('ping', ['g' => $game->id, 'm' => "T\xC3\xA9st t\xC3st", 'x' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
            ]);

        // player session resumed
        $playerSession2 = PlayerSession::where([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
        ])->first();
        $this->assertEquals($playerSession->id, $playerSession2->id);
        $this->assertEquals(1, $playerSession2->duration);
        $this->assertEquals('Tést t?st', $playerSession2->rich_presence);
        $this->assertEquals($gameHash->id, $playerSession2->game_hash_id);

        $user1 = User::whereName($this->user->username)->first();
        $this->assertEquals($game->id, $user1->rich_presence_game_id);
        $this->assertEquals('Tést t?st', $user1->rich_presence);
    }

    public function testPingInvalidGame(): void
    {
        $this->post('dorequest.php', $this->apiParams('ping', ['g' => 123456789]))
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Status' => 404,
                'Code' => 'not_found',
                'Error' => 'Unknown game.',
            ]);

        $this->post('dorequest.php', $this->apiParams('ping', ['g' => 'abcdef']))
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Status' => 404,
                'Code' => 'not_found',
                'Error' => 'Unknown game.',
            ]);
    }

    public function testPingInvalidUser(): void
    {
        /** @var Game $game */
        $game = $this->seedGame();

        $params = [
            'u' => 'UnknownUser',
            't' => 'ABCDEFGHIJK',
            'r' => 'ping',
            'g' => $game->id,
            'm' => 'Doing good',
        ];

        // try with unknown user
        $this->post('dorequest.php', $params)
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/token combination.',
            ]);

        // try with incorrect token
        $params['u'] = $this->user->username;

        $this->post('dorequest.php', $params)
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/token combination.',
            ]);

        // try with banned user
        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Banned, 'connect_token' => Str::random(16)]);

        $params['u'] = $user->username;
        $params['t'] = $user->connect_token;

        $this->post('dorequest.php', $params)
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
            ]);
    }

    public function testPingUnregisteredUser(): void
    {
        // NOTE: Attempting to lump this in the previous test case doesn't work. The
        // response indicates the function was still validating the banned user, despite
        // passing the credentials for the unregistered user. This seems to be a known
        // issue (https://stackoverflow.com/questions/37418155/post-bodies-ignored-when-making-multiple-post-calls-in-laravel-test),
        // but the provided solution didn't seem to work.
        // While a separate test case is desirable, the overhead or refreshing the database
        // is not desirable, which is why most of the test cases are lumped in singular functions.

        /** @var Game $game */
        $game = $this->seedGame();

        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Unregistered, 'connect_token' => Str::random(16)]);

        $params = [
            'u' => $user->username,
            't' => $user->connect_token,
            'r' => 'ping',
            'g' => $game->id,
            'm' => 'Doing good',
        ];

        $this->post('dorequest.php', $params)
            ->assertStatus(403)
            ->assertExactJson([
                'Success' => false,
                'Status' => 403,
                'Code' => 'access_denied',
                'Error' => 'Access denied. Please verify your email address.',
            ]);
    }

    public function testPingUserTokenMismatch(): void
    {
        // NOTE: Attempting to lump this in the previous test case doesn't work. The
        // response indicates the function was still validating the banned user, despite
        // passing the credentials for the unregistered user. This seems to be a known
        // issue (https://stackoverflow.com/questions/37418155/post-bodies-ignored-when-making-multiple-post-calls-in-laravel-test),
        // but the provided solution didn't seem to work.
        // While a separate test case is desirable, the overhead or refreshing the database
        // is not desirable, which is why most of the test cases are lumped in singular functions.

        /** @var Game $game */
        $game = $this->seedGame();

        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);

        $params = [
            'u' => $user->username,
            't' => $this->user->connect_token,
            'r' => 'ping',
            'g' => $game->id,
            'm' => 'Doing good',
        ];

        $this->post('dorequest.php', $params)
            ->assertStatus(401)
            ->assertHeader('WWW-Authenticate', 'Bearer')
            ->assertExactJson([
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/token combination.',
            ]);
    }

    public function testPingDelegatedByName(): void
    {
        /** @var System $standalonesSystem */
        $standalonesSystem = System::factory()->create(['id' => 102]);
        /** @var Game $gameOne */
        $gameOne = Game::factory()->create(['system_id' => $standalonesSystem->id]);

        /** @var User $integrationUser */
        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);
        /** @var User $delegatedUser */
        $delegatedUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);

        $delegatedUser->rich_presence_game_id = $gameOne->id;
        $delegatedUser->save();

        // The integration user is the sole author of all the set's achievements.
        Achievement::factory()->promoted()->count(6)->create([
            'game_id' => $gameOne->id,
            'user_id' => $integrationUser->id,
        ]);

        $params = [
            'u' => $integrationUser->username,
            't' => $integrationUser->connect_token,
            'r' => 'ping',
            'g' => $gameOne->id,
            'm' => 'Doing good',
            'k' => $delegatedUser->username, // !!
        ];

        $this->post('dorequest.php', $params)
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
            ]);

        $delegatedPlayerSession = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $gameOne->id,
        ])->first();
        $this->assertModelExists($delegatedPlayerSession);
        $this->assertEquals(1, $delegatedPlayerSession->duration);
        $this->assertEquals('Doing good', $delegatedPlayerSession->rich_presence);

        // While delegating, updates are made on behalf of username `k`.
        $this->assertDatabaseMissing((new PlayerSession())->getTable(), [
            'user_id' => $integrationUser->id,
            'game_id' => $gameOne->id,
        ]);

        // Next, try to delegate for an unknown user.
        $params['k'] = 'IDontExist';
        $this->post('dorequest.php', $params)
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Status' => 404,
                'Code' => 'not_found',
                'Error' => 'Unknown target user.',
            ]);

        // Next, try to delegate on a non-standalone game.
        // This is not allowed and should fail.
        /** @var System $normalSystem */
        $normalSystem = System::factory()->create(['id' => 1]);
        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create(['system_id' => $normalSystem->id]);

        $params['k'] = $delegatedUser->username;
        $params['g'] = $gameTwo->id;

        $this->post('dorequest.php', $params)
            ->assertStatus(403)
            ->assertExactJson([
                "Success" => false,
                "Error" => "Access denied.",
                "Code" => "access_denied",
                "Status" => 403,
            ]);

        // Next, try to delegate on a game with no achievements authored by the integration user.
        // This is not allowed and should fail.
        /** @var Game $gameThree */
        $gameThree = Game::factory()->create(['system_id' => $standalonesSystem->id]);
        /** @var User $randomUser */
        $randomUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);
        Achievement::factory()->promoted()->count(6)->create(['game_id' => $gameThree->id, 'user_id' => $randomUser->id]);
        $params['g'] = $gameThree->id;

        $this->post('dorequest.php', $params)
            ->assertStatus(403)
            ->assertExactJson([
                "Success" => false,
                "Error" => "Access denied.",
                "Code" => "access_denied",
                "Status" => 403,
            ]);
    }

    public function testPingDelegatedByUlid(): void
    {
        /** @var System $standalonesSystem */
        $standalonesSystem = System::factory()->create(['id' => 102]);
        /** @var Game $gameOne */
        $gameOne = $this->seedGame(system: $standalonesSystem);

        /** @var User $integrationUser */
        $integrationUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);
        /** @var User $delegatedUser */
        $delegatedUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);

        $delegatedUser->rich_presence_game_id = $gameOne->id;
        $delegatedUser->save();

        // The integration user is the sole author of all the set's achievements.
        Achievement::factory()->promoted()->count(6)->create([
            'game_id' => $gameOne->id,
            'user_id' => $integrationUser->id,
        ]);

        $params = [
            'u' => $integrationUser->username,
            't' => $integrationUser->connect_token,
            'r' => 'ping',
            'g' => $gameOne->id,
            'm' => 'Doing good',
            'k' => $delegatedUser->ulid, // !!
        ];

        $this->post('dorequest.php', $params)
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
            ]);

        $delegatedPlayerSession = PlayerSession::where([
            'user_id' => $delegatedUser->id,
            'game_id' => $gameOne->id,
        ])->first();
        $this->assertModelExists($delegatedPlayerSession);
        $this->assertEquals(1, $delegatedPlayerSession->duration);
        $this->assertEquals('Doing good', $delegatedPlayerSession->rich_presence);

        // While delegating, updates are made on behalf of username `k`.
        $this->assertDatabaseMissing((new PlayerSession())->getTable(), [
            'user_id' => $integrationUser->id,
            'game_id' => $gameOne->id,
        ]);

        // Next, try to delegate on a non-standalone game.
        // This is not allowed and should fail.
        /** @var System $normalSystem */
        $normalSystem = System::factory()->create(['id' => 1]);
        /** @var Game $gameTwo */
        $gameTwo = Game::factory()->create(['system_id' => $normalSystem->id]);

        $params['g'] = $gameTwo->id;

        $this->post('dorequest.php', $params)
            ->assertStatus(403)
            ->assertExactJson([
                "Success" => false,
                "Error" => "Access denied.",
                "Code" => "access_denied",
                "Status" => 403,
            ]);

        // Next, try to delegate on a game with no achievements authored by the integration user.
        // This is not allowed and should fail.
        /** @var Game $gameThree */
        $gameThree = Game::factory()->create(['system_id' => $standalonesSystem->id]);
        /** @var User $randomUser */
        $randomUser = User::factory()->create(['Permissions' => Permissions::Registered, 'connect_token' => Str::random(16)]);
        Achievement::factory()->promoted()->count(6)->create(['game_id' => $gameThree->id, 'user_id' => $randomUser->id]);
        $params['g'] = $gameThree->id;

        $this->post('dorequest.php', $params)
            ->assertStatus(403)
            ->assertExactJson([
                "Success" => false,
                "Error" => "Access denied.",
                "Code" => "access_denied",
                "Status" => 403,
            ]);
    }

    public function testPingWithBonusSetResolvesToCoreGame(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::now());

        $system = System::factory()->create();
        $baseGame = $this->seedGame(system: $system);
        $bonusGame = $this->seedGame(system: $system);

        Achievement::factory()->promoted()->count(2)->create(['game_id' => $baseGame->id]);
        Achievement::factory()->promoted()->count(2)->create(['game_id' => $bonusGame->id]);

        $upsertGameCoreSetAction = new UpsertGameCoreAchievementSetFromLegacyFlagsAction();
        $associateAchievementSetToGameAction = new AssociateAchievementSetToGameAction();

        $upsertGameCoreSetAction->execute($baseGame);
        $upsertGameCoreSetAction->execute($bonusGame);
        $associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');

        $bonusGameHash = GameHash::factory()->create(['game_id' => $bonusGame->id]);

        $this->user->rich_presence_game_id = $bonusGame->id;
        $this->user->save();

        // Act
        $response = $this->post('dorequest.php', $this->apiParams('ping', [
            'g' => $bonusGame->id,
            'm' => 'Playing bonus content',
            'x' => $bonusGameHash->md5,
        ]))
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        // Assert
        $response
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $playerSession = PlayerSession::latest()->first();

        $this->assertNotNull($playerSession);
        $this->assertEquals($this->user->id, $playerSession->user_id);
        $this->assertEquals($baseGame->id, $playerSession->game_id);
        $this->assertEquals($bonusGameHash->id, $playerSession->game_hash_id);
        $this->assertEquals('Playing bonus content', $playerSession->rich_presence);
        $this->assertEquals(1, $playerSession->duration);

        $this->assertEquals($baseGame->id, $this->user->fresh()->rich_presence_game_id);
        $this->assertEquals('Playing bonus content', $this->user->fresh()->rich_presence);
    }

    public function testPingWithSpecialtySetMaintainsSubsetGame(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::now());

        $system = System::factory()->create();
        $baseGame = $this->seedGame(system: $system);
        $specialtyGame = $this->seedGame(system: $system);

        Achievement::factory()->promoted()->count(2)->create(['game_id' => $baseGame->id]);
        Achievement::factory()->promoted()->count(2)->create(['game_id' => $specialtyGame->id]);

        $upsertGameCoreSetAction = new UpsertGameCoreAchievementSetFromLegacyFlagsAction();
        $associateAchievementSetToGameAction = new AssociateAchievementSetToGameAction();

        $upsertGameCoreSetAction->execute($baseGame);
        $upsertGameCoreSetAction->execute($specialtyGame);
        $associateAchievementSetToGameAction->execute($baseGame, $specialtyGame, AchievementSetType::Specialty, 'Specialty');

        $specialtyGameHash = GameHash::factory()->create(['game_id' => $specialtyGame->id]);

        $this->user->rich_presence_game_id = $specialtyGame->id;
        $this->user->save();

        // Act
        $response = $this->post('dorequest.php', $this->apiParams('ping', [
            'g' => $specialtyGame->id,
            'm' => 'Playing specialty content',
            'x' => $specialtyGameHash->md5,
        ]));

        // Assert
        $response
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $playerSession = PlayerSession::latest()->first();

        $this->assertNotNull($playerSession);
        $this->assertEquals($this->user->id, $playerSession->user_id);
        $this->assertEquals($specialtyGame->id, $playerSession->game_id);
        $this->assertEquals($specialtyGameHash->id, $playerSession->game_hash_id);
        $this->assertEquals('Playing specialty content', $playerSession->rich_presence);
        $this->assertEquals(1, $playerSession->duration);

        $this->assertEquals($specialtyGame->id, $this->user->fresh()->rich_presence_game_id);
        $this->assertEquals('Playing specialty content', $this->user->fresh()->rich_presence);
    }

    public function testPingWithMultiDiscGameUsesGameIdDirectly(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::now());

        $system = System::factory()->create();
        $game = $this->seedGame(system: $system, withHash: false);
        $gameHash = GameHash::factory()->create([
            'game_id' => $game->id,
            'name' => 'Game Title (Disc 2)',
        ]);

        $this->user->rich_presence_game_id = $game->id;
        $this->user->save();

        // Act
        $response = $this->post('dorequest.php', $this->apiParams('ping', [
            'g' => $game->id,
            'm' => 'Playing disc 2',
            'x' => $gameHash->md5,
        ]));

        // Assert
        $response
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $playerSession = PlayerSession::latest()->first();

        $this->assertNotNull($playerSession);
        $this->assertEquals($this->user->id, $playerSession->user_id);
        $this->assertEquals($game->id, $playerSession->game_id);
        $this->assertNull($playerSession->game_hash_id);
        $this->assertEquals('Playing disc 2', $playerSession->rich_presence);

        $this->assertEquals($game->id, $this->user->fresh()->rich_presence_game_id);
        $this->assertEquals('Playing disc 2', $this->user->fresh()->rich_presence);
    }
}
