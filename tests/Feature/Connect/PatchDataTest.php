<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Community\Enums\AwardType;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\Leaderboard;
use App\Platform\Models\PlayerAchievement;
use App\Platform\Models\PlayerBadge;
use App\Platform\Models\PlayerGame;
use App\Platform\Models\PlayerSession;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class PatchDataTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;

    private function getAchievementPatchData(Achievement $achievement): array
    {
        return [
            'ID' => $achievement->ID,
            'Title' => $achievement->Title,
            'Description' => $achievement->Description,
            'MemAddr' => $achievement->MemAddr,
            'Points' => $achievement->Points,
            'Author' => $achievement->Author,
            'Modified' => $achievement->DateModified->unix(),
            'Created' => $achievement->DateCreated->unix(),
            'BadgeName' => $achievement->BadgeName,
            'Flags' => $achievement->Flags,
            'BadgeURL' => media_asset("Badge/{$achievement->BadgeName}.png"),
            'BadgeLockedURL' => media_asset("Badge/{$achievement->BadgeName}_lock.png"),
        ];
    }

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
        /** @var Leaderboard $leaderboard1 */
        $leaderboard1 = Leaderboard::factory()->create(['GameID' => $game->ID, 'DisplayOrder' => 2]);
        /** @var Leaderboard $leaderboard2 */
        $leaderboard2 = Leaderboard::factory()->create(['GameID' => $game->ID, 'DisplayOrder' => 1, 'Format' => 'SCORE']);
        /** @var Leaderboard $leaderboard3 */
        $leaderboard3 = Leaderboard::factory()->create(['GameID' => $game->ID, 'DisplayOrder' => -1, 'Format' => 'SECS']);

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
        $this->get($this->apiUrl('patch', ['g' => $game->ID]))
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
                ]
            ]);

        // only retrieve published achievements
        $this->get($this->apiUrl('patch', ['g' => $game->ID, 'f' => 3]))
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
                ]
            ]);

        // unknown game
        $this->get($this->apiUrl('patch', ['g' => 999999]))
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Unknown game',
                'Status' => 404,
                'Code' => 'not_found',
            ]);

        // game without achievements/leaderboards/rich presence
        $this->get($this->apiUrl('patch', ['g' => $game2->ID]))
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
                ]
            ]);
    }
}
