<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Enums\ClientSupportLevel;
use App\Enums\GameHashCompatibility;
use App\Enums\UserPreference;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Models\Leaderboard;
use App\Models\PlayerGame;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use App\Models\UserGameAchievementSetPreference;
use App\Platform\Actions\AssociateAchievementSetToGameAction;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\LeaderboardState;
use App\Platform\Services\VirtualGameIdService;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\TestsEmulatorUserAgent;

uses(LazilyRefreshDatabase::class);
uses(TestsConnect::class);
uses(TestsEmulatorUserAgent::class);

class AchievementSetsTestHelpers
{
    public static function getAchievementPatchData(Achievement $achievement, float $rarity = 100.0, float $rarityHardcore = 100.0): array
    {
        $achievement->loadMissing('developer');

        return [
            'ID' => $achievement->id,
            'Title' => $achievement->title,
            'Description' => $achievement->description,
            'MemAddr' => $achievement->trigger_definition,
            'Points' => $achievement->points,
            'Author' => $achievement->developer?->display_name,
            'Modified' => $achievement->modified_at->unix(),
            'Created' => $achievement->created_at->unix(),
            'BadgeName' => $achievement->image_name,
            'Flags' => $achievement->flags,
            'Type' => $achievement->type,
            'Rarity' => $rarity,
            'RarityHardcore' => $rarityHardcore,
            'BadgeURL' => media_asset("Badge/{$achievement->image_name}.png"),
            'BadgeLockedURL' => media_asset("Badge/{$achievement->image_name}_lock.png"),
        ];
    }

    public static function getLeaderboardPatchData(Leaderboard $leaderboard): array
    {
        return [
            'ID' => $leaderboard->id,
            'Mem' => $leaderboard->trigger_definition,
            'Format' => $leaderboard->format,
            'LowerIsBetter' => $leaderboard->rank_asc,
            'Title' => $leaderboard->title,
            'Description' => $leaderboard->description,
            'Hidden' => ($leaderboard->order_column == -1),
        ];
    }

    public static function getWarningAchievementPatchData(string $title, string $description): array
    {
        return [
            'ID' => Achievement::CLIENT_WARNING_ID,
            'MemAddr' => '1=1.300.',
            'Title' => $title,
            'Description' => $description,
            'Points' => 0,
            'Author' => '',
            'Modified' => Carbon::now()->unix(),
            'Created' => Carbon::now()->unix(),
            'BadgeName' => '00000',
            'Flags' => Achievement::FLAG_PROMOTED,
            'Type' => null,
            'Rarity' => 0.0,
            'RarityHardcore' => 0.0,
            'BadgeURL' => media_asset("Badge/00000.png"),
            'BadgeLockedURL' => media_asset("Badge/00000_lock.png"),
        ];
    }

    public static function getClientWarningAchievementPatchData(ClientSupportLevel $clientSupportLevel): array
    {
        return AchievementSetsTestHelpers::getWarningAchievementPatchData(
            title: match ($clientSupportLevel) {
                ClientSupportLevel::Outdated => 'Warning: Outdated Emulator (please update)',
                ClientSupportLevel::Unsupported => 'Warning: Unsupported Emulator',
                default => 'Warning: Unknown Emulator',
            },
            description: ($clientSupportLevel === ClientSupportLevel::Outdated) ?
                'Hardcore unlocks cannot be earned using this version of this emulator.' :
                'Hardcore unlocks cannot be earned using this emulator.',
        );
    }

    public static function createSimpleGame(): array
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'image_icon_asset_path' => '/Images/000010.png',
            'image_title_asset_path' => '/Images/000020.png',
            'image_ingame_asset_path' => '/Images/000030.png',
            'image_box_art_asset_path' => '/Images/000040.png',
            'publisher' => 'WePublishStuff',
            'developer' => 'WeDevelopStuff',
            'genre' => 'Action',
            'released_at' => Carbon::parse('1989-01-15'),
            'released_at_granularity' => 'month',
            'trigger_definition' => 'Display:\nTest',
        ]);

        /** @var User $author */
        $author = User::factory()->create(['display_name' => 'SetAuthor']);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->promoted()->progression()->create(['game_id' => $game->id, 'image_name' => '12345', 'order_column' => 1, 'user_id' => $author->id]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'image_name' => '23456', 'order_column' => 3, 'user_id' => $author->id]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'image_name' => '34567', 'order_column' => 2, 'user_id' => $author->id]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        return [
            'game' => $game,
            'achievements' => [
                $achievement1,
                $achievement2,
                $achievement3,
            ],
            'leaderboards' => [],
        ];
    }

    public static function createGameWithUnpublishedAchievements(): array
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'image_icon_asset_path' => '/Images/000011.png',
            'image_title_asset_path' => '/Images/000021.png',
            'image_ingame_asset_path' => '/Images/000031.png',
            'image_box_art_asset_path' => '/Images/000041.png',
            'publisher' => 'WePublishStuff',
            'developer' => 'WeDevelopStuff',
            'genre' => 'Action',
            'released_at' => Carbon::parse('1989-01-15'),
            'released_at_granularity' => 'month',
            'trigger_definition' => 'Display:\nTest',
        ]);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->promoted()->progression()->create(['game_id' => $game->id, 'image_name' => '12345', 'order_column' => 1]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'image_name' => '23456', 'order_column' => 3]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'image_name' => '34567', 'order_column' => 2]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->promoted()->progression()->create(['game_id' => $game->id, 'image_name' => '45678', 'order_column' => 5]);
        /** @var Achievement $achievement5 */
        $achievement5 = Achievement::factory()->create(['game_id' => $game->id, 'image_name' => '56789', 'order_column' => 6, 'is_promoted' => false]);
        /** @var Achievement $achievement6 */
        $achievement6 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'image_name' => '98765', 'order_column' => 7]);
        /** @var Achievement $achievement7 */
        $achievement7 = Achievement::factory()->promoted()->winCondition()->create(['game_id' => $game->id, 'image_name' => '87654', 'order_column' => 4]);
        /** @var Achievement $achievement8 */
        $achievement8 = Achievement::factory()->create(['game_id' => $game->id, 'image_name' => '76543', 'order_column' => 8]);
        /** @var Achievement $achievement9 */
        $achievement9 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'image_name' => '65432', 'order_column' => 9]);

        /** @var Leaderboard $leaderboard1 */
        $leaderboard1 = Leaderboard::factory()->create(['game_id' => $game->id, 'order_column' => 2]);
        /** @var Leaderboard $leaderboard2 */
        $leaderboard2 = Leaderboard::factory()->create(['game_id' => $game->id, 'order_column' => 1, 'format' => 'SCORE']);
        /** @var Leaderboard $leaderboard3 */
        $leaderboard3 = Leaderboard::factory()->create(['game_id' => $game->id, 'order_column' => -1, 'format' => 'SECS']);
        /** @var Leaderboard $leaderboard4 */
        $leaderboard4 = Leaderboard::factory()->create(['game_id' => $game->id, 'order_column' => 3, 'format' => 'SECS', 'state' => LeaderboardState::Unpublished]);
        /** @var Leaderboard $leaderboard5 */
        $leaderboard5 = Leaderboard::factory()->create(['game_id' => $game->id, 'order_column' => 4, 'format' => 'SECS', 'state' => LeaderboardState::Disabled]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        return [
            'game' => $game,
            'achievements' => [
                $achievement1,
                $achievement2,
                $achievement3,
                $achievement4,
                $achievement5,
                $achievement6,
                $achievement7,
                $achievement8,
                $achievement9,
            ],
            'leaderboards' => [
                $leaderboard1,
                $leaderboard2,
                $leaderboard3,
            ],
        ];
    }

    public static function createMultiSetGame(): array
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'image_icon_asset_path' => '/Images/000011.png',
            'image_title_asset_path' => '/Images/000021.png',
            'image_ingame_asset_path' => '/Images/000031.png',
            'image_box_art_asset_path' => '/Images/000041.png',
            'publisher' => 'WePublishStuff',
            'developer' => 'WeDevelopStuff',
            'genre' => 'Action',
            'released_at' => Carbon::parse('1989-01-15'),
            'released_at_granularity' => 'month',
            'trigger_definition' => 'Display:\nTest',
        ]);
        /** @var Game $bonusGame */
        $bonusGame = Game::factory()->create([
            'system_id' => $system->id,
            'image_icon_asset_path' => '/Images/000012.png',
            'trigger_definition' => 'Display:\nBonus Test',
        ]);
        /** @var Game $specialtyGame */
        $specialtyGame = Game::factory()->create([
            'system_id' => $system->id,
            'image_icon_asset_path' => '/Images/000013.png',
            'trigger_definition' => 'Display:\nSpecialty Test',
        ]);
        /** @var Game $exclusiveGame */
        $exclusiveGame = Game::factory()->create([
            'system_id' => $system->id,
            'image_icon_asset_path' => '/Images/000014.png',
            'trigger_definition' => 'Display:\nExclusive Test',
        ]);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->promoted()->progression()->create(['game_id' => $game->id, 'image_name' => '12345', 'order_column' => 1]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'image_name' => '23456', 'order_column' => 3]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'image_name' => '34567', 'order_column' => 2]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->promoted()->progression()->create(['game_id' => $game->id, 'image_name' => '45678', 'order_column' => 5]);
        /** @var Achievement $achievement5 */
        $achievement5 = Achievement::factory()->create(['game_id' => $game->id, 'image_name' => '56789', 'order_column' => 6, 'is_promoted' => false]);
        /** @var Achievement $achievement6 */
        $achievement6 = Achievement::factory()->promoted()->create(['game_id' => $bonusGame->id, 'image_name' => '98765', 'order_column' => 7]);
        /** @var Achievement $achievement7 */
        $achievement7 = Achievement::factory()->promoted()->winCondition()->create(['game_id' => $bonusGame->id, 'image_name' => '87654', 'order_column' => 4]);
        /** @var Achievement $achievement8 */
        $achievement8 = Achievement::factory()->create(['game_id' => $bonusGame->id, 'image_name' => '76543', 'order_column' => 8]);
        /** @var Achievement $achievement9 */
        $achievement9 = Achievement::factory()->promoted()->create(['game_id' => $bonusGame->id, 'image_name' => '65432', 'order_column' => 9]);
        /** @var Achievement $achievement10 */
        $achievement10 = Achievement::factory()->promoted()->create(['game_id' => $specialtyGame->id, 'image_name' => '54321', 'order_column' => 10]);
        /** @var Achievement $achievement11 */
        $achievement11 = Achievement::factory()->promoted()->create(['game_id' => $exclusiveGame->id, 'image_name' => '43210', 'order_column' => 11]);

        /** @var Leaderboard $leaderboard1 */
        $leaderboard1 = Leaderboard::factory()->create(['game_id' => $game->id, 'order_column' => 2]);
        /** @var Leaderboard $leaderboard2 */
        $leaderboard2 = Leaderboard::factory()->create(['game_id' => $game->id, 'order_column' => 1, 'format' => 'SCORE']);
        /** @var Leaderboard $leaderboard3 */
        $leaderboard3 = Leaderboard::factory()->create(['game_id' => $bonusGame->id, 'order_column' => -1, 'format' => 'SECS']);

        $buildAchievementSetaction = new UpsertGameCoreAchievementSetFromLegacyFlagsAction();
        $buildAchievementSetaction->execute($game);
        $buildAchievementSetaction->execute($bonusGame);
        $buildAchievementSetaction->execute($specialtyGame);
        $buildAchievementSetaction->execute($exclusiveGame);

        $associateSetAction = new AssociateAchievementSetToGameAction();
        $associateSetAction->execute($game, $bonusGame, AchievementSetType::Bonus, 'Bonus Title');
        $associateSetAction->execute($game, $specialtyGame, AchievementSetType::Specialty, 'Specialty Title');
        $associateSetAction->execute($game, $exclusiveGame, AchievementSetType::Exclusive, 'Exclusive Title');

        return [
            'game' => $game,
            'bonusGame' => $bonusGame,
            'specialtyGame' => $specialtyGame,
            'exclusiveGame' => $exclusiveGame,
            'achievements' => [
                $achievement1,
                $achievement2,
                $achievement3,
                $achievement4,
                $achievement5,
            ],
            'bonusAchievements' => [
                $achievement6,
                $achievement7,
                $achievement8,
                $achievement9,
            ],
            'specialtyAchievements' => [
                $achievement10,
            ],
            'exclusiveAchievements' => [
                $achievement11,
            ],
            'leaderboards' => [
                $leaderboard1,
                $leaderboard2,
            ],
            'bonusLeaderboards' => [
                $leaderboard3,
            ],
            'gameHash' => AchievementSetsTestHelpers::createGameHash($game),
            'bonusHash' => AchievementSetsTestHelpers::createGameHash($bonusGame),
            'specialtyHash' => AchievementSetsTestHelpers::createGameHash($specialtyGame),
            'exclusiveHash' => AchievementSetsTestHelpers::createGameHash($exclusiveGame),
        ];
    }

    public static function createGameHash(Game $game, GameHashCompatibility $compatibility = GameHashCompatibility::Compatible): GameHash
    {
        return GameHash::create([
            'game_id' => $game->id,
            'system_id' => $game->system_id,
            'compatibility' => $compatibility,
            'md5' => fake()->md5,
            'name' => 'hash_' . $game->id,
            'description' => 'hash_' . $game->id,
        ]);
    }
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::now());

    $this->seedEmulatorUserAgents();
    $this->createConnectUser();
});

describe('Non multi-set', function () {
    test('returns data for a given id', function () {
        $data = AchievementSetsTestHelpers::createGameWithUnpublishedAchievements();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => $game->title,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][6]), // DisplayOrder: 4
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][3]), // DisplayOrder: 5
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][4]), // DisplayOrder: 6 (unpublished)
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][5]), // DisplayOrder: 7
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][7]), // DisplayOrder: 8 (unpublished)
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][8]), // DisplayOrder: 9
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][2]), // DisplayOrder: -1
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][1]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][0]), // DisplayOrder: 2
                            // leaderboards[3] is unpublished - have to specifically ask for those as older clients don't check state
                            // leaderboards[4] is disabled - it should never be returned to any client
                        ],
                    ],
                ],
            ]);
    });

    test('returns data for a given hash', function () {
        $data = AchievementSetsTestHelpers::createGameWithUnpublishedAchievements();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $gameHash = AchievementSetsTestHelpers::createGameHash($game);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null, // request by hash engages multiset code, which returns null for the core set title
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][6]), // DisplayOrder: 4
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][3]), // DisplayOrder: 5
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][4]), // DisplayOrder: 6 (unpublished)
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][5]), // DisplayOrder: 7
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][7]), // DisplayOrder: 8 (unpublished)
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][8]), // DisplayOrder: 9
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][2]), // DisplayOrder: -1
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][1]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][0]), // DisplayOrder: 2
                            // leaderboards[3] is unpublished - have to specifically ask for those as older clients don't check state
                            // leaderboards[4] is disabled - it should never be returned to any client
                        ],
                    ],
                ],
            ]);
    });

    test('only returns published data for a given id', function () {
        $data = AchievementSetsTestHelpers::createGameWithUnpublishedAchievements();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id, 'f' => 3]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => $game->title,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][6]), // DisplayOrder: 4
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][3]), // DisplayOrder: 5
                            // achievements[4] (DisplayOrder: 6) is unpublished - excluded when filtering for published only
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][5]), // DisplayOrder: 7
                            // achievements[7] (DisplayOrder: 8) is unpublished - excluded when filtering for published only
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][8]), // DisplayOrder: 9
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][2]), // DisplayOrder: -1
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][1]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][0]), // DisplayOrder: 2
                            // leaderboards[3] is unpublished - have to specifically ask for those as older clients don't check state
                            // leaderboards[4] is disabled - it should never be returned to any client
                        ],
                    ],
                ],
            ]);
    });

    test('returns empty arrays for game without achievements/leaderboards/rich presence', function () {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'image_icon_asset_path' => '/Images/000051.png',
            'image_title_asset_path' => '/Images/000061.png',
            'image_ingame_asset_path' => '/Images/000071.png',
            'image_box_art_asset_path' => '/Images/000081.png',
            'publisher' => 'WePublishStuff',
            'developer' => 'WeDevelopStuff',
            'genre' => 'Action',
            'released_at' => Carbon::parse('1989-01-15'),
            'released_at_granularity' => 'month',
            'trigger_definition' => '',
        ]);
        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);
        $achievementSet = $game->achievementSets()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => '',
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => $game->title,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('returns empty arrays for game without achievement set', function () {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'image_icon_asset_path' => '/Images/000051.png',
            'image_title_asset_path' => '/Images/000061.png',
            'image_ingame_asset_path' => '/Images/000071.png',
            'image_box_art_asset_path' => '/Images/000081.png',
            'publisher' => 'WePublishStuff',
            'developer' => 'WeDevelopStuff',
            'genre' => 'Action',
            'released_at' => Carbon::parse('1989-01-15'),
            'released_at_granularity' => 'month',
            'trigger_definition' => '',
        ]);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => '',
                'Sets' => [
                    [
                        'AchievementSetId' => 0,
                        'Title' => $game->title,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('achievement with null author should not return null', function () {
        // see https://github.com/libretro/RetroArch/issues/16648
        $data = AchievementSetsTestHelpers::createGameWithUnpublishedAchievements();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();

        $achievement2 = $data['achievements'][2];
        $achievement2->user_id = null;
        $achievement2->save();
        $achievement2PatchData = AchievementSetsTestHelpers::getAchievementPatchData($achievement2);
        $achievement2PatchData['Author'] = '';

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id, 'f' => 3]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => $game->title,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            $achievement2PatchData, // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][6]), // DisplayOrder: 4
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][3]), // DisplayOrder: 5
                            // achievements[4] (DisplayOrder: 6) is unpublished - excluded when filtering for published only
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][5]), // DisplayOrder: 7
                            // achievements[7] (DisplayOrder: 8) is unpublished - excluded when filtering for published only
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][8]), // DisplayOrder: 9
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][2]), // DisplayOrder: -1
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][1]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][0]), // DisplayOrder: 2
                        ],
                    ],
                ],
            ]);
    });

    test('returns error for invalid game id', function () {
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['g' => 999999]))
            ->assertStatus(404)
            ->assertExactJson([
                'Success' => false,
                'Error' => 'Unknown game.',
                'Status' => 404,
                'Code' => 'not_found',
            ]);
    });

    test('returns error for missing parameters', function () {
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets'))
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Error' => 'One or more required parameters is missing.',
                'Status' => 422,
                'Code' => 'missing_parameter',
            ]);
    });
});

describe('Multi-set', function () {
    test('returns core and bonus data for core hash', function () {
        $data = AchievementSetsTestHelpers::createMultiSetGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $bonusGame = $data['bonusGame'];
        $bonusAchievementSet = $bonusGame->achievementSets()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $data['gameHash']->md5]))
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][3]), // DisplayOrder: 5
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][4]), // DisplayOrder: 6 (unpublished)
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][1]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][0]), // DisplayOrder: 2
                        ],
                    ],
                    [
                        'AchievementSetId' => $bonusAchievementSet->id,
                        'Title' => 'Bonus Title',
                        'Type' => 'bonus',
                        'GameId' => $bonusGame->id,
                        'ImageIconUrl' => media_asset($bonusGame->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][1]), // DisplayOrder: 4
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][0]), // DisplayOrder: 7
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][2]), // DisplayOrder: 8 (unpublished)
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][3]), // DisplayOrder: 9
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['bonusLeaderboards'][0]), // DisplayOrder: -1
                        ],
                    ],
                ],
            ]);
    });

    test('returns core and bonus data for bonus hash', function () {
        $data = AchievementSetsTestHelpers::createMultiSetGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $bonusGame = $data['bonusGame'];
        $bonusAchievementSet = $bonusGame->achievementSets()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $data['bonusHash']->md5]))
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][3]), // DisplayOrder: 5
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][4]), // DisplayOrder: 6 (unpublished)
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][1]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][0]), // DisplayOrder: 2
                        ],
                    ],
                    [
                        'AchievementSetId' => $bonusAchievementSet->id,
                        'Title' => 'Bonus Title',
                        'Type' => 'bonus',
                        'GameId' => $bonusGame->id,
                        'ImageIconUrl' => media_asset($bonusGame->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][1]), // DisplayOrder: 4
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][0]), // DisplayOrder: 7
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][2]), // DisplayOrder: 8 (unpublished)
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][3]), // DisplayOrder: 9
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['bonusLeaderboards'][0]), // DisplayOrder: -1
                        ],
                    ],
                ],
            ]);
    });

    test('returns specialty, core and bonus data for specialty hash', function () {
        $data = AchievementSetsTestHelpers::createMultiSetGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $bonusGame = $data['bonusGame'];
        $bonusAchievementSet = $bonusGame->achievementSets()->first();
        $specialtyGame = $data['specialtyGame'];
        $specialtyAchievementSet = $specialtyGame->achievementSets()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $data['specialtyHash']->md5]))
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $specialtyGame->id, // returns rich presence for specialty game
                'RichPresencePatch' => $specialtyGame->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][3]), // DisplayOrder: 5
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][4]), // DisplayOrder: 6 (unpublished)
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][1]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][0]), // DisplayOrder: 2
                        ],
                    ],
                    [
                        'AchievementSetId' => $specialtyAchievementSet->id,
                        'Title' => 'Specialty Title',
                        'Type' => 'specialty',
                        'GameId' => $specialtyGame->id,
                        'ImageIconUrl' => media_asset($specialtyGame->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['specialtyAchievements'][0]), // DisplayOrder: 10
                        ],
                        'Leaderboards' => [],
                    ],
                    [
                        'AchievementSetId' => $bonusAchievementSet->id,
                        'Title' => 'Bonus Title',
                        'Type' => 'bonus',
                        'GameId' => $bonusGame->id,
                        'ImageIconUrl' => media_asset($bonusGame->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][1]), // DisplayOrder: 4
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][0]), // DisplayOrder: 7
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][2]), // DisplayOrder: 8 (unpublished)
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][3]), // DisplayOrder: 9
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['bonusLeaderboards'][0]), // DisplayOrder: -1
                        ],
                    ],
                ],
            ]);
    });

    test('returns only exclusive data for exclusive hash', function () {
        $data = AchievementSetsTestHelpers::createMultiSetGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $exclusiveGame = $data['exclusiveGame'];
        $exclusiveAchievementSet = $exclusiveGame->achievementSets()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $data['exclusiveHash']->md5]))
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $exclusiveGame->id,
                'RichPresencePatch' => $exclusiveGame->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $exclusiveAchievementSet->id,
                        'Title' => 'Exclusive Title',
                        'Type' => 'exclusive',
                        'GameId' => $exclusiveGame->id,
                        'ImageIconUrl' => media_asset($exclusiveGame->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['exclusiveAchievements'][0]), // DisplayOrder: 11
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('returns only core data for core id', function () {
        $data = AchievementSetsTestHelpers::createMultiSetGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $bonusGame = $data['bonusGame'];
        $bonusAchievementSet = $bonusGame->achievementSets()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id]))
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => $game->title,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][3]), // DisplayOrder: 5
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][4]), // DisplayOrder: 6 (unpublished)
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][1]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][0]), // DisplayOrder: 2
                        ],
                    ],
                ],
            ]);
    });

    test('returns only bonus data for bonus id', function () {
        $data = AchievementSetsTestHelpers::createMultiSetGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $bonusGame = $data['bonusGame'];
        $bonusAchievementSet = $bonusGame->achievementSets()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['g' => $bonusGame->id]))
            ->assertExactJson([
                'Success' => true,
                'GameId' => $bonusGame->id,
                'Title' => $bonusGame->title,
                'ImageIconUrl' => media_asset($bonusGame->image_icon_asset_path),
                'ConsoleId' => $bonusGame->system_id,
                'RichPresenceGameId' => $bonusGame->id,
                'RichPresencePatch' => $bonusGame->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $bonusAchievementSet->id,
                        'Title' => $bonusGame->title,
                        'Type' => 'core',
                        'GameId' => $bonusGame->id,
                        'ImageIconUrl' => media_asset($bonusGame->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][1]), // DisplayOrder: 4
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][0]), // DisplayOrder: 7
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][2]), // DisplayOrder: 8 (unpublished)
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][3]), // DisplayOrder: 9
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['bonusLeaderboards'][0]), // DisplayOrder: -1
                        ],
                    ],
                ],
            ]);
    });

    test('returns only core data for core hash if globally opted out of multiset', function () {
        $data = AchievementSetsTestHelpers::createMultiSetGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $bonusGame = $data['bonusGame'];
        $bonusAchievementSet = $bonusGame->achievementSets()->first();

        $this->user->preferences_bitfield |= (1 << UserPreference::Game_OptOutOfAllSubsets);
        $this->user->save();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $data['gameHash']->md5]))
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][3]), // DisplayOrder: 5
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][4]), // DisplayOrder: 6 (unpublished)
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][1]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][0]), // DisplayOrder: 2
                        ],
                    ],
                ],
            ]);
    });

    test('returns only bonus data for bonus hash if globally opted out of multiset', function () {
        $data = AchievementSetsTestHelpers::createMultiSetGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $bonusGame = $data['bonusGame'];
        $bonusAchievementSet = $bonusGame->achievementSets()->first();

        $this->user->preferences_bitfield |= (1 << UserPreference::Game_OptOutOfAllSubsets);
        $this->user->save();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $data['bonusHash']->md5]))
            ->assertExactJson([
                'Success' => true,
                'GameId' => $bonusGame->id,
                'Title' => $bonusGame->title,
                'ImageIconUrl' => media_asset($bonusGame->image_icon_asset_path),
                'ConsoleId' => $bonusGame->system_id,
                'RichPresenceGameId' => $bonusGame->id,
                'RichPresencePatch' => $bonusGame->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $bonusAchievementSet->id,
                        'Title' => 'Bonus Title',
                        'Type' => 'bonus',
                        'GameId' => $bonusGame->id,
                        'ImageIconUrl' => media_asset($bonusGame->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][1]), // DisplayOrder: 4
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][0]), // DisplayOrder: 7
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][2]), // DisplayOrder: 8 (unpublished)
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][3]), // DisplayOrder: 9
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['bonusLeaderboards'][0]), // DisplayOrder: -1
                        ],
                    ],
                ],
            ]);
    });

    test('returns only core data for core hash if opted out of bonus set', function () {
        $data = AchievementSetsTestHelpers::createMultiSetGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $bonusGame = $data['bonusGame'];
        $bonusAchievementSet = $bonusGame->achievementSets()->first();

        $bonusGameAchievementSet = GameAchievementSet::whereGameId($game->id)->whereType(AchievementSetType::Bonus)->first();
        UserGameAchievementSetPreference::factory()->create([
            'user_id' => $this->user->id,
            'game_achievement_set_id' => $bonusGameAchievementSet->id,
            'opted_in' => false,
        ]);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $data['gameHash']->md5]))
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][3]), // DisplayOrder: 5
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][4]), // DisplayOrder: 6 (unpublished)
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][1]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][0]), // DisplayOrder: 2
                        ],
                    ],
                ],
            ]);
    });

    test('returns only bonus data for core hash if opted out of core set', function () {
        $data = AchievementSetsTestHelpers::createMultiSetGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $bonusGame = $data['bonusGame'];
        $bonusAchievementSet = $bonusGame->achievementSets()->first();

        $gameAchievementSet = GameAchievementSet::whereGameId($game->id)->whereType(AchievementSetType::Core)->first();
        UserGameAchievementSetPreference::factory()->create([
            'user_id' => $this->user->id,
            'game_achievement_set_id' => $gameAchievementSet->id,
            'opted_in' => false,
        ]);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $data['gameHash']->md5]))
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $bonusAchievementSet->id,
                        'Title' => 'Bonus Title',
                        'Type' => 'bonus',
                        'GameId' => $bonusGame->id,
                        'ImageIconUrl' => media_asset($bonusGame->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][1]), // DisplayOrder: 4
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][0]), // DisplayOrder: 7
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][2]), // DisplayOrder: 8 (unpublished)
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][3]), // DisplayOrder: 9
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['bonusLeaderboards'][0]), // DisplayOrder: -1
                        ],
                    ],
                ],
            ]);
    });

    test('returns warning for core hash if opted out of core and bonus set', function () {
        $data = AchievementSetsTestHelpers::createMultiSetGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $bonusGame = $data['bonusGame'];
        $bonusAchievementSet = $bonusGame->achievementSets()->first();

        $gameAchievementSet = GameAchievementSet::whereGameId($game->id)->whereType(AchievementSetType::Core)->first();
        UserGameAchievementSetPreference::factory()->create([
            'user_id' => $this->user->id,
            'game_achievement_set_id' => $gameAchievementSet->id,
            'opted_in' => false,
        ]);

        $bonusGameAchievementSet = GameAchievementSet::whereGameId($game->id)->whereType(AchievementSetType::Bonus)->first();
        UserGameAchievementSetPreference::factory()->create([
            'user_id' => $this->user->id,
            'game_achievement_set_id' => $bonusGameAchievementSet->id,
            'opted_in' => false,
        ]);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $data['gameHash']->md5]))
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => $game->title,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getWarningAchievementPatchData('All Sets Opted Out', 'You have opted out of all achievement sets for this game. Visit the game page to change your preferences.'),
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('returns core and bonus data for core hash if globally opted out, but opted in to bonus set', function () {
        $data = AchievementSetsTestHelpers::createMultiSetGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $bonusGame = $data['bonusGame'];
        $bonusAchievementSet = $bonusGame->achievementSets()->first();

        $this->user->preferences_bitfield |= (1 << UserPreference::Game_OptOutOfAllSubsets);
        $this->user->save();

        $bonusGameAchievementSet = GameAchievementSet::whereGameId($game->id)->whereType(AchievementSetType::Bonus)->first();
        UserGameAchievementSetPreference::factory()->create([
            'user_id' => $this->user->id,
            'game_achievement_set_id' => $bonusGameAchievementSet->id,
            'opted_in' => true,
        ]);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $data['gameHash']->md5]))
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][3]), // DisplayOrder: 5
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][4]), // DisplayOrder: 6 (unpublished)
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][1]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][0]), // DisplayOrder: 2
                        ],
                    ],
                    [
                        'AchievementSetId' => $bonusAchievementSet->id,
                        'Title' => 'Bonus Title',
                        'Type' => 'bonus',
                        'GameId' => $bonusGame->id,
                        'ImageIconUrl' => media_asset($bonusGame->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][1]), // DisplayOrder: 4
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][0]), // DisplayOrder: 7
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][2]), // DisplayOrder: 8 (unpublished)
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][3]), // DisplayOrder: 9
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['bonusLeaderboards'][0]), // DisplayOrder: -1
                        ],
                    ],
                ],
            ]);
    });

    test('returns core and bonus data for bonus hash if globally opted out, but opted in to core set', function () {
        $data = AchievementSetsTestHelpers::createMultiSetGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $bonusGame = $data['bonusGame'];
        $bonusAchievementSet = $bonusGame->achievementSets()->first();

        $this->user->preferences_bitfield |= (1 << UserPreference::Game_OptOutOfAllSubsets);
        $this->user->save();

        $gameAchievementSet = GameAchievementSet::whereGameId($game->id)->whereType(AchievementSetType::Core)->first();
        UserGameAchievementSetPreference::factory()->create([
            'user_id' => $this->user->id,
            'game_achievement_set_id' => $gameAchievementSet->id,
            'opted_in' => true,
        ]);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $data['bonusHash']->md5]))
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][3]), // DisplayOrder: 5
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][4]), // DisplayOrder: 6 (unpublished)
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][1]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][0]), // DisplayOrder: 2
                        ],
                    ],
                    [
                        'AchievementSetId' => $bonusAchievementSet->id,
                        'Title' => 'Bonus Title',
                        'Type' => 'bonus',
                        'GameId' => $bonusGame->id,
                        'ImageIconUrl' => media_asset($bonusGame->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][1]), // DisplayOrder: 4
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][0]), // DisplayOrder: 7
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][2]), // DisplayOrder: 8 (unpublished)
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][3]), // DisplayOrder: 9
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['bonusLeaderboards'][0]), // DisplayOrder: -1
                        ],
                    ],
                ],
            ]);
    });

    test('returns core rich presence for specialty hash if specialty game does not have rich presence', function () {
        $data = AchievementSetsTestHelpers::createMultiSetGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $bonusGame = $data['bonusGame'];
        $bonusAchievementSet = $bonusGame->achievementSets()->first();
        $specialtyGame = $data['specialtyGame'];
        $specialtyGame->trigger_definition = '';
        $specialtyGame->save();
        $specialtyAchievementSet = $specialtyGame->achievementSets()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $data['specialtyHash']->md5]))
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][3]), // DisplayOrder: 5
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][4]), // DisplayOrder: 6 (unpublished)
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][1]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['leaderboards'][0]), // DisplayOrder: 2
                        ],
                    ],
                    [
                        'AchievementSetId' => $specialtyAchievementSet->id,
                        'Title' => 'Specialty Title',
                        'Type' => 'specialty',
                        'GameId' => $specialtyGame->id,
                        'ImageIconUrl' => media_asset($specialtyGame->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['specialtyAchievements'][0]), // DisplayOrder: 10
                        ],
                        'Leaderboards' => [],
                    ],
                    [
                        'AchievementSetId' => $bonusAchievementSet->id,
                        'Title' => 'Bonus Title',
                        'Type' => 'bonus',
                        'GameId' => $bonusGame->id,
                        'ImageIconUrl' => media_asset($bonusGame->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][1]), // DisplayOrder: 4
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][0]), // DisplayOrder: 7
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][2]), // DisplayOrder: 8 (unpublished)
                            AchievementSetsTestHelpers::getAchievementPatchData($data['bonusAchievements'][3]), // DisplayOrder: 9
                        ],
                        'Leaderboards' => [
                            AchievementSetsTestHelpers::getLeaderboardPatchData($data['bonusLeaderboards'][0]), // DisplayOrder: -1
                        ],
                    ],
                ],
            ]);
    });

    test('returns no rich presence for specialty hash if specialty game does not have rich presence and globally opted out of multi-set', function () {
        $data = AchievementSetsTestHelpers::createMultiSetGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $bonusGame = $data['bonusGame'];
        $bonusAchievementSet = $bonusGame->achievementSets()->first();
        $specialtyGame = $data['specialtyGame'];
        $specialtyGame->trigger_definition = '';
        $specialtyGame->save();
        $specialtyAchievementSet = $specialtyGame->achievementSets()->first();

        $this->user->preferences_bitfield |= (1 << UserPreference::Game_OptOutOfAllSubsets);
        $this->user->save();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $data['specialtyHash']->md5]))
            ->assertExactJson([
                'Success' => true,
                'GameId' => $specialtyGame->id,
                'Title' => $specialtyGame->title,
                'ImageIconUrl' => media_asset($specialtyGame->image_icon_asset_path),
                'ConsoleId' => $specialtyGame->system_id,
                'RichPresenceGameId' => $specialtyGame->id,
                'RichPresencePatch' => $specialtyGame->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $specialtyAchievementSet->id,
                        'Title' => 'Specialty Title',
                        'Type' => 'specialty',
                        'GameId' => $specialtyGame->id,
                        'ImageIconUrl' => media_asset($specialtyGame->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['specialtyAchievements'][0]), // DisplayOrder: 10
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });
});

describe('Rarity', function () {
    test('returns 100% rarity for a game with no play history', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => $game->title,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0], 100.0, 100.0), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2], 100.0, 100.0), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1], 100.0, 100.0), // DisplayOrder: 3
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('returns adjusted rarity for a game the player has not played before', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();

        $game->players_total = 11;
        $game->players_hardcore = 9; // both rarity calculations should use the non-hardcore player count
        $game->save();

        // rarity calculation = (unlocks + 1) / (num_players) [max:100.0]
        $data['achievements'][0]->unlocks_total = 10;
        $data['achievements'][0]->unlocks_hardcore = 9;
        $data['achievements'][0]->save();

        $data['achievements'][2]->unlocks_total = 7;
        $data['achievements'][2]->unlocks_hardcore = 5;
        $data['achievements'][2]->save();

        $data['achievements'][1]->unlocks_total = 2;
        $data['achievements'][1]->unlocks_hardcore = 0;
        $data['achievements'][1]->save();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => $game->title,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0], 91.67, 83.33), // 11/12=91.67, 10/12=83.33
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2], 66.67, 50.00), //  8/12=66.67,  6/12=50.00
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1], 25.00, 8.33),  //  3/12=25.00,  1/12= 8.33
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('returns unadjusted rarity for a game the player has played before', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();

        $game->players_total = 11;
        $game->players_hardcore = 9; // both rarity calculations should use the non-hardcore player count
        $game->save();

        // rarity calculation = (unlocks) / (num_players) [max:100.0]
        $data['achievements'][0]->unlocks_total = 10;
        $data['achievements'][0]->unlocks_hardcore = 9;
        $data['achievements'][0]->save();

        $data['achievements'][2]->unlocks_total = 7;
        $data['achievements'][2]->unlocks_hardcore = 5;
        $data['achievements'][2]->save();

        $data['achievements'][1]->unlocks_total = 2;
        $data['achievements'][1]->unlocks_hardcore = 0;
        $data['achievements'][1]->save();

        PlayerGame::create([
            'user_id' => $this->user->id,
            'game_id' => $game->id,
        ]);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => $game->title,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0], 100.00, 90.91), // 11/11=100.00, 10/11=90.91
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2], 72.73, 54.55),  //  8/11= 72.73,  6/11=54.55
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1], 27.27, 9.09),   //  3/11= 27.27,  1/11= 9.09
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });
});

const UNKNOWN_CLIENT_WARNING = 'The server does not recognize this client and will not allow hardcore unlocks. Please send a message to RAdmin on the RetroAchievements website for information on how to submit your emulator for hardcore consideration.';

describe('User Agent', function () {
    test('valid user agent does not receive warning', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => $game->title,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('no user agent receives warning', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();

        $this->get($this->apiUrl('achievementsets', ['g' => $game->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => $game->title,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getClientWarningAchievementPatchData(ClientSupportLevel::Unknown),
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                        ],
                        'Leaderboards' => [],
                    ],
                ],
                'Warning' => UNKNOWN_CLIENT_WARNING,
            ]);
    });

    test('unknown user agent receives warning', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentUnknown])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => $game->title,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getClientWarningAchievementPatchData(ClientSupportLevel::Unknown),
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                        ],
                        'Leaderboards' => [],
                    ],
                ],
                'Warning' => UNKNOWN_CLIENT_WARNING,
            ]);
    });

    test('outdated user agent receives warning', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentOutdated])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => $game->title,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getClientWarningAchievementPatchData(ClientSupportLevel::Outdated),
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('unsupported user agent receives warning', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();

        $this->withHeaders(['User-Agent' => $this->userAgentUnsupported])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => $game->title,
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => $game->title,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getClientWarningAchievementPatchData(ClientSupportLevel::Unsupported),
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('blocked user agent receives error', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];

        $this->withHeaders(['User-Agent' => $this->userAgentBlocked])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id]))
            ->assertStatus(403)
            ->assertExactJson([
                'Code' => 'unsupported_client',
                'Status' => 403,
                'Success' => false,
                'Error' => 'This client is not supported.',
            ]);
    });
});

describe('Unsupported Hash', function () {
    test('incompatible hash returns no data', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $gameHash = AchievementSetsTestHelpers::createGameHash($game, GameHashCompatibility::Incompatible);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id + VirtualGameIdService::IncompatibleIdBase,
                'Title' => "Unsupported Game Version ($game->title)",
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                // NOTE: no rich presence information returned for incompatible hash
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id + VirtualGameIdService::IncompatibleIdBase,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getWarningAchievementPatchData(
                                title: 'Unsupported Game Version',
                                description: 'This version of the game is known to not work with the defined achievements. See the Supported Game Files page for this game to find a compatible version.',
                            ),
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('incompatible virtual game id returns no data', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        AchievementSetsTestHelpers::createGameHash($game, GameHashCompatibility::Incompatible);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id + VirtualGameIdService::IncompatibleIdBase]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id + VirtualGameIdService::IncompatibleIdBase,
                'Title' => "Unsupported Game Version ($game->title)",
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                // NOTE: no rich presence information returned for incompatible hash
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id + VirtualGameIdService::IncompatibleIdBase,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getWarningAchievementPatchData(
                                title: 'Unsupported Game Version',
                                description: 'This version of the game is known to not work with the defined achievements. See the Supported Game Files page for this game to find a compatible version.',
                            ),
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('untested hash returns no data', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $gameHash = AchievementSetsTestHelpers::createGameHash($game, GameHashCompatibility::Untested);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id + VirtualGameIdService::UntestedIdBase,
                'Title' => "Unsupported Game Version ($game->title)",
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                // NOTE: no rich presence information returned for incompatible hash
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id + VirtualGameIdService::UntestedIdBase,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getWarningAchievementPatchData(
                                title: 'Unsupported Game Version',
                                description: 'This version of the game has not been tested to see if it works with the defined achievements. See the Supported Game Files page for this game to find a compatible version.',
                            ),
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('untested virtual game id returns no data', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        AchievementSetsTestHelpers::createGameHash($game, GameHashCompatibility::Untested);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id + VirtualGameIdService::UntestedIdBase]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id + VirtualGameIdService::UntestedIdBase,
                'Title' => "Unsupported Game Version ($game->title)",
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                // NOTE: no rich presence information returned for incompatible hash
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id + VirtualGameIdService::UntestedIdBase,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getWarningAchievementPatchData(
                                title: 'Unsupported Game Version',
                                description: 'This version of the game has not been tested to see if it works with the defined achievements. See the Supported Game Files page for this game to find a compatible version.',
                            ),
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('untested hash returns data for compatibility tester', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();

        $gameHash = AchievementSetsTestHelpers::createGameHash($game, GameHashCompatibility::Untested);
        $gameHash->compatibility_tester_id = $this->user->id;
        $gameHash->save();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => "Unsupported Game Version ($game->title)",
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('untested virtual game id returns data for compatibility tester', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();

        $gameHash = AchievementSetsTestHelpers::createGameHash($game, GameHashCompatibility::Untested);
        $gameHash->compatibility_tester_id = $this->user->id;
        $gameHash->save();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id + VirtualGameIdService::UntestedIdBase]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => "Unsupported Game Version ($game->title)",
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('untested hash returns data for QATeam member', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $gameHash = AchievementSetsTestHelpers::createGameHash($game, GameHashCompatibility::Untested);

        $this->seed(RolesTableSeeder::class);
        $this->user->assignRole(Role::QUALITY_ASSURANCE);
        $this->user->save();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => "Unsupported Game Version ($game->title)",
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('untested virtual game id returns data for QATeam member', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $gameHash = AchievementSetsTestHelpers::createGameHash($game, GameHashCompatibility::Untested);

        $this->seed(RolesTableSeeder::class);
        $this->user->assignRole(Role::QUALITY_ASSURANCE);
        $this->user->save();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id + VirtualGameIdService::UntestedIdBase]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => "Unsupported Game Version ($game->title)",
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('untested hash returns data for achievement author', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $gameHash = AchievementSetsTestHelpers::createGameHash($game, GameHashCompatibility::Untested);

        $game['achievements'][1]->loadMissing('developer');
        $this->user = $game['achievements'][1]->developer;
        $this->user->connect_token = Str::random(16);
        $this->user->save();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => "Unsupported Game Version ($game->title)",
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('untested virtual game id returns data for achievement author', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $gameHash = AchievementSetsTestHelpers::createGameHash($game, GameHashCompatibility::Untested);

        $game['achievements'][1]->loadMissing('developer');
        $this->user = $game['achievements'][1]->developer;
        $this->user->connect_token = Str::random(16);
        $this->user->save();

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id + VirtualGameIdService::UntestedIdBase]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id,
                'Title' => "Unsupported Game Version ($game->title)",
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                'RichPresenceGameId' => $game->id,
                'RichPresencePatch' => $game->trigger_definition,
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][0]), // DisplayOrder: 1
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][2]), // DisplayOrder: 2
                            AchievementSetsTestHelpers::getAchievementPatchData($data['achievements'][1]), // DisplayOrder: 3
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('patch required hash returns no data', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        $gameHash = AchievementSetsTestHelpers::createGameHash($game, GameHashCompatibility::PatchRequired);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['m' => $gameHash->md5]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id + VirtualGameIdService::PatchRequiredIdBase,
                'Title' => "Unsupported Game Version ($game->title)",
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                // NOTE: no rich presence information returned for incompatible hash
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id + VirtualGameIdService::PatchRequiredIdBase,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getWarningAchievementPatchData(
                                title: 'Unsupported Game Version',
                                description: 'This version of the game requires a patch to support achievements. See the Supported Game Files page for this game to find a compatible version.',
                            ),
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });

    test('patch required virtual game id returns no data', function () {
        $data = AchievementSetsTestHelpers::createSimpleGame();
        $game = $data['game'];
        $achievementSet = $game->achievementSets()->first();
        AchievementSetsTestHelpers::createGameHash($game, GameHashCompatibility::PatchRequired);

        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('achievementsets', ['g' => $game->id + VirtualGameIdService::PatchRequiredIdBase]))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'GameId' => $game->id + VirtualGameIdService::PatchRequiredIdBase,
                'Title' => "Unsupported Game Version ($game->title)",
                'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                'ConsoleId' => $game->system_id,
                // NOTE: no rich presence information returned for incompatible hash
                'Sets' => [
                    [
                        'AchievementSetId' => $achievementSet->id,
                        'Title' => null,
                        'Type' => 'core',
                        'GameId' => $game->id + VirtualGameIdService::PatchRequiredIdBase,
                        'ImageIconUrl' => media_asset($game->image_icon_asset_path),
                        'Achievements' => [
                            AchievementSetsTestHelpers::getWarningAchievementPatchData(
                                title: 'Unsupported Game Version',
                                description: 'This version of the game requires a patch to support achievements. See the Supported Game Files page for this game to find a compatible version.',
                            ),
                        ],
                        'Leaderboards' => [],
                    ],
                ],
            ]);
    });
});
