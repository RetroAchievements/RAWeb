<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\GameRelease;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\GameReleaseRegion;
use App\Platform\Enums\ReleasedAtGranularity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class GameExtendedTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;
    use TestsPlayerAchievements;

    public function testGetGameExtendedUnknownGame(): void
    {
        $this->get($this->apiUrl('GetGameExtended', ['i' => 999999]))
            ->assertSuccessful()
            ->assertExactJson([]);
    }

    public function testGetGame(): void
    {
        $releasedAt = Carbon::parse('1992-05-16');

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'forum_topic_id' => 1234,
            'image_icon_asset_path' => '/Images/000011.png',
            'image_title_asset_path' => '/Images/000021.png',
            'image_ingame_asset_path' => '/Images/000031.png',
            'image_box_art_asset_path' => '/Images/000041.png',
            'publisher' => 'WePublishStuff',
            'developer' => 'WeDevelopStuff',
            'genre' => 'Action',
        ]);

        GameRelease::factory()->create([
            'game_id' => $game->id,
            'title' => $game->title,
            'released_at' => $releasedAt,
            'released_at_granularity' => ReleasedAtGranularity::Day,
            'region' => GameReleaseRegion::NorthAmerica,
            'is_canonical_game_title' => true,
        ]);

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'image_name' => '12345', 'order_column' => 1]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'image_name' => '23456', 'order_column' => 3]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'image_name' => '34567', 'order_column' => 2]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->create(['game_id' => $game->id]); // unofficial

        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var User $user3 */
        $user3 = User::factory()->create();
        /** @var User $user4 */
        $user4 = User::factory()->create();

        // $this->user has all achievements unlocked in hardcore
        $this->addHardcoreUnlock($this->user, $achievement1);
        $this->addHardcoreUnlock($this->user, $achievement2);
        $this->addHardcoreUnlock($this->user, $achievement3);

        // user2 has all achievements unlocked in softcore
        $this->addSoftcoreUnlock($user2, $achievement1);
        $this->addSoftcoreUnlock($user2, $achievement2);
        $this->addSoftcoreUnlock($user2, $achievement3);

        // user3 has all achievements unlocked, mix of hardcore and softcore
        $this->addHardcoreUnlock($user3, $achievement1);
        $this->addSoftcoreUnlock($user3, $achievement2);
        $this->addSoftcoreUnlock($user3, $achievement3);

        // user4 only has two achievements unlocked
        $this->addHardcoreUnlock($user4, $achievement1);
        $this->addHardcoreUnlock($user4, $achievement2);

        $this->get($this->apiUrl('GetGameExtended', ['i' => $game->id]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $game->id,
                'Title' => $game->title,
                'ConsoleID' => $system->id,
                'ConsoleName' => $system->name,
                'ForumTopicID' => $game->forum_topic_id,
                'Flags' => null,
                'ImageIcon' => $game->image_icon_asset_path,
                'ImageTitle' => $game->image_title_asset_path,
                'ImageIngame' => $game->image_ingame_asset_path,
                'ImageBoxArt' => $game->image_box_art_asset_path,
                'Publisher' => $game->publisher,
                'Developer' => $game->developer,
                'Genre' => $game->genre,
                'Released' => $releasedAt->format('Y-m-d'),
                'ReleasedAtGranularity' => 'day',
                'NumAchievements' => 3,
                'NumDistinctPlayers' => 4,
                'NumDistinctPlayersCasual' => 4,
                'NumDistinctPlayersHardcore' => 4,
                'Achievements' => [
                    $achievement1->id => [
                        'ID' => $achievement1->id,
                        'Title' => $achievement1->title,
                        'Description' => $achievement1->description,
                        'Points' => $achievement1->points,
                        'BadgeName' => $achievement1->image_name,
                        'DisplayOrder' => $achievement1->order_column,
                        'Author' => $achievement1->developer->username,
                        'AuthorULID' => $achievement1->developer->ulid,
                        'DateCreated' => $achievement1->created_at->__toString(),
                        'DateModified' => $achievement1->modified_at->__toString(),
                        'NumAwarded' => 4,
                        'NumAwardedHardcore' => 3,
                    ],
                    $achievement3->id => [
                        'ID' => $achievement3->id,
                        'Title' => $achievement3->title,
                        'Description' => $achievement3->description,
                        'Points' => $achievement3->points,
                        'BadgeName' => $achievement3->image_name,
                        'DisplayOrder' => $achievement3->order_column,
                        'Author' => $achievement3->developer->username,
                        'AuthorULID' => $achievement3->developer->ulid,
                        'DateCreated' => $achievement3->created_at->__toString(),
                        'DateModified' => $achievement3->modified_at->__toString(),
                        'NumAwarded' => 3,
                        'NumAwardedHardcore' => 1,
                    ],
                    $achievement2->id => [
                        'ID' => $achievement2->id,
                        'Title' => $achievement2->title,
                        'Description' => $achievement2->description,
                        'Points' => $achievement2->points,
                        'BadgeName' => $achievement2->image_name,
                        'DisplayOrder' => $achievement2->order_column,
                        'Author' => $achievement2->developer->username,
                        'AuthorULID' => $achievement2->developer->ulid,
                        'DateCreated' => $achievement2->created_at->__toString(),
                        'DateModified' => $achievement2->modified_at->__toString(),
                        'NumAwarded' => 4,
                        'NumAwardedHardcore' => 2,
                    ],
                ],
            ]);

        $this->get($this->apiUrl('GetGameExtended', ['i' => $game->id, 'f' => Achievement::FLAG_UNPROMOTED]))
            ->assertSuccessful()
            ->assertJson([
                'Achievements' => [
                    $achievement4->id => [
                        'ID' => $achievement4->id,
                        'Title' => $achievement4->title,
                        'Description' => $achievement4->description,
                        'Points' => $achievement4->points,
                        'BadgeName' => $achievement4->image_name,
                        'DisplayOrder' => $achievement4->order_column,
                        'Author' => $achievement4->developer->username,
                        'AuthorULID' => $achievement4->developer->ulid,
                        'DateCreated' => $achievement4->created_at->__toString(),
                        'DateModified' => $achievement4->modified_at->__toString(),
                        'NumAwarded' => 0,
                        'NumAwardedHardcore' => 0,
                    ],
                ],
            ]);
    }

    public function testGetGameClaimed(): void
    {
        $releasedAt = Carbon::parse('1992-05-16');

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'forum_topic_id' => 1234,
            'image_icon_asset_path' => '/Images/000011.png',
            'image_title_asset_path' => '/Images/000021.png',
            'image_ingame_asset_path' => '/Images/000031.png',
            'image_box_art_asset_path' => '/Images/000041.png',
            'publisher' => 'WePublishStuff',
            'developer' => 'WeDevelopStuff',
            'genre' => 'Action',
        ]);

        GameRelease::factory()->create([
            'game_id' => $game->id,
            'title' => $game->title,
            'released_at' => $releasedAt,
            'released_at_granularity' => ReleasedAtGranularity::Day,
            'region' => GameReleaseRegion::NorthAmerica,
            'is_canonical_game_title' => true,
        ]);

        /** @var User $user2 */
        $user2 = User::factory()->create(['Permissions' => Permissions::Developer]);

        /** @var AchievementSetClaim $claim */
        $claim = AchievementSetClaim::factory()->create([
            'user_id' => $user2->id,
            'game_id' => $game->id,
        ]);

        $this->get($this->apiUrl('GetGameExtended', ['i' => $game->id]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $game->id,
                'Title' => $game->title,
                'ConsoleID' => $system->id,
                'ConsoleName' => $system->name,
                'ForumTopicID' => $game->forum_topic_id,
                'Flags' => null,
                'ImageIcon' => $game->image_icon_asset_path,
                'ImageTitle' => $game->image_title_asset_path,
                'ImageIngame' => $game->image_ingame_asset_path,
                'ImageBoxArt' => $game->image_box_art_asset_path,
                'Publisher' => $game->publisher,
                'Developer' => $game->developer,
                'Genre' => $game->genre,
                'Released' => $releasedAt->format('Y-m-d'),
                'ReleasedAtGranularity' => 'day',
                'Achievements' => [],
                'Claims' => [
                    [
                        'User' => $claim->user->username,
                        'ULID' => $claim->user->ulid,
                        'SetType' => $claim->set_type->toLegacyInteger(),
                        'ClaimType' => $claim->claim_type->toLegacyInteger(),
                        'Created' => $claim->created_at->__toString(),
                        'Expiration' => $claim->finished_at->__toString(),
                    ],
                ],
            ]);
    }

    public function testItValidatesTheFlagParameter(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        // Assert
        $this->get($this->apiUrl('GetGameExtended', ['i' => $game->id, 'f' => 2]))
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Invalid flag parameter. Valid values are 3 (published) or 5 (unpublished).',
                'errors' => [
                    'f' => [
                        'Invalid flag parameter. Valid values are 3 (published) or 5 (unpublished).',
                    ],
                ],
            ]);
    }
}
