<?php

declare(strict_types=1);

namespace Tests\Feature\Community;

use App\Community\Actions\AddGameToListAction;
use App\Community\Actions\RemoveGameFromListAction;
use App\Community\Enums\UserGameListType;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Platform\Enums\UnlockMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class UserGameListTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerBadges;

    public function testSetRequestLimitNewUser(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['points_hardcore' => 0, 'points' => 0]);

        $requestInfo = UserGameListEntry::getUserSetRequestsInformation($user);

        $this->assertEquals($requestInfo, [
            'total' => 0,
            'pointsForNext' => 1250,
        ]);
    }

    public function testSetRequestLimitFromAge(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['points_hardcore' => 0, 'points' => 0,
            'created_at' => Carbon::now()->subDays(370),
        ]);

        $requestInfo = UserGameListEntry::getUserSetRequestsInformation($user);

        $this->assertEquals($requestInfo, [
            'total' => 1,
            'pointsForNext' => 1250,
        ]);
    }

    public function testSetRequestLimitFromAwards(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['points_hardcore' => 0, 'points' => 0]);

        /** @var System $system */
        $system = System::factory()->create(['id' => System::Events]);

        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);

        $this->addMasteryBadge($user, $game, UnlockMode::Hardcore);

        $requestInfo = UserGameListEntry::getUserSetRequestsInformation($user);

        $this->assertEquals($requestInfo, [
            'total' => 1,
            'pointsForNext' => 1250,
        ]);
    }

    public function testSetRequestLimitFromPoints(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['points_hardcore' => 123456, 'points' => 0]);

        $requestInfo = UserGameListEntry::getUserSetRequestsInformation($user);

        $this->assertEquals($requestInfo, [
            'total' => 25,
            'pointsForNext' => 6544, // 130000 - 123456
        ]);
    }

    public function testSetRequestLimitFromManyPoints(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['points_hardcore' => 12345678, 'points' => 0]);

        $requestInfo = UserGameListEntry::getUserSetRequestsInformation($user);

        $this->assertEquals($requestInfo, [
            'total' => 1247,
            'pointsForNext' => 4322, // 12350000 - 12345678
        ]);
    }

    public function testSetRequestLimitFromSoftcorePoints(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['points_hardcore' => 23456, 'points' => 1111]);

        $requestInfo = UserGameListEntry::getUserSetRequestsInformation($user);

        $this->assertEquals($requestInfo, [
            'total' => 7, // 1250, 2500, 5000, 7500, 10000, 15000, 20000
            'pointsForNext' => 433, // 25000 - 23456 - 1111
        ]);
    }

    public function testSetRequestLimitFromManySoftcorePoints(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['points_hardcore' => 1234, 'points' => 11111]);

        $requestInfo = UserGameListEntry::getUserSetRequestsInformation($user);

        $this->assertEquals($requestInfo, [
            'total' => 5,
            'pointsForNext' => 2655, // 15000 - 1234 - 11111
        ]);
    }

    public function testSetRequestAddAndRemove(): void
    {
        // Freeze time
        Carbon::setTestNow(Carbon::now()->startOfSecond());
        $now = Carbon::now()->toISOString();

        /** @var User $user */
        $user = User::factory()->create(['points_hardcore' => 10000]);
        /** @var Game $game1 */
        $game1 = Game::factory()->create();
        /** @var Game $game2 */
        $game2 = Game::factory()->create();
        /** @var Game $game3 */
        $game3 = Game::factory()->create();

        $action = new AddGameToListAction();
        $userGameListEntry1 = $action->execute($user, $game1, UserGameListType::AchievementSetRequest);
        $userGameListEntry2 = $action->execute($user, $game2, UserGameListType::AchievementSetRequest);
        $userGameListEntry3 = $action->execute($user, $game3, UserGameListType::AchievementSetRequest);
        $this->assertInstanceOf(UserGameListEntry::class, $userGameListEntry1);
        $this->assertInstanceOf(UserGameListEntry::class, $userGameListEntry2);
        $this->assertInstanceOf(UserGameListEntry::class, $userGameListEntry3);

        $entries = $user->gameListEntries(UserGameListType::AchievementSetRequest)->get();
        $this->assertCount(3, $entries);
        $this->assertEquals($game1->id, $entries[0]->game_id);
        $this->assertEquals($game2->id, $entries[1]->game_id);
        $this->assertEquals($game3->id, $entries[2]->game_id);
        $this->assertEquals(UserGameListType::AchievementSetRequest, $entries[0]->type);
        $this->assertEquals(UserGameListType::AchievementSetRequest, $entries[1]->type);
        $this->assertEquals(UserGameListType::AchievementSetRequest, $entries[2]->type);

        $deleteAction = new RemoveGameFromListAction();
        $this->assertTrue($deleteAction->execute($user, $game2, UserGameListType::AchievementSetRequest));

        $entries = $user->gameListEntries(UserGameListType::AchievementSetRequest)->get();
        $this->assertCount(2, $entries);
        $this->assertEquals($game1->id, $entries[0]->game_id);
        $this->assertEquals($game3->id, $entries[1]->game_id);
        $this->assertEquals(UserGameListType::AchievementSetRequest, $entries[0]->type);
        $this->assertEquals(UserGameListType::AchievementSetRequest, $entries[1]->type);

        // no longer present, delete should fail
        $this->assertFalse($deleteAction->execute($user, $game2, UserGameListType::AchievementSetRequest));

        // re-add. should appear at end TODO not appearing at the end
        $userGameListEntry4 = $action->execute($user, $game2, UserGameListType::AchievementSetRequest);
        $this->assertInstanceOf(UserGameListEntry::class, $userGameListEntry4);

        $entries = $user->gameListEntries(UserGameListType::AchievementSetRequest)->get();
        $this->assertCount(3, $entries);
        $this->assertEquals($game1->id, $entries[0]->game_id);
        $this->assertEquals($game2->id, $entries[1]->game_id);
        $this->assertEquals($game3->id, $entries[2]->game_id);
        $this->assertEquals(UserGameListType::AchievementSetRequest, $entries[0]->type);
        $this->assertEquals(UserGameListType::AchievementSetRequest, $entries[1]->type);
        $this->assertEquals(UserGameListType::AchievementSetRequest, $entries[2]->type);
    }

    public function testSetRequestAddDuplicate(): void
    {
        // Freeze time
        Carbon::setTestNow(Carbon::now()->startOfSecond());
        $now = Carbon::now()->toISOString();

        /** @var User $user */
        $user = User::factory()->create(['points_hardcore' => 10000]);
        /** @var Game $game1 */
        $game1 = Game::factory()->create();
        /** @var Game $game2 */
        $game2 = Game::factory()->create();

        $action = new AddGameToListAction();
        $userGameListEntry1 = $action->execute($user, $game1, UserGameListType::AchievementSetRequest);
        $userGameListEntry2 = $action->execute($user, $game2, UserGameListType::AchievementSetRequest);
        $this->assertInstanceOf(UserGameListEntry::class, $userGameListEntry1);
        $this->assertInstanceOf(UserGameListEntry::class, $userGameListEntry2);
        $this->assertNull($action->execute($user, $game1, UserGameListType::AchievementSetRequest));
        $this->assertNull($action->execute($user, $game2, UserGameListType::AchievementSetRequest));

        $entries = $user->gameListEntries(UserGameListType::AchievementSetRequest)->get();
        $this->assertCount(2, $entries);
        $this->assertEquals($game1->id, $entries[0]->game_id);
        $this->assertEquals($game2->id, $entries[1]->game_id);
        $this->assertEquals(UserGameListType::AchievementSetRequest, $entries[0]->type);
        $this->assertEquals(UserGameListType::AchievementSetRequest, $entries[1]->type);
    }

    public function testSetRequestAddAtLimit(): void
    {
        // Freeze time
        Carbon::setTestNow(Carbon::now()->startOfSecond());
        $now = Carbon::now()->toISOString();

        /** @var User $user */
        $user = User::factory()->create(['points_hardcore' => 2345, 'points' => 0]);
        /** @var Game $game1 */
        $game1 = Game::factory()->create();
        /** @var Game $game2 */
        $game2 = Game::factory()->create();

        // 2345 points should only grant one request
        $action = new AddGameToListAction();
        $userGameListEntry1 = $action->execute($user, $game1, UserGameListType::AchievementSetRequest);
        $this->assertInstanceOf(UserGameListEntry::class, $userGameListEntry1);
        $this->assertNull($action->execute($user, $game2, UserGameListType::AchievementSetRequest));

        $entries = $user->gameListEntries(UserGameListType::AchievementSetRequest)->get();
        $this->assertCount(1, $entries);
        $this->assertEquals($game1->id, $entries[0]->game_id);
        $this->assertEquals(UserGameListType::AchievementSetRequest, $entries[0]->type);
    }

    public function testSetRequestScopeWithoutAchievements(): void
    {
        // Freeze time
        Carbon::setTestNow(Carbon::now()->startOfSecond());
        $now = Carbon::now()->toISOString();

        /** @var User $user */
        $user = User::factory()->create(['points_hardcore' => 10000]);
        /** @var Game $game1 */
        $game1 = Game::factory()->create();
        /** @var Game $game2 */
        $game2 = Game::factory()->create();
        Achievement::factory()->promoted()->create(['game_id' => $game2->id]);
        /** @var Game $game3 */
        $game3 = Game::factory()->create();

        $action = new AddGameToListAction();
        $userGameListEntry1 = $action->execute($user, $game1, UserGameListType::AchievementSetRequest);
        $userGameListEntry2 = $action->execute($user, $game2, UserGameListType::AchievementSetRequest);
        $userGameListEntry3 = $action->execute($user, $game3, UserGameListType::AchievementSetRequest);
        $this->assertInstanceOf(UserGameListEntry::class, $userGameListEntry1);
        $this->assertInstanceOf(UserGameListEntry::class, $userGameListEntry2);
        $this->assertInstanceOf(UserGameListEntry::class, $userGameListEntry3);

        $entries = $user->gameListEntries(UserGameListType::AchievementSetRequest)->get();
        $this->assertCount(3, $entries);
        $this->assertEquals($game1->id, $entries[0]->game_id);
        $this->assertEquals($game2->id, $entries[1]->game_id);
        $this->assertEquals($game3->id, $entries[2]->game_id);
        $this->assertEquals(UserGameListType::AchievementSetRequest, $entries[0]->type);
        $this->assertEquals(UserGameListType::AchievementSetRequest, $entries[1]->type);
        $this->assertEquals(UserGameListType::AchievementSetRequest, $entries[2]->type);

        $entries = $user->gameListEntries(UserGameListType::AchievementSetRequest)->withoutAchievements()->get();
        $this->assertCount(2, $entries);
        $this->assertEquals($game1->id, $entries[0]->game_id);
        $this->assertEquals($game3->id, $entries[1]->game_id);
        $this->assertEquals(UserGameListType::AchievementSetRequest, $entries[0]->type);
        $this->assertEquals(UserGameListType::AchievementSetRequest, $entries[1]->type);
    }
}
