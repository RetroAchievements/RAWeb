<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\AchievementSet;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Platform\Concerns\TestsAuditComments;
use Tests\TestCase;

class UploadLeaderboardTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;
    use TestsAuditComments;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
    }

    public function checksumParams(array $params): array
    {
        $newParams = $this->apiParams('uploadleaderboard', $params);
        $leaderboardId = $newParams['i'] ?? 0;

        $message = "{$newParams['u']}SECRET{$leaderboardId}SEC{$newParams['s']}{$newParams['b']}{$newParams['c']}{$newParams['l']}RE2{$newParams['f']}";
        $newParams['h'] = md5($message);

        return $newParams;
    }

    public function testUploadLeaderboardDeveloper(): void
    {
        $this->user = User::factory()->create(['connect_token' => Str::random(16), 'Permissions' => Permissions::Developer]);
        $this->user->assignRole(Role::DEVELOPER);

        $game = $this->seedGame();
        $this->addServerUser();

        // ----------------------------
        // new leaderboard for unclaimed game
        // TODO: temporarily disabled and duplicated below. when the claim check is restored,
        //       re-enable this code and remove the duplicated code.
        // $this->post('dorequest.php', $this->checksumParams([
        //     'g' => $game->ID,
        //     'n' => 'Title',
        //     'd' => 'Description',
        //     's' => '1=0',
        //     'b' => '2=0',
        //     'c' => '3=0',
        //     'l' => '4=0',
        //     'w' => 1,
        //     'f' => 'VALUE',
        // ]))
        //     ->assertStatus(403)
        //     ->assertExactJson([
        //         'Status' => 403,
        //         'Code' => 'access_denied',
        //         'Success' => false,
        //         'Error' => 'You must have an active claim on this game to perform this action.',
        //     ]);

        // ----------------------------
        // new leaderboard for valid game with claim
        AchievementSetClaim::factory()->create([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
        ]);

        $this->post('dorequest.php', $this->checksumParams([
            'g' => $game->ID,
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1 = Leaderboard::find(1);
        $this->assertEquals('Title', $leaderboard1->Title);
        $this->assertEquals('Description', $leaderboard1->Description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->Mem);
        $this->assertEquals(true, $leaderboard1->LowerIsBetter);
        $this->assertEquals('VALUE', $leaderboard1->Format);
        $this->assertEquals(1, $leaderboard1->DisplayOrder);

        // ----------------------------
        // second new leaderboard for valid game
        $this->post('dorequest.php', $this->checksumParams([
            'g' => $game->ID,
            'n' => 'Title2',
            'd' => 'Description2',
            's' => '5=0',
            'b' => '6=0',
            'c' => '7=0',
            'l' => '8=0',
            'w' => 0,
            'f' => 'SCORE',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 2,
            ]);

        $leaderboard2 = Leaderboard::find(2);
        $this->assertEquals('Title2', $leaderboard2->Title);
        $this->assertEquals('Description2', $leaderboard2->Description);
        $this->assertEquals('STA:5=0::CAN:7=0::SUB:6=0::VAL:8=0', $leaderboard2->Mem);
        $this->assertEquals(false, $leaderboard2->LowerIsBetter);
        $this->assertEquals('SCORE', $leaderboard2->Format);
        $this->assertEquals(2, $leaderboard2->DisplayOrder);

        // ----------------------------
        // update first leaderboard title
        $this->post('dorequest.php', $this->checksumParams([
            'i' => $leaderboard1->ID,
            'g' => $game->id,
            'n' => 'Title3',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => $leaderboard1->id,
            ]);

        $leaderboard1->refresh();
        $this->assertEquals('Title3', $leaderboard1->Title);
        $this->assertEquals('Description', $leaderboard1->Description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->Mem);
        $this->assertEquals(true, $leaderboard1->LowerIsBetter);
        $this->assertEquals('VALUE', $leaderboard1->Format);
        $this->assertEquals(1, $leaderboard1->DisplayOrder);
        $this->assertAuditComment(ArticleType::Leaderboard, $leaderboard1->id,
            "{$this->user->display_name} edited this leaderboard's title.");

        // ----------------------------
        // update second leaderboard everything
        $this->post('dorequest.php', $this->checksumParams([
            'i' => $leaderboard2->ID,
            'g' => $game->id,
            'n' => 'Title4',
            'd' => 'Description4',
            's' => '11=0',
            'b' => '12=0',
            'c' => '13=0',
            'l' => '14=0',
            'w' => 1,
            'f' => 'TIMESECS',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => $leaderboard2->id,
            ]);

        $leaderboard2->refresh();
        $this->assertEquals('Title4', $leaderboard2->Title);
        $this->assertEquals('Description4', $leaderboard2->Description);
        $this->assertEquals('STA:11=0::CAN:13=0::SUB:12=0::VAL:14=0', $leaderboard2->Mem);
        $this->assertEquals(true, $leaderboard2->LowerIsBetter);
        $this->assertEquals('TIMESECS', $leaderboard2->Format);
        $this->assertEquals(2, $leaderboard2->DisplayOrder);
        $this->assertAuditComment(ArticleType::Leaderboard, $leaderboard2->id,
            "{$this->user->display_name} edited this leaderboard's title, description, format, order, logic.");

        // ----------------------------
        // update non-existant leaderboard
        $this->post('dorequest.php', $this->checksumParams([
            'i' => 9999,
            'g' => $game->id,
            'n' => 'Title4',
            'd' => 'Description4',
            's' => '11=0',
            'b' => '12=0',
            'c' => '13=0',
            'l' => '14=0',
            'w' => 1,
            'f' => 'TIMESECS',
        ]))
            ->assertStatus(404)
            ->assertExactJson([
                'Status' => 404,
                'Code' => 'not_found',
                'Success' => false,
                'Error' => 'Unknown leaderboard.',
            ]);

        // ----------------------------
        // update second leaderboard unknown format
        $this->post('dorequest.php', $this->checksumParams([
            'i' => $leaderboard2->ID,
            'g' => $game->id,
            'n' => 'Title4',
            'd' => 'Description4',
            's' => '11=0',
            'b' => '12=0',
            'c' => '13=0',
            'l' => '14=0',
            'w' => 1,
            'f' => 'BANANA',
        ]))
            ->assertStatus(422)
            ->assertExactJson([
                'Status' => 422,
                'Code' => 'invalid_parameter',
                'Success' => false,
                'Error' => 'Unknown format: BANANA',
            ]);

        $leaderboard2->refresh();
        $this->assertEquals('TIMESECS', $leaderboard2->Format);

        // ----------------------------
        // create new leaderboard unknown format
        $this->post('dorequest.php', $this->checksumParams([
            'g' => $game->id,
            'n' => 'Title4',
            'd' => 'Description4',
            's' => '11=0',
            'b' => '12=0',
            'c' => '13=0',
            'l' => '14=0',
            'w' => 1,
            'f' => 'BANANA',
        ]))
            ->assertStatus(422)
            ->assertExactJson([
                'Status' => 422,
                'Code' => 'invalid_parameter',
                'Success' => false,
                'Error' => 'Unknown format: BANANA',
            ]);

        $this->assertEquals(2, Leaderboard::count());

        // ----------------------------
        // third new leaderboard for valid achievement set
        $achievementSet = GameAchievementSet::create(['game_id' => $game->id, 'achievement_set_id' => AchievementSet::create()->id]);
        $this->post('dorequest.php', $this->checksumParams([
            'p' => $achievementSet->id,
            'n' => 'Title5',
            'd' => 'Description5',
            's' => '15=0',
            'b' => '16=0',
            'c' => '17=0',
            'l' => '18=0',
            'w' => 1,
            'f' => 'UNSIGNED',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 3,
            ]);

        $leaderboard3 = Leaderboard::find(3);
        $this->assertEquals('Title5', $leaderboard3->Title);
        $this->assertEquals('Description5', $leaderboard3->Description);
        $this->assertEquals('STA:15=0::CAN:17=0::SUB:16=0::VAL:18=0', $leaderboard3->Mem);
        $this->assertEquals(true, $leaderboard3->LowerIsBetter);
        $this->assertEquals('UNSIGNED', $leaderboard3->Format);
        $this->assertEquals($game->id, $leaderboard3->GameID);
        $this->assertEquals(3, $leaderboard3->DisplayOrder);

        // ----------------------------
        // create new leaderboard invalid checksum
        $params = $this->checksumParams([
            'g' => $game->id,
            'n' => 'Title5',
            'd' => 'Description5',
            's' => '11=0',
            'b' => '12=0',
            'c' => '13=0',
            'l' => '14=0',
            'w' => 1,
            'f' => 'SCORE',
        ]);
        unset($params['h']); // no checksum
        $this->post('dorequest.php', $params)
            ->assertStatus(422)
            ->assertExactJson([
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Success' => false,
                'Error' => 'One or more required parameters is missing.',
            ]);
        $this->assertEquals(3, Leaderboard::count());

        $params['h'] = 'INVALID'; // invalid checksum
        $this->post('dorequest.php', $params)
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'Invalid checksum.',
            ]);

        $this->assertEquals(3, Leaderboard::count());

        $params['i'] = $leaderboard2->ID; // also try updating
        $this->post('dorequest.php', $params)
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'Invalid checksum.',
            ]);

        $this->assertEquals(3, Leaderboard::count());
        $leaderboard2->refresh();
        $this->assertEquals('Title4', $leaderboard2->Title);

        // TODO: this is a duplicate of the first commented out subtest above. it was
        //       commented out to avoid updating all of the intermediate IDs given that
        //       the intent is to re-enable the claim requirement for new leaderboards.
        //       when that happens, this should be removed and the above subtest uncommented.

        // ----------------------------
        // new leaderboard for unclaimed game
        $game2 = $this->seedGame();
        $this->post('dorequest.php', $this->checksumParams([
            'g' => $game2->ID,
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 4,
            ]);

        $leaderboard4 = Leaderboard::find(4);
        $this->assertEquals('Title', $leaderboard4->Title);
        $this->assertEquals('Description', $leaderboard4->Description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard4->Mem);
        $this->assertEquals(true, $leaderboard4->LowerIsBetter);
        $this->assertEquals('VALUE', $leaderboard4->Format);
        $this->assertEquals(1, $leaderboard4->DisplayOrder);
   }

    public function testUploadLeaderboardJuniorDeveloper(): void
    {
        $this->user = User::factory()->create(['connect_token' => Str::random(16), 'Permissions' => Permissions::JuniorDeveloper]);
        $this->user->assignRole(Role::DEVELOPER_JUNIOR);

        $game = $this->seedGame();
        $this->addServerUser();

        // ----------------------------
        // new leaderboard for unclaimed game
        // NOTE: LeaderboardPolicy::create validates claim, so junior only gets a generic access denied message
        $this->post('dorequest.php', $this->checksumParams([
            'g' => $game->ID,
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'Access denied.',
            ]);

        // ----------------------------
        // new leaderboard for valid game with claim
        AchievementSetClaim::factory()->create([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
        ]);

        $this->post('dorequest.php', $this->checksumParams([
            'g' => $game->ID,
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => 1,
            ]);

        $leaderboard1 = Leaderboard::find(1);
        $this->assertEquals('Title', $leaderboard1->Title);
        $this->assertEquals('Description', $leaderboard1->Description);
        $this->assertEquals('STA:1=0::CAN:3=0::SUB:2=0::VAL:4=0', $leaderboard1->Mem);
        $this->assertEquals(true, $leaderboard1->LowerIsBetter);
        $this->assertEquals('VALUE', $leaderboard1->Format);

        // ----------------------------
        // update leaderboard
        $this->post('dorequest.php', $this->checksumParams([
            'i' => $leaderboard1->id,
            'g' => $game->ID,
            'n' => 'Title2',
            'd' => 'Description2',
            's' => '11=0',
            'b' => '12=0',
            'c' => '13=0',
            'l' => '14=0',
            'w' => 0,
            'f' => 'SCORE',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'LeaderboardID' => $leaderboard1->id,
            ]);

        $leaderboard1->refresh();
        $this->assertEquals('Title2', $leaderboard1->Title);
        $this->assertEquals('Description2', $leaderboard1->Description);
        $this->assertEquals('STA:11=0::CAN:13=0::SUB:12=0::VAL:14=0', $leaderboard1->Mem);
        $this->assertEquals(false, $leaderboard1->LowerIsBetter);
        $this->assertEquals('SCORE', $leaderboard1->Format);
        $this->assertAuditComment(ArticleType::Leaderboard, $leaderboard1->id,
            "{$this->user->display_name} edited this leaderboard's title, description, format, order, logic.");

        // ----------------------------
        // update another user's leaderboard
        $user2 = User::factory()->create();
        $leaderboard2 = Leaderboard::factory()->create(['author_id' => $user2->id]);

        $this->post('dorequest.php', $this->checksumParams([
            'i' => $leaderboard2->id,
            'g' => $game->ID,
            'n' => 'Title2',
            'd' => 'Description2',
            's' => '11=0',
            'b' => '12=0',
            'c' => '13=0',
            'l' => '14=0',
            'w' => 0,
            'f' => 'SCORE',
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'Access denied.',
            ]);

        $leaderboard2->refresh();
        $this->assertNotEquals('Title2', $leaderboard2->Title);
        $this->assertNotEquals('Description2', $leaderboard2->Description);
    }

    public function testUploadLeaderboardNonDeveloper(): void
    {
        $this->user = User::factory()->create(['connect_token' => Str::random(16), 'Permissions' => Permissions::Registered]);

        $game = $this->seedGame();
        $this->addServerUser();

        // ----------------------------
        // new leaderboard for valid game
        $this->post('dorequest.php', $this->checksumParams([
            'g' => $game->ID,
            'n' => 'Title',
            'd' => 'Description',
            's' => '1=0',
            'b' => '2=0',
            'c' => '3=0',
            'l' => '4=0',
            'w' => 1,
            'f' => 'VALUE',
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'Access denied.',
            ]);

        // ----------------------------
        // update another user's leaderboard
        $user2 = User::factory()->create();
        $leaderboard2 = Leaderboard::factory()->create(['author_id' => $user2->id]);

        $this->post('dorequest.php', $this->checksumParams([
            'i' => $leaderboard2->id,
            'g' => $game->ID,
            'n' => 'Title2',
            'd' => 'Description2',
            's' => '11=0',
            'b' => '12=0',
            'c' => '13=0',
            'l' => '14=0',
            'w' => 0,
            'f' => 'SCORE',
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Code' => 'access_denied',
                'Success' => false,
                'Error' => 'Access denied.',
            ]);

        $leaderboard2->refresh();
        $this->assertNotEquals('Title2', $leaderboard2->Title);
        $this->assertNotEquals('Description2', $leaderboard2->Description);
    }
}
