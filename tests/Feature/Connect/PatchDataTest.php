<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\ClientSupportLevel;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\PlayerGame;
use App\Models\System;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\AchievementFlag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Concerns\TestsEmulatorUserAgent;
use Tests\TestCase;

class PatchDataTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;
    use TestsEmulatorUserAgent;

    private string $unknownClientWarning = 'The server does not recognize this client and will not allow hardcore unlocks. Please send a message to RAdmin on the RetroAchievements website for information on how to submit your emulator for hardcore consideration.';

    private function getAchievementPatchData(Achievement $achievement, float $rarity = 100.0, float $rarityHardcore = 100.0): array
    {
        return [
            'ID' => $achievement->ID,
            'Title' => $achievement->Title,
            'Description' => $achievement->Description,
            'MemAddr' => $achievement->MemAddr,
            'Points' => $achievement->Points,
            'Author' => $achievement->developer->User,
            'Modified' => $achievement->DateModified->unix(),
            'Created' => $achievement->DateCreated->unix(),
            'BadgeName' => $achievement->BadgeName,
            'Flags' => $achievement->Flags,
            'Type' => $achievement->type,
            'Rarity' => $rarity,
            'RarityHardcore' => $rarityHardcore,
            'BadgeURL' => media_asset("Badge/{$achievement->BadgeName}.png"),
            'BadgeLockedURL' => media_asset("Badge/{$achievement->BadgeName}_lock.png"),
        ];
    }

    /* TODO: uncomment when functionality is enabled
    private function getWarningAchievementPatchData(ClientSupportLevel $clientSupportLevel): array
    {
        return [
            'ID' => Achievement::CLIENT_WARNING_ID,
            'MemAddr' => '1=1.300.',
            'Title' => ($clientSupportLevel === ClientSupportLevel::Outdated) ?
                'Warning: Outdated Client (please update)' : 'Warning: Unknown Client',
            'Description' => 'Hardcore unlocks cannot be earned using this client.',
            'Points' => 0,
            'Author' => '',
            'Modified' => Carbon::now()->unix(),
            'Created' => Carbon::now()->unix(),
            'BadgeName' => '00000',
            'Flags' => AchievementFlag::OfficialCore->value,
            'Type' => null,
            'Rarity' => 0.0,
            'RarityHardcore' => 0.0,
            'BadgeURL' => media_asset("Badge/00000.png"),
            'BadgeLockedURL' => media_asset("Badge/00000_lock.png"),
        ];
    }
    */

    private function getLeaderboardPatchData(Leaderboard $leaderboard): array
    {
        return [
            'ID' => $leaderboard->ID,
            'Mem' => $leaderboard->Mem,
            'Format' => $leaderboard->Format,
            'LowerIsBetter' => $leaderboard->LowerIsBetter,
            'Title' => $leaderboard->Title,
            'Description' => $leaderboard->Description,
            'Hidden' => ($leaderboard->DisplayOrder == -1),
        ];
    }

    public function testGameData(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ImageIcon' => '/Images/000011.png',
            'ImageTitle' => '/Images/000021.png',
            'ImageIngame' => '/Images/000031.png',
            'ImageBoxArt' => '/Images/000041.png',
            'Publisher' => 'WePublishStuff',
            'Developer' => 'WeDevelopStuff',
            'Genre' => 'Action',
            'Released' => 'Jan 1989',
            'RichPresencePatch' => 'Display:\nTest',
        ]);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->progression()->create(['GameID' => $game->ID, 'BadgeName' => '12345', 'DisplayOrder' => 1]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'BadgeName' => '23456', 'DisplayOrder' => 3]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'BadgeName' => '34567', 'DisplayOrder' => 2]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->published()->progression()->create(['GameID' => $game->ID, 'BadgeName' => '45678', 'DisplayOrder' => 5]);
        /** @var Achievement $achievement5 */
        $achievement5 = Achievement::factory()->create(['GameID' => $game->ID, 'BadgeName' => '56789', 'DisplayOrder' => 6, 'Flags' => 0]);
        /** @var Achievement $achievement6 */
        $achievement6 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'BadgeName' => '98765', 'DisplayOrder' => 7]);
        /** @var Achievement $achievement7 */
        $achievement7 = Achievement::factory()->published()->winCondition()->create(['GameID' => $game->ID, 'BadgeName' => '87654', 'DisplayOrder' => 4]);
        /** @var Achievement $achievement8 */
        $achievement8 = Achievement::factory()->create(['GameID' => $game->ID, 'BadgeName' => '76543', 'DisplayOrder' => 8]);
        /** @var Achievement $achievement9 */
        $achievement9 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'BadgeName' => '65432', 'DisplayOrder' => 9]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        /** @var Leaderboard $leaderboard1 */
        $leaderboard1 = Leaderboard::factory()->create(['GameID' => $game->ID, 'DisplayOrder' => 2]);
        /** @var Leaderboard $leaderboard2 */
        $leaderboard2 = Leaderboard::factory()->create(['GameID' => $game->ID, 'DisplayOrder' => 1, 'Format' => 'SCORE']);
        /** @var Leaderboard $leaderboard3 */
        $leaderboard3 = Leaderboard::factory()->create(['GameID' => $game->ID, 'DisplayOrder' => -1, 'Format' => 'SECS']);

        $this->seedEmulatorUserAgents();

        /** @var Game $game2 */
        $game2 = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ImageIcon' => '/Images/000051.png',
            'ImageTitle' => '/Images/000061.png',
            'ImageIngame' => '/Images/000071.png',
            'ImageBoxArt' => '/Images/000081.png',
            'Publisher' => 'WePublishStuff',
            'Developer' => 'WeDevelopStuff',
            'Genre' => 'Action',
            'Released' => 'Jan 1989',
            'RichPresencePatch' => '',
        ]);

        // general use case
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('patch', ['g' => $game->ID]))
            ->assertExactJson([
                'Success' => true,
                'PatchData' => [
                    'ID' => $game->ID,
                    'Title' => $game->Title,
                    'ConsoleID' => $game->ConsoleID,
                    'ImageIcon' => $game->ImageIcon,
                    'ImageIconURL' => media_asset($game->ImageIcon),
                    'RichPresencePatch' => $game->RichPresencePatch,
                    'Achievements' => [
                        $this->getAchievementPatchData($achievement1), // DisplayOrder: 1
                        $this->getAchievementPatchData($achievement3), // DisplayOrder: 2
                        $this->getAchievementPatchData($achievement2), // DisplayOrder: 3
                        $this->getAchievementPatchData($achievement7), // DisplayOrder: 4
                        $this->getAchievementPatchData($achievement4), // DisplayOrder: 5
                        // $achievement5 (DisplayOrder: 6) has invalid flags and should not be returned
                        $this->getAchievementPatchData($achievement6), // DisplayOrder: 7
                        $this->getAchievementPatchData($achievement8), // DisplayOrder: 8 (unofficial)
                        $this->getAchievementPatchData($achievement9), // DisplayOrder: 9
                    ],
                    'Leaderboards' => [
                        $this->getLeaderboardPatchData($leaderboard3), // DisplayOrder: -1
                        $this->getLeaderboardPatchData($leaderboard2), // DisplayOrder: 1
                        $this->getLeaderboardPatchData($leaderboard1), // DisplayOrder: 2
                    ],
                ],
            ]);

        // only retrieve published achievements
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('patch', ['g' => $game->ID, 'f' => 3]))
            ->assertExactJson([
                'Success' => true,
                'PatchData' => [
                    'ID' => $game->ID,
                    'Title' => $game->Title,
                    'ConsoleID' => $game->ConsoleID,
                    'ImageIcon' => $game->ImageIcon,
                    'ImageIconURL' => media_asset($game->ImageIcon),
                    'RichPresencePatch' => $game->RichPresencePatch,
                    'Achievements' => [
                        $this->getAchievementPatchData($achievement1), // DisplayOrder: 1
                        $this->getAchievementPatchData($achievement3), // DisplayOrder: 2
                        $this->getAchievementPatchData($achievement2), // DisplayOrder: 3
                        $this->getAchievementPatchData($achievement7), // DisplayOrder: 4
                        $this->getAchievementPatchData($achievement4), // DisplayOrder: 5
                        // $achievement5 (DisplayOrder: 6) has invalid flags and should not be returned
                        $this->getAchievementPatchData($achievement6), // DisplayOrder: 7
                        // $achievement8 (DisplayOrder: 8) is unofficial
                        $this->getAchievementPatchData($achievement9), // DisplayOrder: 9
                    ],
                    'Leaderboards' => [
                        $this->getLeaderboardPatchData($leaderboard3), // DisplayOrder: -1
                        $this->getLeaderboardPatchData($leaderboard2), // DisplayOrder: 1
                        $this->getLeaderboardPatchData($leaderboard1), // DisplayOrder: 2
                    ],
                ],
            ]);

        // achievement with null author should not return null (see https://github.com/libretro/RetroArch/issues/16648)
        $achievement3->user_id = null;
        $achievement3->save();
        $achievement3PatchData = $this->getAchievementPatchData($achievement3);
        $achievement3PatchData['Author'] = '';
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('patch', ['g' => $game->ID, 'f' => 3]))
            ->assertExactJson([
                'Success' => true,
                'PatchData' => [
                    'ID' => $game->ID,
                    'Title' => $game->Title,
                    'ConsoleID' => $game->ConsoleID,
                    'ImageIcon' => $game->ImageIcon,
                    'ImageIconURL' => media_asset($game->ImageIcon),
                    'RichPresencePatch' => $game->RichPresencePatch,
                    'Achievements' => [
                        $this->getAchievementPatchData($achievement1), // DisplayOrder: 1
                        $achievement3PatchData, // DisplayOrder: 2
                        $this->getAchievementPatchData($achievement2), // DisplayOrder: 3
                        $this->getAchievementPatchData($achievement7), // DisplayOrder: 4
                        $this->getAchievementPatchData($achievement4), // DisplayOrder: 5
                        // $achievement5 (DisplayOrder: 6) has invalid flags and should not be returned
                        $this->getAchievementPatchData($achievement6), // DisplayOrder: 7
                        // $achievement8 (DisplayOrder: 8) is unofficial
                        $this->getAchievementPatchData($achievement9), // DisplayOrder: 9
                    ],
                    'Leaderboards' => [
                        $this->getLeaderboardPatchData($leaderboard3), // DisplayOrder: -1
                        $this->getLeaderboardPatchData($leaderboard2), // DisplayOrder: 1
                        $this->getLeaderboardPatchData($leaderboard1), // DisplayOrder: 2
                    ],
                ],
            ]);

        // unknown game
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('patch', ['g' => 999999]))
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Unknown game',
                'Status' => 404,
                'Code' => 'not_found',
            ]);

        // game without achievements/leaderboards/rich presence
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('patch', ['g' => $game2->ID]))
            ->assertExactJson([
                'Success' => true,
                'PatchData' => [
                    'ID' => $game2->ID,
                    'Title' => $game2->Title,
                    'ConsoleID' => $game2->ConsoleID,
                    'ImageIcon' => $game2->ImageIcon,
                    'ImageIconURL' => media_asset($game2->ImageIcon),
                    'RichPresencePatch' => '',
                    'Achievements' => [],
                    'Leaderboards' => [],
                ],
            ]);
    }

    public function testAchievementRarity(): void
    {
        // testGameData already handled the case where the game has no play history
        // (all rarities should be expected to be 100.0)

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ImageIcon' => '/Images/000011.png',
        ]);
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'BadgeName' => '12345', 'DisplayOrder' => 1]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'BadgeName' => '23456', 'DisplayOrder' => 2]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'BadgeName' => '34567', 'DisplayOrder' => 3]);

        $game->players_total = 11;
        $game->players_hardcore = 9; // both rarity calculations should use the non-hardcore player count
        $game->save();

        // rarity calculation = (unlocks + 1) / (num_players) [max:100.0]
        $achievement1->unlocks_total = 10;
        $achievement1->unlocks_hardcore_total = 9;
        $achievement1->save();

        $achievement2->unlocks_total = 7;
        $achievement2->unlocks_hardcore_total = 5;
        $achievement2->save();

        $achievement3->unlocks_total = 2;
        $achievement3->unlocks_hardcore_total = 0;
        $achievement3->save();

        $this->seedEmulatorUserAgents();

        // if the player has never played game before, the number of players will be incremented to calculate rarity
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('patch', ['g' => $game->ID]))
            ->assertExactJson([
                'Success' => true,
                'PatchData' => [
                    'ID' => $game->ID,
                    'Title' => $game->Title,
                    'ConsoleID' => $game->ConsoleID,
                    'ImageIcon' => $game->ImageIcon,
                    'ImageIconURL' => media_asset($game->ImageIcon),
                    'RichPresencePatch' => $game->RichPresencePatch,
                    'Achievements' => [
                        $this->getAchievementPatchData($achievement1, 91.67, 83.33), // 11/12=91.67, 10/12=83.33
                        $this->getAchievementPatchData($achievement2, 66.67, 50.00), //  8/12=66.67,  6/12=50.00
                        $this->getAchievementPatchData($achievement3, 25.00, 8.33),  //  3/12=25.00,  1/12= 8.33
                    ],
                    'Leaderboards' => [],
                ],
            ]);

        // if the player has played the game before, the number of players will not be incremented to calculate rarity
        // addHardcoreUnlock will create a player_game for game. need to manually create one for game2
        $playerGame = new PlayerGame([
            'user_id' => $this->user->ID,
            'game_id' => $game->ID,
        ]);
        $playerGame->save();
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('patch', ['g' => $game->ID]))
            ->assertExactJson([
                'Success' => true,
                'PatchData' => [
                    'ID' => $game->ID,
                    'Title' => $game->Title,
                    'ConsoleID' => $game->ConsoleID,
                    'ImageIcon' => $game->ImageIcon,
                    'ImageIconURL' => media_asset($game->ImageIcon),
                    'RichPresencePatch' => $game->RichPresencePatch,
                    'Achievements' => [
                        $this->getAchievementPatchData($achievement1, 100.00, 90.91), // 11/11=100.00, 10/11=90.91
                        $this->getAchievementPatchData($achievement2, 72.73, 54.55),  //  8/11= 72.73,  6/11=54.55
                        $this->getAchievementPatchData($achievement3, 27.27, 9.09),   //  3/11= 27.27,  1/11= 9.09
                    ],
                    'Leaderboards' => [],
                ],
            ]);
    }

    public function testUserAgent(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'ConsoleID' => $system->ID,
            'ImageIcon' => '/Images/000011.png',
            'ImageTitle' => '/Images/000021.png',
            'ImageIngame' => '/Images/000031.png',
            'ImageBoxArt' => '/Images/000041.png',
            'Publisher' => 'WePublishStuff',
            'Developer' => 'WeDevelopStuff',
            'Genre' => 'Action',
            'Released' => 'Jan 1989',
            'RichPresencePatch' => 'Display:\nTest',
        ]);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->progression()->create(['GameID' => $game->ID, 'BadgeName' => '12345', 'DisplayOrder' => 1]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'BadgeName' => '23456', 'DisplayOrder' => 3]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID, 'BadgeName' => '34567', 'DisplayOrder' => 2]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->published()->progression()->create(['GameID' => $game->ID, 'BadgeName' => '45678', 'DisplayOrder' => 5]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $this->seedEmulatorUserAgents();

        // no user agent
        $this->get($this->apiUrl('patch', ['g' => $game->ID]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'PatchData' => [
                    'ID' => $game->ID,
                    'Title' => $game->Title,
                    'ConsoleID' => $game->ConsoleID,
                    'ImageIcon' => $game->ImageIcon,
                    'ImageIconURL' => media_asset($game->ImageIcon),
                    'RichPresencePatch' => $game->RichPresencePatch,
                    'Achievements' => [
                        // $this->getWarningAchievementPatchData(ClientSupportLevel::Unknown),
                        $this->getAchievementPatchData($achievement1), // DisplayOrder: 1
                        $this->getAchievementPatchData($achievement3), // DisplayOrder: 2
                        $this->getAchievementPatchData($achievement2), // DisplayOrder: 3
                        $this->getAchievementPatchData($achievement4), // DisplayOrder: 5
                    ],
                    'Leaderboards' => [],
                ],
                'Warning' => $this->unknownClientWarning,
            ]);

        // unknown user agent
        $this->withHeaders(['User-Agent' => $this->userAgentUnknown])
            ->get($this->apiUrl('patch', ['g' => $game->ID]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'PatchData' => [
                    'ID' => $game->ID,
                    'Title' => $game->Title,
                    'ConsoleID' => $game->ConsoleID,
                    'ImageIcon' => $game->ImageIcon,
                    'ImageIconURL' => media_asset($game->ImageIcon),
                    'RichPresencePatch' => $game->RichPresencePatch,
                    'Achievements' => [
                        // $this->getWarningAchievementPatchData(ClientSupportLevel::Unknown),
                        $this->getAchievementPatchData($achievement1), // DisplayOrder: 1
                        $this->getAchievementPatchData($achievement3), // DisplayOrder: 2
                        $this->getAchievementPatchData($achievement2), // DisplayOrder: 3
                        $this->getAchievementPatchData($achievement4), // DisplayOrder: 5
                    ],
                    'Leaderboards' => [],
                ],
                'Warning' => $this->unknownClientWarning,
            ]);

        // outdated user agent
        $this->withHeaders(['User-Agent' => $this->userAgentOutdated])
            ->get($this->apiUrl('patch', ['g' => $game->ID]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'PatchData' => [
                    'ID' => $game->ID,
                    'Title' => $game->Title,
                    'ConsoleID' => $game->ConsoleID,
                    'ImageIcon' => $game->ImageIcon,
                    'ImageIconURL' => media_asset($game->ImageIcon),
                    'RichPresencePatch' => $game->RichPresencePatch,
                    'Achievements' => [
                        // $this->getWarningAchievementPatchData(ClientSupportLevel::Outdated),
                        $this->getAchievementPatchData($achievement1), // DisplayOrder: 1
                        $this->getAchievementPatchData($achievement3), // DisplayOrder: 2
                        $this->getAchievementPatchData($achievement2), // DisplayOrder: 3
                        $this->getAchievementPatchData($achievement4), // DisplayOrder: 5
                    ],
                    'Leaderboards' => [],
                ],
            ]);

        // blocked user agent
        $this->withHeaders(['User-Agent' => $this->userAgentBlocked])
            ->get($this->apiUrl('patch', ['g' => $game->ID]))
            ->assertStatus(403)
            ->assertExactJson([
                'Status' => 403,
                'Success' => false,
                'Error' => 'This client is not supported',
            ]);

        // valid user agent
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('patch', ['g' => $game->ID]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'PatchData' => [
                    'ID' => $game->ID,
                    'Title' => $game->Title,
                    'ConsoleID' => $game->ConsoleID,
                    'ImageIcon' => $game->ImageIcon,
                    'ImageIconURL' => media_asset($game->ImageIcon),
                    'RichPresencePatch' => $game->RichPresencePatch,
                    'Achievements' => [
                        $this->getAchievementPatchData($achievement1), // DisplayOrder: 1
                        $this->getAchievementPatchData($achievement3), // DisplayOrder: 2
                        $this->getAchievementPatchData($achievement2), // DisplayOrder: 3
                        $this->getAchievementPatchData($achievement4), // DisplayOrder: 5
                    ],
                    'Leaderboards' => [],
                ],
            ]);
    }
}
