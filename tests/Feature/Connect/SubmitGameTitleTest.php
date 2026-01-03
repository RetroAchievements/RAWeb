<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Community\Enums\CommentableType;
use App\Enums\GameHashCompatibility;
use App\Enums\Permissions;
use App\Models\Game;
use App\Models\Role;
use App\Models\System;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\AchievementSetType;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Platform\Concerns\TestsAuditComments;
use Tests\TestCase;

class SubmitGameTitleTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;
    use TestsAuditComments;

    public function testSubmitNewGameTitle(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var System $system2 */
        $system2 = System::factory()->create();
        /** @var Game $game1 */
        $game1 = Game::factory()->create(['system_id' => $system2->id]);

        $this->seed(RolesTableSeeder::class);
        $this->addServerUser();

        $md5 = fake()->md5;
        $title = ucwords(fake()->words(2, true));

        /* regular user */
        $this->user->setAttribute('Permissions', Permissions::Registered);
        $this->user->save();

        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'i' => $title,
            'c' => $system->id,
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Code' => 'access_denied',
                'Error' => 'You must be a developer to perform this action.',
                'Status' => 403,
                'Success' => false,
            ]);

        $this->user->assignRole(Role::DEVELOPER_JUNIOR);
        $this->user->save();

        /* junior developer */
        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'i' => $title,
            'c' => $system->id,
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Code' => 'access_denied',
                'Error' => 'You must be a developer to perform this action.',
                'Status' => 403,
                'Success' => false,
            ]);

        /* new game */
        $this->user->removeRole(Role::DEVELOPER_JUNIOR);
        $this->user->assignRole(Role::DEVELOPER);
        $this->user->save();

        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'i' => $title,
            'c' => $system->id,
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game1->id + 1,
                'Response' => [
                    'GameID' => $game1->id + 1,
                ],
            ]);

        $newGame = Game::find($game1->id + 1);
        $this->assertEquals($title, $newGame->title);
        $this->assertEquals(1, $newGame->hashes->count());
        $this->assertEquals($md5, $newGame->hashes->first()->md5);
        $this->assertEquals(GameHashCompatibility::Compatible, $newGame->hashes->first()->compatibility);
        $this->assertEquals(1, $newGame->achievementSets()->count()); // ensure core achievement set created
        $this->assertEquals(1, $newGame->gameAchievementSets()->count());
        $this->assertEquals(AchievementSetType::Core, $newGame->gameAchievementSets()->first()->type);
        $this->assertEquals(1, $newGame->releases()->count()); // ensure release created with canonical title
        $this->assertEquals($title, $newGame->releases()->first()->title);
        $this->assertEquals(true, $newGame->releases()->first()->is_canonical_game_title);

        $this->assertAuditComment(CommentableType::GameHash, $newGame->id, "$md5 linked by {$this->user->display_name}.");

        /* game and hash already exist (game will be matched by title and console) */
        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'i' => $title,
            'c' => $system->id,
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameID' => $newGame->id,
                'Response' => [
                    'GameID' => $newGame->id,
                ],
            ]);

        $this->assertFalse(Game::where('id', $newGame->id + 1)->exists());

        $newGame->refresh();
        $this->assertEquals($title, $newGame->title);
        $this->assertEquals(1, $newGame->hashes->count());
        $this->assertEquals($md5, $newGame->hashes->first()->md5);
        $this->assertEquals(GameHashCompatibility::Compatible, $newGame->hashes->first()->compatibility);
        $this->assertEquals(1, $newGame->achievementSets()->count());
        $this->assertEquals(1, $newGame->releases()->count());
        $this->assertEquals($title, $newGame->releases()->first()->title);

        /* game exists on another console */
        $md5 = fake()->md5;
        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'i' => $title,
            'c' => $system2->id,
            'd' => 'Game (U).nes',
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameID' => $newGame->id + 1,
                'Response' => [
                    'GameID' => $newGame->id + 1,
                ],
            ]);

        $newGame2 = Game::find($newGame->id + 1);
        $this->assertEquals($title, $newGame2->title);
        $this->assertEquals(1, $newGame2->hashes->count());
        $this->assertEquals($md5, $newGame2->hashes->first()->md5);
        $this->assertEquals('Game (U).nes', $newGame2->hashes->first()->name);
        $this->assertEquals(1, $newGame2->achievementSets()->count());
        $this->assertEquals(1, $newGame->releases()->count());
        $this->assertEquals($title, $newGame->releases()->first()->title);

        $this->assertAuditComment(CommentableType::GameHash, $newGame2->id, "$md5 linked by {$this->user->display_name}. Description: \"Game (U).nes\"");

        /* invalid title */
        $md5 = fake()->md5;
        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'i' => 'A',
            'c' => $system2->id,
        ]))
            ->assertStatus(422)
            ->assertExactJson([
                'Code' => 'invalid_parameter',
                'Error' => 'Title must be at least two characters long.',
                'Status' => 422,
                'Success' => false,
            ]);

        /* invalid hash */
        $this->get($this->apiUrl('submitgametitle', [
            'm' => '12345678',
            'i' => 'A',
            'c' => $system2->id,
        ]))
            ->assertStatus(422)
            ->assertExactJson([
                'Code' => 'invalid_parameter',
                'Error' => 'Hash must be 32 characters long.',
                'Status' => 422,
                'Success' => false,
            ]);
    }

    public function testSubmitNewMD5(): void
    {
        /** @var Game $game */
        $game = $this->seedGame();
        $this->assertEquals(1, $game->hashes->count());
        $oldTitle = $game->title;

        $this->seed(RolesTableSeeder::class);
        $this->addServerUser();

        $md5 = fake()->md5;
        $title = ucwords(fake()->words(2, true));

        /* regular user */
        $this->user->setAttribute('Permissions', Permissions::Registered);
        $this->user->save();

        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'g' => $game->id,
            'i' => $title,
            'c' => $game->system->id,
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Code' => 'access_denied',
                'Error' => 'You must be a developer to perform this action.',
                'Status' => 403,
                'Success' => false,
            ]);

        /* new md5 for existing game */
        $this->user->assignRole(Role::DEVELOPER);
        $this->user->save();

        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'g' => $game->id,
            'i' => $title,
            'c' => $game->system->id,
        ]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->id,
                'Response' => [
                    'GameID' => $game->id,
                ],
            ]);

        $game->refresh();
        $this->assertEquals($oldTitle, $game->title); // title should not be changed
        $this->assertEquals(2, $game->hashes->count());
        $this->assertEquals($md5, $game->hashes->slice(1, 1)->first()->md5);
        $this->assertEquals(GameHashCompatibility::Compatible, $game->hashes->slice(1, 1)->first()->compatibility);

        $this->assertAuditComment(CommentableType::GameHash, $game->id, "$md5 linked by {$this->user->display_name}.");
    }

    public function testSubmitInactiveConsole(): void
    {
        /** @var System $system */
        $system = System::factory()->create(['active' => false]);
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);

        $this->seed(RolesTableSeeder::class);
        $this->addServerUser();

        $md5 = fake()->md5;
        $title = ucwords(fake()->words(2, true));

        /* new game */
        $this->user->assignRole(Role::DEVELOPER);
        $this->user->save();

        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'i' => $title,
            'c' => $system->id,
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Code' => 'access_denied',
                'Error' => 'You do not have permission to add games to an inactive system.',
                'Status' => 403,
                'Success' => false,
            ]);

        $this->assertFalse(Game::where('id', $game->id + 1)->exists());

        /* new md5 for existing game */
        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'g' => $game->id,
            'i' => $title,
            'c' => $system->id,
        ]))
            ->assertStatus(403)
            ->assertExactJson([
                'Code' => 'access_denied',
                'Error' => 'You do not have permission to add hashes to games for an inactive system.',
                'Status' => 403,
                'Success' => false,
            ]);

        $game->refresh();
        $this->assertEquals(0, $game->hashes->count());

        /* hash manager role required to link to inactive console */
        $this->user->assignRole(Role::GAME_HASH_MANAGER);

        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'i' => $title,
            'c' => $system->id,
        ]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->id + 1,
                'Response' => [
                    'GameID' => $game->id + 1,
                ],
            ]);

        $newGame = Game::find($game->id + 1);
        $this->assertEquals($title, $newGame->title);
        $this->assertEquals(1, $newGame->hashes->count());
        $this->assertEquals($md5, $newGame->hashes->first()->md5);
        $this->assertEquals(GameHashCompatibility::Compatible, $newGame->hashes->first()->compatibility);
        $this->assertEquals(1, $newGame->achievementSets()->count());
        $this->assertEquals(1, $newGame->releases()->count());
        $this->assertEquals($title, $newGame->releases()->first()->title);

        $this->assertAuditComment(CommentableType::GameHash, $newGame->id, "$md5 linked by {$this->user->display_name}.");

        /* new md5 for existing game */
        $md5 = fake()->md5;
        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'g' => $newGame->id,
            'i' => $title,
            'c' => $system->id,
        ]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $newGame->id,
                'Response' => [
                    'GameID' => $newGame->id,
                ],
            ]);

        $newGame->refresh();
        $this->assertEquals($title, $newGame->title); // title should not be changed
        $this->assertEquals(2, $newGame->hashes->count());
        $this->assertEquals($md5, $newGame->hashes->slice(1, 1)->first()->md5);
        $this->assertEquals(GameHashCompatibility::Compatible, $newGame->hashes->slice(1, 1)->first()->compatibility);
        $this->assertEquals(1, $newGame->achievementSets()->count());
        $this->assertEquals(1, $newGame->releases()->count());
        $this->assertEquals($title, $newGame->releases()->first()->title);

        $this->assertAuditComment(CommentableType::GameHash, $newGame->id, "$md5 linked by {$this->user->display_name}.");
    }

    public function testSubmitNewSubsetGameWithExistingParent(): void
    {
        /** @var System $system */
        $system = System::factory()->create();

        // create the parent game first with its core achievement set
        /** @var Game $parentGame */
        $parentGame = Game::factory()->create([
            'Title' => 'Mega Man 2',
            'ConsoleID' => $system->id,
        ]);
        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($parentGame);

        $this->seed(RolesTableSeeder::class);
        $this->addServerUser();

        $this->user->assignRole(Role::DEVELOPER);
        $this->user->save();

        $md5 = fake()->md5;
        $subsetTitle = 'Mega Man 2 [Subset - Bonus]';

        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'i' => $subsetTitle,
            'c' => $system->id,
        ]))
            ->assertStatus(200)
            ->assertJsonStructure([
                'Success',
                'GameID',
                'Response' => ['GameID'],
            ]);

        // verify the subset game was created
        $subsetGame = Game::where('Title', $subsetTitle)->first();
        $this->assertNotNull($subsetGame);
        $this->assertEquals($subsetTitle, $subsetGame->title);
        $this->assertEquals($system->id, $subsetGame->ConsoleID);

        // verify the subset game has its own core achievement set
        $this->assertEquals(1, $subsetGame->gameAchievementSets()->count());
        $subsetCoreSet = $subsetGame->gameAchievementSets()->first();
        $this->assertEquals(AchievementSetType::Core, $subsetCoreSet->type);

        // verify the parent game now has the subset's achievement set attached as WillBeExclusive
        $parentGame->refresh();
        $parentGameSets = $parentGame->gameAchievementSets()->get();
        $this->assertEquals(2, $parentGameSets->count());

        $attachedSet = $parentGameSets->firstWhere('type', AchievementSetType::WillBeExclusive);
        $this->assertNotNull($attachedSet);
        $this->assertEquals('Bonus', $attachedSet->title);
        $this->assertEquals($subsetCoreSet->achievement_set_id, $attachedSet->achievement_set_id);
    }

    public function testSubmitNewSubsetGameWithNoParent(): void
    {
        /** @var System $system */
        $system = System::factory()->create();

        // ... we intentionally don't create a parent game right here ...

        $this->seed(RolesTableSeeder::class);
        $this->addServerUser();

        $this->user->assignRole(Role::DEVELOPER);
        $this->user->save();

        $md5 = fake()->md5;
        $subsetTitle = 'Nonexistent Game [Subset - Bonus]';

        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'i' => $subsetTitle,
            'c' => $system->id,
        ]))
            ->assertStatus(200)
            ->assertJsonStructure([
                'Success',
                'GameID',
                'Response' => ['GameID'],
            ]);

        // verify the subset game was created
        $subsetGame = Game::where('Title', $subsetTitle)->first();
        $this->assertNotNull($subsetGame);
        $this->assertEquals($subsetTitle, $subsetGame->title);

        // verify the subset game has its own core achievement set
        $this->assertEquals(1, $subsetGame->gameAchievementSets()->count());
        $subsetCoreSet = $subsetGame->gameAchievementSets()->first();
        $this->assertEquals(AchievementSetType::Core, $subsetCoreSet->type);

        // verify no parent attachment was made (since no parent exists)
        // the subset's achievement set should only be attached to itself
        $attachedGames = $subsetCoreSet->achievementSet->games()->get();
        $this->assertEquals(1, $attachedGames->count());
        $this->assertEquals($subsetGame->id, $attachedGames->first()->id);
    }
}
