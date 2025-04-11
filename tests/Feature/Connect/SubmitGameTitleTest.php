<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Community\Enums\ArticleType;
use App\Enums\GameHashCompatibility;
use App\Enums\Permissions;
use App\Models\Game;
use App\Models\System;
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
        $game1 = Game::factory()->create(['ConsoleID' => $system2->id]);

        $this->addServerUser();

        $md5 = fake()->md5;
        $title = ucwords(fake()->words(2, true));

        /* must be developer */
        $this->user->setAttribute('Permissions', Permissions::Registered);
        $this->user->save();

        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'i' => $title,
            'c' => $system->id,
        ]))
            ->assertExactJson([
                'Success' => false,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        $this->user->setAttribute('Permissions', Permissions::JuniorDeveloper);
        $this->user->save();

        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'i' => $title,
            'c' => $system->id,
        ]))
            ->assertExactJson([
                'Success' => false,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        /* new game */
        $this->user->setAttribute('Permissions', Permissions::Developer);
        $this->user->save();

        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'i' => $title,
            'c' => $system->id,
        ]))
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'GameID' => $game1->id + 1,
                ],
            ]);

        $newGame = Game::find($game1->id + 1);
        $this->assertEquals($title, $newGame->title);
        $this->assertEquals(1, $newGame->hashes->count());
        $this->assertEquals($md5, $newGame->hashes->first()->md5);
        $this->assertEquals(GameHashCompatibility::Compatible, $newGame->hashes->first()->compatibility);

        $this->assertAuditComment(ArticleType::GameHash, $newGame->id, "$md5 linked by {$this->user->display_name}.");

        /* game and hash already exist (game will be matched by title and console) */
        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'i' => $title,
            'c' => $system->id,
        ]))
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'GameID' => $newGame->id,
                ],
            ]);

        $this->assertFalse(Game::where('ID', $newGame->id + 1)->exists());

        $newGame->refresh();
        $this->assertEquals($title, $newGame->title);
        $this->assertEquals(1, $newGame->hashes->count());
        $this->assertEquals($md5, $newGame->hashes->first()->md5);
        $this->assertEquals(GameHashCompatibility::Compatible, $newGame->hashes->first()->compatibility);

        /* game exists on another console */
        $md5 = fake()->md5;
        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'i' => $title,
            'c' => $system2->id,
            'd' => 'Game (U).nes',
        ]))
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'GameID' => $newGame->id + 1,
                ],
            ]);

        $newGame2 = Game::find($newGame->id + 1);
        $this->assertEquals($title, $newGame2->title);
        $this->assertEquals(1, $newGame2->hashes->count());
        $this->assertEquals($md5, $newGame2->hashes->first()->md5);
        $this->assertEquals('Game (U).nes', $newGame2->hashes->first()->name);

        $this->assertAuditComment(ArticleType::GameHash, $newGame2->id, "$md5 linked by {$this->user->display_name}. Description: \"Game (U).nes\"");

        /* invalid title */
        $md5 = fake()->md5;
        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'i' => 'A',
            'c' => $system2->id,
        ]))
            ->assertExactJson([
                'Success' => false,
                'Error' => "Cannot submit game title given as 'A'",
            ]);
    }

    public function testSubmitNewMD5(): void
    {
        /** @var Game $game */
        $game = $this->seedGame();
        $this->assertEquals(1, $game->hashes->count());
        $oldTitle = $game->title;

        $this->addServerUser();

        $md5 = fake()->md5;
        $title = ucwords(fake()->words(2, true));

        /* must be developer */
        $this->user->setAttribute('Permissions', Permissions::Registered);
        $this->user->save();

        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'g' => $game->id,
            'i' => $title,
            'c' => $game->system->id,
        ]))
            ->assertExactJson([
                'Success' => false,
                'Error' => 'You must be a developer to perform this action! Please drop a message in the forums to apply.',
            ]);

        /* new md5 */
        $this->user->setAttribute('Permissions', Permissions::Developer);
        $this->user->save();

        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'g' => $game->id,
            'i' => $title,
            'c' => $game->system->id,
        ]))
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'GameID' => $game->id,
                ],
            ]);

        $game->refresh();
        $this->assertEquals($oldTitle, $game->title); // title should not be changed
        $this->assertEquals(2, $game->hashes->count());
        $this->assertEquals($md5, $game->hashes->slice(1, 1)->first()->md5);
        $this->assertEquals(GameHashCompatibility::Compatible, $game->hashes->slice(1, 1)->first()->compatibility);

        $this->assertAuditComment(ArticleType::GameHash, $game->id, "$md5 linked by {$this->user->display_name}.");
    }

    public function testSubmitInactiveConsole(): void
    {
        /** @var System $system */
        $system = System::factory()->create(['active' => false]);
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        $this->addServerUser();

        $md5 = fake()->md5;
        $title = ucwords(fake()->words(2, true));

        /* new game */
        $this->user->setAttribute('Permissions', Permissions::Developer);
        $this->user->save();

        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'i' => $title,
            'c' => $system->id,
        ]))
            ->assertExactJson([
                'Success' => false,
                'Error' => "Cannot submit game title for unknown ConsoleID {$system->id}",
            ]);

        $this->assertFalse(Game::where('ID', $game->id + 1)->exists());

        /* new md5 */
        $this->user->setAttribute('Permissions', Permissions::Developer);
        $this->user->save();

        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'g' => $game->id,
            'i' => $title,
            'c' => $system->id,
        ]))
            ->assertExactJson([
                'Success' => false,
                'Error' => "Cannot submit game title for unknown ConsoleID {$system->id}",
            ]);

        $game->refresh();
        $this->assertEquals(0, $game->hashes->count());

        /* moderator can link to inactive console */
        $this->user->setAttribute('Permissions', Permissions::Moderator);
        $this->user->save();

        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'i' => $title,
            'c' => $system->id,
        ]))
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'GameID' => $game->id + 1,
                ],
            ]);

        $newGame = Game::find($game->id + 1);
        $this->assertEquals($title, $newGame->title);
        $this->assertEquals(1, $newGame->hashes->count());
        $this->assertEquals($md5, $newGame->hashes->first()->md5);
        $this->assertEquals(GameHashCompatibility::Compatible, $newGame->hashes->first()->compatibility);

        $this->assertAuditComment(ArticleType::GameHash, $newGame->id, "$md5 linked by {$this->user->display_name}.");

        /* new md5 */
        $md5 = fake()->md5;
        $this->get($this->apiUrl('submitgametitle', [
            'm' => $md5,
            'g' => $newGame->id,
            'i' => $title,
            'c' => $system->id,
        ]))
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    'GameID' => $newGame->id,
                ],
            ]);

        $newGame->refresh();
        $this->assertEquals($title, $newGame->title); // title should not be changed
        $this->assertEquals(2, $newGame->hashes->count());
        $this->assertEquals($md5, $newGame->hashes->slice(1, 1)->first()->md5);
        $this->assertEquals(GameHashCompatibility::Compatible, $newGame->hashes->slice(1, 1)->first()->compatibility);

        $this->assertAuditComment(ArticleType::GameHash, $newGame->id, "$md5 linked by {$this->user->display_name}.");
    }
}
