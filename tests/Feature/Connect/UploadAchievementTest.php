<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\AchievementSetClaim;
use App\Models\System;
use App\Models\Trigger;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\TriggerableType;
use App\Platform\Events\AchievementCreated;
use App\Platform\Events\AchievementPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class UploadAchievementTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testItCreatesNewAchievementAndDispatchesEvent(): void
    {
        Event::fake([
            AchievementCreated::class,
        ]);

        /** @var User $author */
        $author = User::factory()->create([
            'Permissions' => Permissions::Developer,
            'appToken' => Str::random(16),
        ]);
        $game = $this->seedGame(withHash: false);

        AchievementSetClaim::factory()->create([
            'user_id' => $author->id,
            'game_id' => $game->id,
        ]);

        $this->get($this->apiUrl('uploadachievement', [
            'u' => $author->username,
            't' => $author->appToken,
            'g' => $game->ID,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 5, // Unofficial - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => 1,
                'Error' => '',
            ]);

        Event::assertDispatched(AchievementCreated::class);
    }

    public function testItPublishesAchievementAndDispatchesEvent(): void
    {
        Event::fake([
            AchievementPublished::class,
        ]);

        /** @var User $author */
        $author = User::factory()->create([
            'Permissions' => Permissions::Developer,
            'appToken' => Str::random(16),
        ]);
        $game = $this->seedGame(withHash: false);

        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->for($game)->create(['user_id' => $author->id]);

        AchievementSetClaim::factory()->create([
            'user_id' => $author->id,
            'game_id' => $game->id,
        ]);

        $this->get($this->apiUrl('uploadachievement', [
            'a' => $achievement->id,
            'u' => $author->username,
            't' => $author->appToken,
            'g' => $game->id,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => AchievementFlag::OfficialCore->value, // Unofficial - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ]))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement->id,
                'Error' => '',
            ]);

        Event::assertDispatched(AchievementPublished::class);
    }

    public function testAchievementLifetime(): void
    {
        /** @var User $author */
        $author = User::factory()->create([
            'Permissions' => Permissions::Developer,
            'appToken' => Str::random(16),
            'ContribCount' => 0,
            'ContribYield' => 0,
        ]);
        $game = $this->seedGame(withHash: false);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->create(['user_id' => $author->id]);

        AchievementSetClaim::factory()->create([
            'user_id' => $author->id,
            'game_id' => $game->id,
        ]);

        $params = [
            'u' => $author->User,
            't' => $author->appToken,
            'g' => $game->ID,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 5, // Unofficial - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ];

        // ====================================================
        // create an achievement
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement1->ID + 1,
                'Error' => '',
            ]);

        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::findOrFail($achievement1->ID + 1);
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title1');
        $this->assertEquals($achievement2->MemAddr, '0xH0000=1');
        $this->assertEquals($achievement2->Points, 5);
        $this->assertEquals($achievement2->Flags, AchievementFlag::Unofficial->value);
        $this->assertNull($achievement2->type);
        $this->assertEquals($achievement2->user_id, $author->id);
        $this->assertEquals($achievement2->BadgeName, '001234');

        $game->refresh();
        $this->assertEquals($game->achievements_published, 0);
        $this->assertEquals($game->achievements_unpublished, 1);
        $this->assertEquals($game->points_total, 0);

        // ====================================================
        // publish achievement
        $params['a'] = $achievement2->ID;
        $params['f'] = 3; // Official - hardcode for test to prevent false success if enum changes
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'Error' => '',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title1');
        $this->assertEquals($achievement2->MemAddr, '0xH0000=1');
        $this->assertEquals($achievement2->Points, 5);
        $this->assertEquals($achievement2->Flags, AchievementFlag::OfficialCore->value);
        $this->assertNull($achievement2->type);
        $this->assertEquals($achievement2->BadgeName, '001234');

        $game->refresh();
        $this->assertEquals($game->achievements_published, 1);
        $this->assertEquals($game->achievements_unpublished, 0);
        $this->assertEquals($game->points_total, 5);

        // ====================================================
        // modify achievement
        $params['n'] = 'Title2';
        $params['d'] = 'Description2';
        $params['z'] = 10;
        $params['m'] = '0xH0001=1';
        $params['b'] = '002345';
        $params['x'] = 'progression';
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'Error' => '',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title2');
        $this->assertEquals($achievement2->MemAddr, '0xH0001=1');
        $this->assertEquals($achievement2->Points, 10);
        $this->assertEquals($achievement2->Flags, AchievementFlag::OfficialCore->value);
        $this->assertEquals($achievement2->type, 'progression');
        $this->assertEquals($achievement2->BadgeName, '002345');

        $game->refresh();
        $this->assertEquals($game->achievements_published, 1);
        $this->assertEquals($game->achievements_unpublished, 0);
        $this->assertEquals($game->points_total, 10);

        // ====================================================
        // unlock achievement; contrib yield changes
        $this->addHardcoreUnlock($author, $achievement2);
        $this->addHardcoreUnlock($this->user, $achievement2);

        $author->refresh();
        $this->assertEquals($author->ContribCount, 1);
        $this->assertEquals($author->ContribYield, 10);

        $game->refresh();
        $this->assertEquals($game->players_total, 2);
        $this->assertEquals($game->players_hardcore, 2);

        // ====================================================
        // rescore achievement; contrib yield changes
        $params['z'] = 5;
        unset($params['x']); // omitting optional 'x' parameter should not change type
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'Error' => '',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title2');
        $this->assertEquals($achievement2->MemAddr, '0xH0001=1');
        $this->assertEquals($achievement2->Points, 5);
        $this->assertEquals($achievement2->Flags, AchievementFlag::OfficialCore->value);
        $this->assertEquals($achievement2->type, 'progression');
        $this->assertEquals($achievement2->BadgeName, '002345');

        $game->refresh();
        $this->assertEquals($game->achievements_published, 1);
        $this->assertEquals($game->achievements_unpublished, 0);
        $this->assertEquals($game->points_total, 5);
        $this->assertEquals($game->players_total, 2);
        $this->assertEquals($game->players_hardcore, 2);

        $author->refresh();
        $this->assertEquals($author->ContribCount, 1);
        $this->assertEquals($author->ContribYield, 5);

        // ====================================================
        // demote achievement; contrib yield changes
        $params['f'] = 5;
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'Error' => '',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title2');
        $this->assertEquals($achievement2->MemAddr, '0xH0001=1');
        $this->assertEquals($achievement2->Points, 5);
        $this->assertEquals($achievement2->Flags, AchievementFlag::Unofficial->value);
        $this->assertEquals($achievement2->type, 'progression');
        $this->assertEquals($achievement2->BadgeName, '002345');

        $game->refresh();
        $this->assertEquals($game->achievements_published, 0);
        $this->assertEquals($game->achievements_unpublished, 1);
        $this->assertEquals($game->points_total, 0);
        $this->assertEquals($game->players_total, 0);
        $this->assertEquals($game->players_hardcore, 0);

        $author->refresh();
        $this->assertEquals($author->ContribCount, 0);
        $this->assertEquals($author->ContribYield, 0);

        // ====================================================
        // change points while demoted
        $params['z'] = 10;
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'Error' => '',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title2');
        $this->assertEquals($achievement2->MemAddr, '0xH0001=1');
        $this->assertEquals($achievement2->Points, 10);
        $this->assertEquals($achievement2->Flags, AchievementFlag::Unofficial->value);
        $this->assertEquals($achievement2->type, 'progression');
        $this->assertEquals($achievement2->BadgeName, '002345');

        $game->refresh();
        $this->assertEquals($game->achievements_published, 0);
        $this->assertEquals($game->achievements_unpublished, 1);
        $this->assertEquals($game->points_total, 0);
        $this->assertEquals($game->players_total, 0);
        $this->assertEquals($game->players_hardcore, 0);

        $author->refresh();
        $this->assertEquals($author->ContribCount, 0);
        $this->assertEquals($author->ContribYield, 0);

        // ====================================================
        // repromote achievement; contrib yield changes
        $params['f'] = 3;
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'Error' => '',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title2');
        $this->assertEquals($achievement2->MemAddr, '0xH0001=1');
        $this->assertEquals($achievement2->Points, 10);
        $this->assertEquals($achievement2->Flags, AchievementFlag::OfficialCore->value);
        $this->assertEquals($achievement2->type, 'progression');
        $this->assertEquals($achievement2->BadgeName, '002345');

        $game->refresh();
        $this->assertEquals($game->achievements_published, 1);
        $this->assertEquals($game->achievements_unpublished, 0);
        $this->assertEquals($game->points_total, 10);
        $this->assertEquals($game->players_total, 2);
        $this->assertEquals($game->players_hardcore, 2);

        $author->refresh();
        $this->assertEquals($author->ContribCount, 1);
        $this->assertEquals($author->ContribYield, 10);
    }

    public function testNonDevPermissions(): void
    {
        /** @var User $author */
        $author = User::factory()->create([
            'Permissions' => Permissions::Registered,
            'appToken' => Str::random(16),
        ]);
        $game = $this->seedGame(withHash: false);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->create(['GameID' => $game->ID, 'user_id' => $author->id]);

        $params = [
            'u' => $author->User,
            't' => $author->appToken,
            'g' => $game->ID,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 5, // Unofficial - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ];

        // ====================================================
        // non-developer cannot create achievements
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => 0,
                'Error' => "You must be a developer to perform this action! Please drop a message in the forums to apply.",
            ]);
    }

    public function testJrDevPermissions(): void
    {
        /** @var User $author */
        $author = User::factory()->create([
            'Permissions' => Permissions::JuniorDeveloper,
            'appToken' => Str::random(16),
        ]);
        $game = $this->seedGame(withHash: false);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->create(['GameID' => $game->ID, 'user_id' => $this->user->id]);

        $params = [
            'u' => $author->User,
            't' => $author->appToken,
            'g' => $game->ID,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 5, // Unofficial - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ];

        // ====================================================
        // junior developer cannot create achievement without claim
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => 0,
                'Error' => "You must have an active claim on this game to perform this action.",
            ]);

        // ====================================================
        // junior developer can create achievement with claim
        AchievementSetClaim::factory()->create([
            'user_id' => $author->id,
            'game_id' => $game->id,
        ]);

        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement1->ID + 1,
                'Error' => '',
            ]);

        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::findOrFail($achievement1->ID + 1);
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title1');
        $this->assertEquals($achievement2->MemAddr, '0xH0000=1');
        $this->assertEquals($achievement2->Points, 5);
        $this->assertEquals($achievement2->Flags, AchievementFlag::Unofficial->value);
        $this->assertNull($achievement2->type);
        $this->assertEquals($achievement2->BadgeName, '001234');

        // ====================================================
        // junior developer can modify their own achievement
        $params['a'] = $achievement2->ID;
        $params['n'] = 'Title2';
        $params['d'] = 'Description2';
        $params['z'] = 10;
        $params['m'] = '0xH0001=1';
        $params['b'] = '002345';
        $params['x'] = 'progression';
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement1->ID + 1,
                'Error' => '',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title2');
        $this->assertEquals($achievement2->MemAddr, '0xH0001=1');
        $this->assertEquals($achievement2->Points, 10);
        $this->assertEquals($achievement2->Flags, AchievementFlag::Unofficial->value);
        $this->assertEquals($achievement2->type, 'progression');
        $this->assertEquals($achievement2->BadgeName, '002345');

        // ====================================================
        // junior developer cannot modify an achievement owned by someone else
        $params['a'] = $achievement1->ID;
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement1->ID,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $achievement1->refresh();
        $this->assertNotEquals($achievement1->Title, 'Title2');
        $this->assertNotEquals($achievement1->MemAddr, '0xH0001=1');
        $this->assertNotEquals($achievement1->Points, 10);
        $this->assertEquals($achievement1->Flags, AchievementFlag::Unofficial->value);
        $this->assertNotEquals($achievement1->type, 'progression');
        $this->assertNotEquals($achievement1->BadgeName, '002345');

        // ====================================================
        // junior developer cannot promote their own achievement
        $params['a'] = $achievement2->ID;
        $params['f'] = 3;
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement2->ID,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title2');
        $this->assertEquals($achievement2->MemAddr, '0xH0001=1');
        $this->assertEquals($achievement2->Points, 10);
        $this->assertEquals($achievement2->Flags, AchievementFlag::Unofficial->value);
        $this->assertEquals($achievement2->type, 'progression');
        $this->assertEquals($achievement2->BadgeName, '002345');

        // ====================================================
        // junior developer cannot demote their own achievement
        $achievement2->Flags = AchievementFlag::OfficialCore->value;
        $achievement2->save();
        $params['f'] = 5;
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement2->ID,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title2');
        $this->assertEquals($achievement2->MemAddr, '0xH0001=1');
        $this->assertEquals($achievement2->Points, 10);
        $this->assertEquals($achievement2->Flags, AchievementFlag::OfficialCore->value);
        $this->assertEquals($achievement2->type, 'progression');
        $this->assertEquals($achievement2->BadgeName, '002345');

        // ====================================================
        // junior developer cannot change logic of their own achievement in core
        $params['f'] = 3;
        $params['m'] = '0xH0002=1';
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement2->ID,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title2');
        $this->assertEquals($achievement2->MemAddr, '0xH0001=1');
        $this->assertEquals($achievement2->Points, 10);
        $this->assertEquals($achievement2->Flags, AchievementFlag::OfficialCore->value);
        $this->assertEquals($achievement2->type, 'progression');
        $this->assertEquals($achievement2->BadgeName, '002345');

        // ====================================================
        // junior developer can change all non-logic of their own achievement in core
        $params['n'] = 'Title3';
        $params['d'] = 'Description3';
        $params['z'] = 5;
        $params['m'] = '0xH0001=1';
        $params['b'] = '003456';
        $params['x'] = '';
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'Error' => '',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title3');
        $this->assertEquals($achievement2->MemAddr, '0xH0001=1');
        $this->assertEquals($achievement2->Points, 5);
        $this->assertEquals($achievement2->Flags, AchievementFlag::OfficialCore->value);
        $this->assertNull($achievement2->type);
        $this->assertEquals($achievement2->BadgeName, '003456');
    }

    public function testDevPermissions(): void
    {
        /** @var User $author */
        $author = User::factory()->create([
            'Permissions' => Permissions::Developer,
            'appToken' => Str::random(16),
        ]);
        $game = $this->seedGame(withHash: false);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->create(['GameID' => $game->ID, 'user_id' => $this->user->id]);

        $params = [
            'u' => $author->User,
            't' => $author->appToken,
            'g' => $game->ID,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 5, // Unofficial - hardcode for test to prevent false success if enum changes
            'b' => '001234',
        ];

        // ====================================================
        // developer cannot create achievement without claim
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => 0,
                'Error' => "You must have an active claim on this game to perform this action.",
            ]);

        // ====================================================
        // developer can create achievement with claim
        AchievementSetClaim::factory()->create([
            'user_id' => $author->id,
            'game_id' => $game->id,
        ]);

        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement1->ID + 1,
                'Error' => '',
            ]);

        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::findOrFail($achievement1->ID + 1);
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title1');
        $this->assertEquals($achievement2->MemAddr, '0xH0000=1');
        $this->assertEquals($achievement2->Points, 5);
        $this->assertEquals($achievement2->Flags, AchievementFlag::Unofficial->value);
        $this->assertNull($achievement2->type);
        $this->assertEquals($achievement2->BadgeName, '001234');

        // ====================================================
        // developer can modify their own achievement
        $params['a'] = $achievement2->ID;
        $params['n'] = 'Title2';
        $params['d'] = 'Description2';
        $params['z'] = 10;
        $params['m'] = '0xH0001=1';
        $params['b'] = '002345';
        $params['x'] = 'progression';
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement1->ID + 1,
                'Error' => '',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title2');
        $this->assertEquals($achievement2->MemAddr, '0xH0001=1');
        $this->assertEquals($achievement2->Points, 10);
        $this->assertEquals($achievement2->Flags, AchievementFlag::Unofficial->value);
        $this->assertEquals($achievement2->type, 'progression');
        $this->assertEquals($achievement2->BadgeName, '002345');

        // ====================================================
        // developer can promote their own achievement
        $params['f'] = 3;
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'Error' => '',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title2');
        $this->assertEquals($achievement2->MemAddr, '0xH0001=1');
        $this->assertEquals($achievement2->Points, 10);
        $this->assertEquals($achievement2->Flags, AchievementFlag::OfficialCore->value);
        $this->assertEquals($achievement2->type, 'progression');
        $this->assertEquals($achievement2->BadgeName, '002345');

        // ====================================================
        // developer can change all properties of their own achievement in core
        $params['n'] = 'Title3';
        $params['d'] = 'Description3';
        $params['z'] = 5;
        $params['m'] = '0xH0002=1';
        $params['b'] = '003456';
        $params['x'] = '';
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'Error' => '',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title3');
        $this->assertEquals($achievement2->MemAddr, '0xH0002=1');
        $this->assertEquals($achievement2->Points, 5);
        $this->assertEquals($achievement2->Flags, AchievementFlag::OfficialCore->value);
        $this->assertNull($achievement2->type);
        $this->assertEquals($achievement2->BadgeName, '003456');

        // ====================================================
        // developer can demote their own achievement
        $params['f'] = 5;
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'Error' => '',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title3');
        $this->assertEquals($achievement2->MemAddr, '0xH0002=1');
        $this->assertEquals($achievement2->Points, 5);
        $this->assertEquals($achievement2->Flags, AchievementFlag::Unofficial->value);
        $this->assertNull($achievement2->type);
        $this->assertEquals($achievement2->BadgeName, '003456');

        // ====================================================
        // developer can modify an achievement owned by someone else
        $params['a'] = $achievement1->ID;
        $params['n'] = 'Title2';
        $params['d'] = 'Description2';
        $params['z'] = 10;
        $params['m'] = '0xH0001=1';
        $params['b'] = '002345';
        $params['x'] = 'progression';
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement1->ID,
                'Error' => '',
            ]);

        $achievement1->refresh();
        $this->assertEquals($achievement1->Title, 'Title2');
        $this->assertEquals($achievement1->MemAddr, '0xH0001=1');
        $this->assertEquals($achievement1->Points, 10);
        $this->assertEquals($achievement1->Flags, AchievementFlag::Unofficial->value);
        $this->assertEquals($achievement1->type, 'progression');
        $this->assertEquals($achievement1->BadgeName, '002345');

        // ====================================================
        // developer can promote someone else's achievement
        $params['f'] = 3;
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement1->ID,
                'Error' => '',
            ]);

        $achievement1->refresh();
        $this->assertEquals($achievement1->GameID, $game->ID);
        $this->assertEquals($achievement1->Title, 'Title2');
        $this->assertEquals($achievement1->MemAddr, '0xH0001=1');
        $this->assertEquals($achievement1->Points, 10);
        $this->assertEquals($achievement1->Flags, AchievementFlag::OfficialCore->value);
        $this->assertEquals($achievement1->type, 'progression');
        $this->assertEquals($achievement1->BadgeName, '002345');

        // ====================================================
        // developer can change all properties of someone else's achievement in core
        $params['n'] = 'Title3';
        $params['d'] = 'Description3';
        $params['z'] = 5;
        $params['m'] = '0xH0002=1';
        $params['b'] = '003456';
        $params['x'] = '';
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement1->ID,
                'Error' => '',
            ]);

        $achievement1->refresh();
        $this->assertEquals($achievement1->GameID, $game->ID);
        $this->assertEquals($achievement1->Title, 'Title3');
        $this->assertEquals($achievement1->MemAddr, '0xH0002=1');
        $this->assertEquals($achievement1->Points, 5);
        $this->assertEquals($achievement1->Flags, AchievementFlag::OfficialCore->value);
        $this->assertNull($achievement1->type);
        $this->assertEquals($achievement1->BadgeName, '003456');

        // ====================================================
        // developer can demote someone else's achievement
        $params['f'] = 5;
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement1->ID,
                'Error' => '',
            ]);

        $achievement1->refresh();
        $this->assertEquals($achievement1->GameID, $game->ID);
        $this->assertEquals($achievement1->Title, 'Title3');
        $this->assertEquals($achievement1->MemAddr, '0xH0002=1');
        $this->assertEquals($achievement1->Points, 5);
        $this->assertEquals($achievement1->Flags, AchievementFlag::Unofficial->value);
        $this->assertNull($achievement1->type);
        $this->assertEquals($achievement1->BadgeName, '003456');
    }

    public function testSubset(): void
    {
        /** @var User $author */
        $author = User::factory()->create([
            'Permissions' => Permissions::Developer,
            'appToken' => Str::random(16),
        ]);
        $game = $this->seedGame(withHash: false);
        $game->Title .= " [Subset - Testing]";
        $game->save();

        AchievementSetClaim::factory()->create([
            'user_id' => $author->id,
            'game_id' => $game->id,
        ]);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->create(['GameID' => $game->ID + 1, 'user_id' => $author->id]);

        $params = [
            'u' => $author->User,
            't' => $author->appToken,
            'g' => $game->ID,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 5,
            'b' => '001234',
        ];

        // ====================================================
        // create an achievement
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement1->ID + 1,
                'Error' => '',
            ]);

        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::findOrFail($achievement1->ID + 1);
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title1');
        $this->assertEquals($achievement2->MemAddr, '0xH0000=1');
        $this->assertEquals($achievement2->Points, 5);
        $this->assertEquals($achievement2->Flags, AchievementFlag::Unofficial->value);
        $this->assertNull($achievement2->type);
        $this->assertEquals($achievement2->user_id, $author->id);
        $this->assertEquals($achievement2->BadgeName, '001234');

        $game->refresh();
        $this->assertEquals($game->achievements_published, 0);
        $this->assertEquals($game->achievements_unpublished, 1);
        $this->assertEquals($game->points_total, 0);

        // ====================================================
        // publish achievement
        $params['a'] = $achievement2->ID;
        $params['f'] = 3; // Official - hardcode for test to prevent false success if enum changes
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'Error' => '',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title1');
        $this->assertEquals($achievement2->MemAddr, '0xH0000=1');
        $this->assertEquals($achievement2->Points, 5);
        $this->assertEquals($achievement2->Flags, AchievementFlag::OfficialCore->value);
        $this->assertNull($achievement2->type);
        $this->assertEquals($achievement2->BadgeName, '001234');

        $game->refresh();
        $this->assertEquals($game->achievements_published, 1);
        $this->assertEquals($game->achievements_unpublished, 0);
        $this->assertEquals($game->points_total, 5);

        // ====================================================
        // cannot set progression or win condition type on achievement in subset
        $params['n'] = 'Title2';
        $params['d'] = 'Description2';
        $params['z'] = 10;
        $params['m'] = '0xH0001=1';
        $params['b'] = '002345';
        $params['x'] = 'progression';
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement2->ID,
                'Error' => 'Cannot set progression or win condition type on achievement in subset, test kit, or event.',
            ]);

        // ====================================================
        // can otherwise modify achievement
        $params['x'] = 'not-given';
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'Error' => '',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title2');
        $this->assertEquals($achievement2->MemAddr, '0xH0001=1');
        $this->assertEquals($achievement2->Points, 10);
        $this->assertEquals($achievement2->Flags, AchievementFlag::OfficialCore->value);
        $this->assertEquals($achievement2->type, null);
        $this->assertEquals($achievement2->BadgeName, '002345');

        $game->refresh();
        $this->assertEquals($game->achievements_published, 1);
        $this->assertEquals($game->achievements_unpublished, 0);
        $this->assertEquals($game->points_total, 10);
    }

    public function testRolloutConsole(): void
    {
        /** @var User $author */
        $author = User::factory()->create([
            'Permissions' => Permissions::Developer,
            'appToken' => Str::random(16),
        ]);
        /** @var System $system */
        $system = System::factory()->create(['ID' => 500, 'active' => false]);
        $game = $this->seedGame(system: $system, withHash: false);

        AchievementSetClaim::factory()->create([
            'user_id' => $author->id,
            'game_id' => $game->id,
        ]);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->create(['GameID' => $game->ID + 1, 'user_id' => $author->id]);

        $params = [
            'u' => $author->User,
            't' => $author->appToken,
            'g' => $game->ID,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 5,
            'b' => '001234',
        ];

        // ====================================================
        // can upload to unofficial
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement1->ID + 1,
                'Error' => '',
            ]);

        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::findOrFail($achievement1->ID + 1);
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title1');
        $this->assertEquals($achievement2->MemAddr, '0xH0000=1');
        $this->assertEquals($achievement2->Points, 5);
        $this->assertEquals($achievement2->Flags, AchievementFlag::Unofficial->value);
        $this->assertNull($achievement2->type);
        $this->assertEquals($achievement2->user_id, $author->id);
        $this->assertEquals($achievement2->BadgeName, '001234');

        $game->refresh();
        $this->assertEquals($game->achievements_published, 0);
        $this->assertEquals($game->achievements_unpublished, 1);
        $this->assertEquals($game->points_total, 0);

        // ====================================================
        // can modify in unofficial
        $params['a'] = $achievement2->ID;
        $params['n'] = 'Title2';
        $params['d'] = 'Description2';
        $params['z'] = 10;
        $params['m'] = '0xH0001=1';
        $params['b'] = '002345';
        $params['x'] = 'progression';
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => true,
                'AchievementID' => $achievement2->ID,
                'Error' => '',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title2');
        $this->assertEquals($achievement2->MemAddr, '0xH0001=1');
        $this->assertEquals($achievement2->Points, 10);
        $this->assertEquals($achievement2->Flags, AchievementFlag::Unofficial->value);
        $this->assertEquals($achievement2->type, 'progression');
        $this->assertEquals($achievement2->BadgeName, '002345');

        $game->refresh();
        $this->assertEquals($game->achievements_published, 0);
        $this->assertEquals($game->achievements_unpublished, 1);
        $this->assertEquals($game->points_total, 0);

        // ====================================================
        // cannot promote for rollout console
        $params['f'] = 3;
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => $achievement2->ID,
                'Error' => 'You cannot promote achievements for a game from an unsupported console (console ID: 500).',
            ]);

        $achievement2->refresh();
        $this->assertEquals($achievement2->GameID, $game->ID);
        $this->assertEquals($achievement2->Title, 'Title2');
        $this->assertEquals($achievement2->MemAddr, '0xH0001=1');
        $this->assertEquals($achievement2->Points, 10);
        $this->assertEquals($achievement2->Flags, AchievementFlag::Unofficial->value);
        $this->assertEquals($achievement2->type, 'progression');
        $this->assertEquals($achievement2->BadgeName, '002345');
    }

    public function testOtherErrors(): void
    {
        /** @var User $author */
        $author = User::factory()->create([
            'Permissions' => Permissions::Developer,
            'appToken' => Str::random(16),
        ]);
        $game = $this->seedGame(withHash: false);

        AchievementSetClaim::factory()->create([
            'user_id' => $author->id,
            'game_id' => $game->id,
        ]);

        $params = [
            'u' => $author->User,
            't' => $author->appToken,
            'g' => $game->ID,
            'n' => 'Title1',
            'd' => 'Description1',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => 5,
            'b' => '001234',
        ];

        // ====================================================
        // invalid flag
        $params['f'] = 4;
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => 0,
                'Error' => 'Invalid achievement flag',
            ]);

        // ====================================================
        // invalid points
        $params['f'] = 5;
        $params['z'] = 15;
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => 0,
                'Error' => 'Invalid points value (15).',
            ]);

        // ====================================================
        // invalid type
        $params['z'] = 10;
        $params['x'] = 'unknown';
        $this->get($this->apiUrl('uploadachievement', $params))
            ->assertExactJson([
                'Success' => false,
                'AchievementID' => 0,
                'Error' => 'Invalid achievement type',
            ]);
    }

    public function testWhenCreatingNewAchievementItsTriggerIsUnversioned(): void
    {
        // Arrange
        $author = User::factory()->create([
            'Permissions' => Permissions::Developer,
            'appToken' => Str::random(16),
        ]);
        $game = $this->seedGame(withHash: false);

        AchievementSetClaim::factory()->create([
            'user_id' => $author->id,
            'game_id' => $game->id,
        ]);

        // Act
        $response = $this->get($this->apiUrl('uploadachievement', [
            'u' => $author->username,
            't' => $author->appToken,
            'g' => $game->id,
            'n' => 'Test Achievement',
            'd' => 'Test Description',
            'z' => 5,
            'm' => '0xH0000=1',
            'f' => AchievementFlag::Unofficial->value,
            'b' => 'test-badge',
        ]));

        // Assert
        $this->assertArrayHasKey('Success', $response);
        $this->assertTrue($response['Success']);
        $this->assertArrayHasKey('AchievementID', $response);

        $achievement = Achievement::find($response['AchievementID']);
        $this->assertNotNull($achievement);

        $trigger = $achievement->trigger;
        $this->assertNotNull($trigger);
        $this->assertEquals('0xH0000=1', $trigger->conditions);
        $this->assertNull($trigger->version); // !! trigger should be unversioned
    }

    public function testWhenEditingUnversionedAchievementTriggerIsUpdatedInPlace(): void
    {
        // Arrange
        $author = User::factory()->create([
            'Permissions' => Permissions::Developer,
            'appToken' => Str::random(16),
        ]);
        $game = $this->seedGame(withHash: false);

        $achievement = Achievement::factory()->create([
            'GameID' => $game->id,
            'user_id' => $author->id,
            'Flags' => AchievementFlag::Unofficial->value,
            'MemAddr' => '0xH0000=1',
        ]);
        $trigger = Trigger::factory()->create([
            'triggerable_id' => $achievement->id,
            'triggerable_type' => TriggerableType::Achievement,
            'conditions' => '0xH0000=1',
            'version' => null, // !!
            'user_id' => $author->id,
        ]);

        AchievementSetClaim::factory()->create([
            'user_id' => $author->id,
            'game_id' => $game->id,
        ]);

        // Act
        $response = $this->get($this->apiUrl('uploadachievement', [
            'u' => $author->username,
            't' => $author->appToken,
            'g' => $game->id,
            'n' => 'Test Achievement',
            'd' => 'Test Description',
            'z' => 5,
            'm' => '0xHaaaa=1', // !! the dev is updating the achievement's logic
            'f' => AchievementFlag::Unofficial->value, // !! still unofficial, though.
            'b' => 'test-badge',
            'a' => $achievement->id,
        ]));

        // Assert
        $this->assertArrayHasKey('Success', $response);
        $this->assertTrue($response['Success']);

        $achievement->refresh();
        $this->assertEquals('0xHaaaa=1', $achievement->MemAddr);

        $newTrigger = $achievement->trigger;
        $this->assertNotNull($newTrigger);
        $this->assertEquals($trigger->id, $newTrigger->id); // !! same trigger ID - it was updated in place.
        $this->assertEquals('0xHaaaa=1', $newTrigger->conditions);
        $this->assertNull($newTrigger->version); // the trigger is also still unversioned.
    }

    public function testWhenPromotingAchievementTheTriggerBecomesVersioned(): void
    {
        // Arrange
        $author = User::factory()->create([
            'Permissions' => Permissions::Developer,
            'appToken' => Str::random(16),
        ]);
        $game = $this->seedGame(withHash: false);

        $achievement = Achievement::factory()->create([
            'GameID' => $game->id,
            'user_id' => $author->id,
            'Flags' => AchievementFlag::Unofficial->value, // !! currently sitting in unofficial.
            'MemAddr' => '0xH0000=1',
        ]);
        $trigger = Trigger::factory()->create([
            'triggerable_id' => $achievement->id,
            'triggerable_type' => TriggerableType::Achievement,
            'conditions' => '0xH0000=1',
            'version' => null, // !! currently unversioned, never been published.
            'user_id' => $author->id,
        ]);

        AchievementSetClaim::factory()->create([
            'user_id' => $author->id,
            'game_id' => $game->id,
        ]);

        // Act
        $response = $this->get($this->apiUrl('uploadachievement', [
            'u' => $author->username,
            't' => $author->appToken,
            'g' => $game->id,
            'n' => 'Test Achievement',
            'd' => 'Test Description',
            'z' => 5,
            'm' => '0xH0000=1', // the logic didn't change!
            'f' => AchievementFlag::OfficialCore->value, // promoted to core!
            'b' => 'test-badge',
            'a' => $achievement->id,
        ]));

        // Assert
        $this->assertArrayHasKey('Success', $response);
        $this->assertTrue($response['Success']);

        $achievement->refresh();
        $trigger = $achievement->trigger;
        $this->assertNotNull($trigger);
        $this->assertEquals('0xH0000=1', $trigger->conditions);
        $this->assertEquals(1, $trigger->version); // the trigger now is at version 1
    }

    public function testWhenEditingVersionedAchievementNewTriggerVersionIsCreated(): void
    {
        // Arrange
        $author = User::factory()->create([
            'Permissions' => Permissions::Developer,
            'appToken' => Str::random(16),
        ]);
        $game = $this->seedGame(withHash: false);

        $achievement = Achievement::factory()->create([
            'GameID' => $game->id,
            'user_id' => $author->id,
            'Flags' => AchievementFlag::OfficialCore->value, // !!
            'MemAddr' => '0xH0000=1',
        ]);
        $trigger = Trigger::factory()->create([
            'triggerable_id' => $achievement->id,
            'triggerable_type' => TriggerableType::Achievement,
            'conditions' => '0xH0000=1',
            'version' => 1, // !!
            'user_id' => $author->id,
        ]);

        // Act
        $response = $this->get($this->apiUrl('uploadachievement', [
            'u' => $author->username,
            't' => $author->appToken,
            'g' => $game->id,
            'n' => 'Test Achievement',
            'd' => 'Test Description',
            'z' => 5,
            'm' => '0xHaaaa=1', // logic changed!
            'f' => AchievementFlag::OfficialCore->value,
            'b' => 'test-badge',
            'a' => $achievement->id,
        ]));

        // Assert
        $this->assertArrayHasKey('Success', $response);
        $this->assertTrue($response['Success']);

        $achievement->refresh();
        $newTrigger = $achievement->trigger;
        $this->assertNotNull($newTrigger);
        $this->assertNotEquals($trigger->id, $newTrigger->id); // new version was created, so new ID
        $this->assertEquals('0xHaaaa=1', $newTrigger->conditions);
        $this->assertEquals(2, $newTrigger->version); // previous version was 1, so we're on 2 now.
        $this->assertEquals($trigger->id, $newTrigger->parent_id); // we also have a stable link to the previous version.
    }
}
