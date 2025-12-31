<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\Game;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameInfoListTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;

    public function testGameInfoList(): void
    {
        /** @var System $system1 */
        $system1 = System::factory()->create();
        /** @var System $system2 */
        $system2 = System::factory()->create();

        /** @var Game $game1 */
        $game1 = Game::factory()->create(['title' => 'One', 'system_id' => $system1->id, 'image_icon_asset_path' => '/Images/000001.png', 'achievements_published' => 3]);
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['title' => 'Two', 'system_id' => $system2->id, 'image_icon_asset_path' => '/Images/000002.png', 'achievements_published' => 7]);
        /** @var Game $game3 */
        $game3 = Game::factory()->create(['title' => 'Three', 'system_id' => $system1->id, 'image_icon_asset_path' => '/Images/000003.png', 'achievements_published' => 11]);
        /** @var Game $game4 */
        $game4 = Game::factory()->create(['title' => 'Four', 'system_id' => $system2->id, 'image_icon_asset_path' => '/Images/000004.png', 'achievements_unpublished' => 5]);
        /** @var Game $game5 */
        $game5 = Game::factory()->create(['title' => 'Five', 'system_id' => $system1->id, 'image_icon_asset_path' => '/Images/000005.png', 'achievements_unpublished' => 9]);
        /** @var Game $game6 */
        $game6 = Game::factory()->create(['title' => 'Six', 'system_id' => $system2->id, 'image_icon_asset_path' => '/Images/000006.png', 'achievements_unpublished' => 1]);
        /** @var Game $game7 */
        $game7 = Game::factory()->create(['title' => 'Seven', 'system_id' => $system1->id, 'image_icon_asset_path' => '/Images/000007.png']);
        /** @var Game $game8 */
        $game8 = Game::factory()->create(['title' => 'Eight', 'system_id' => $system2->id, 'image_icon_asset_path' => '/Images/000008.png']);
        /** @var Game $game9 */
        $game9 = Game::factory()->create(['title' => 'Nine', 'system_id' => $system1->id, 'image_icon_asset_path' => '/Images/000009.png']);
        /** @var Game $game10 */
        $game10 = Game::factory()->create(['title' => 'Ten', 'system_id' => $system2->id, 'image_icon_asset_path' => '/Images/000010.png', 'achievements_published' => 2, 'achievements_unpublished' => 1]);

        // one game
        $this->get($this->apiUrl('gameinfolist', ['g' => '4']))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    ['ID' => 4, 'Title' => 'Four', 'ImageIcon' => '/Images/000004.png', 'ImageUrl' => '/media/Images/000004.png'],
                ],
            ]);

        // CSV of games
        $this->get($this->apiUrl('gameinfolist', ['g' => '1,3,5,7,9']))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    ['ID' => 1, 'Title' => 'One', 'ImageIcon' => '/Images/000001.png', 'ImageUrl' => '/media/Images/000001.png'],
                    ['ID' => 3, 'Title' => 'Three', 'ImageIcon' => '/Images/000003.png', 'ImageUrl' => '/media/Images/000003.png'],
                    ['ID' => 5, 'Title' => 'Five', 'ImageIcon' => '/Images/000005.png', 'ImageUrl' => '/media/Images/000005.png'],
                    ['ID' => 7, 'Title' => 'Seven', 'ImageIcon' => '/Images/000007.png', 'ImageUrl' => '/media/Images/000007.png'],
                    ['ID' => 9, 'Title' => 'Nine', 'ImageIcon' => '/Images/000009.png', 'ImageUrl' => '/media/Images/000009.png'],
                ],
            ]);

        // all unknown games
        $this->get($this->apiUrl('gameinfolist', ['g' => '99,98']))
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Status' => 404,
                'Code' => 'not_found',
                'Error' => 'Unknown games.',
            ]);

        // some unknown games - known games returned, unknown games ignored
        $this->get($this->apiUrl('gameinfolist', ['g' => '99,6']))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Response' => [
                    ['ID' => 6, 'Title' => 'Six', 'ImageIcon' => '/Images/000006.png', 'ImageUrl' => '/media/Images/000006.png'],
                ],
            ]);

        // empty list
        $this->get($this->apiUrl('gameinfolist', ['g' => '']))
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'invalid_parameter',
                'Error' => 'You must specify at least one game ID.',
            ]);

        // no list
        $this->get($this->apiUrl('gameinfolist', []))
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Error' => 'One or more required parameters is missing.',
            ]);
    }
}
