<?php

declare(strict_types=1);

namespace Tests\Feature\Community;

use App\Community\Actions\AddGameToListAction;
use App\Community\Actions\RemoveGameFromListAction;
use App\Community\Enums\UserGameListType;
use App\Community\Models\UserGameListEntry;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerBadge;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserGameListTest extends TestCase
{
    use RefreshDatabase;

    public function testSetRequestLimitNewUser(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['RAPoints' => 0, 'RASoftcorePoints' => 0]);

        $requestInfo = UserGameListEntry::getUserSetRequestsInformation($user);

        $this->assertEquals($requestInfo, [
            'total' => 0,
            'pointsForNext' => 1250,
        ]);
    }

    public function testSetRequestLimitFromAge(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['RAPoints' => 0, 'RASoftcorePoints' => 0,
            'Created' => Carbon::now()->subDays(370),
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
        $user = User::factory()->create(['RAPoints' => 0, 'RASoftcorePoints' => 0]);

        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => 101]);

        /** @var Achievement $publishedAchievements */
        $publishedAchievements = Achievement::factory()->published()->count(10)->create(['GameID' => $game->ID]);

        /** @var PlayerBadge $badge */
        $badge = new PlayerBadge([
            'User' => $user->User,
            'AwardType' => 1,
            'AwardData' => $game->ID,
            'AwardDataExtra' => 1,
            'AwardDate' => Carbon::now(),
        ]);
        $user->playerBadges()->save($badge);

        $requestInfo = UserGameListEntry::getUserSetRequestsInformation($user);

        $this->assertEquals($requestInfo, [
            'total' => 1,
            'pointsForNext' => 1250,
        ]);
    }

    public function testSetRequestLimitFromPoints(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['RAPoints' => 123456, 'RASoftcorePoints' => 0]);

        $requestInfo = UserGameListEntry::getUserSetRequestsInformation($user);

        $this->assertEquals($requestInfo, [
            'total' => 25,
            'pointsForNext' => 6544, // 130000 - 123456
        ]);
    }

    public function testSetRequestLimitFromManyPoints(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['RAPoints' => 12345678, 'RASoftcorePoints' => 0]);

        $requestInfo = UserGameListEntry::getUserSetRequestsInformation($user);

        $this->assertEquals($requestInfo, [
            'total' => 1247,
            'pointsForNext' => 4322, // 12350000 - 12345678
        ]);
    }

    public function testSetRequestLimitFromSoftcorePoints(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['RAPoints' => 23456, 'RASoftcorePoints' => 1111]);

        $requestInfo = UserGameListEntry::getUserSetRequestsInformation($user);

        $this->assertEquals($requestInfo, [
            'total' => 7, // 1250, 2500, 5000, 7500, 10000, 15000, 20000
            'pointsForNext' => 433, // 25000 - 23456 - 1111
        ]);
    }

    public function testSetRequestLimitFromManySoftcorePoints(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['RAPoints' => 1234, 'RASoftcorePoints' => 11111]);

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
        $user = User::factory()->create(['RAPoints' => 10000]);
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

        $this->assertEquals($user->gameList(UserGameListType::AchievementSetRequest)->get()->toArray(), [
            ['id' => $userGameListEntry1->id, 'User' => $user->User, 'GameID' => $game1->ID, 'Updated' => $now, 'user_id' => $user->ID, 'type' => UserGameListType::AchievementSetRequest, 'created_at' => $now],
            ['id' => $userGameListEntry2->id, 'User' => $user->User, 'GameID' => $game2->ID, 'Updated' => $now, 'user_id' => $user->ID, 'type' => UserGameListType::AchievementSetRequest, 'created_at' => $now],
            ['id' => $userGameListEntry3->id, 'User' => $user->User, 'GameID' => $game3->ID, 'Updated' => $now, 'user_id' => $user->ID, 'type' => UserGameListType::AchievementSetRequest, 'created_at' => $now],
        ]);

        $deleteAction = new RemoveGameFromListAction();
        $this->assertTrue($deleteAction->execute($user, $game2, UserGameListType::AchievementSetRequest));

        $this->assertEquals($user->gameList(UserGameListType::AchievementSetRequest)->get()->toArray(), [
            ['id' => $userGameListEntry1->id, 'User' => $user->User, 'GameID' => $game1->ID, 'Updated' => $now, 'user_id' => $user->ID, 'type' => UserGameListType::AchievementSetRequest, 'created_at' => $now],
            ['id' => $userGameListEntry3->id, 'User' => $user->User, 'GameID' => $game3->ID, 'Updated' => $now, 'user_id' => $user->ID, 'type' => UserGameListType::AchievementSetRequest, 'created_at' => $now],
        ]);

        // no longer present, delete should fail
        $this->assertFalse($deleteAction->execute($user, $game2, UserGameListType::AchievementSetRequest));

        // re-add. should appear at end TODO not appearing at the end
        $userGameListEntry4 = $action->execute($user, $game2, UserGameListType::AchievementSetRequest);
        $this->assertInstanceOf(UserGameListEntry::class, $userGameListEntry4);
        $this->assertEquals($user->gameList(UserGameListType::AchievementSetRequest)->get()->toArray(), [
            ['id' => $userGameListEntry1->id, 'User' => $user->User, 'GameID' => $game1->ID, 'Updated' => $now, 'user_id' => $user->ID, 'type' => UserGameListType::AchievementSetRequest, 'created_at' => $now],
            ['id' => $userGameListEntry4->id, 'User' => $user->User, 'GameID' => $game2->ID, 'Updated' => $now, 'user_id' => $user->ID, 'type' => UserGameListType::AchievementSetRequest, 'created_at' => $now],
            ['id' => $userGameListEntry3->id, 'User' => $user->User, 'GameID' => $game3->ID, 'Updated' => $now, 'user_id' => $user->ID, 'type' => UserGameListType::AchievementSetRequest, 'created_at' => $now],
        ]);
    }

    public function testSetRequestAddDuplicate(): void
    {
        // Freeze time
        Carbon::setTestNow(Carbon::now()->startOfSecond());
        $now = Carbon::now()->toISOString();

        /** @var User $user */
        $user = User::factory()->create(['RAPoints' => 10000]);
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

        $this->assertEquals($user->gameList(UserGameListType::AchievementSetRequest)->get()->toArray(), [
            ['id' => $userGameListEntry1->id, 'User' => $user->User, 'GameID' => $game1->ID, 'Updated' => $now, 'user_id' => $user->ID, 'type' => UserGameListType::AchievementSetRequest, 'created_at' => $now],
            ['id' => $userGameListEntry2->id, 'User' => $user->User, 'GameID' => $game2->ID, 'Updated' => $now, 'user_id' => $user->ID, 'type' => UserGameListType::AchievementSetRequest, 'created_at' => $now],
        ]);
    }

    public function testSetRequestAddAtLimit(): void
    {
        // Freeze time
        Carbon::setTestNow(Carbon::now()->startOfSecond());
        $now = Carbon::now()->toISOString();

        /** @var User $user */
        $user = User::factory()->create(['RAPoints' => 2345, 'RASoftcorePoints' => 0]);
        /** @var Game $game1 */
        $game1 = Game::factory()->create();
        /** @var Game $game2 */
        $game2 = Game::factory()->create();

        // 2345 points should only grant one request
        $action = new AddGameToListAction();
        $userGameListEntry1 = $action->execute($user, $game1, UserGameListType::AchievementSetRequest);
        $this->assertInstanceOf(UserGameListEntry::class, $userGameListEntry1);
        $this->assertNull($action->execute($user, $game2, UserGameListType::AchievementSetRequest));

        $this->assertEquals($user->gameList(UserGameListType::AchievementSetRequest)->get()->toArray(), [
            ['id' => $userGameListEntry1->id, 'User' => $user->User, 'GameID' => $game1->ID, 'Updated' => $now, 'user_id' => $user->ID, 'type' => UserGameListType::AchievementSetRequest, 'created_at' => $now],
        ]);
    }

    public function testSetRequestScopeWithoutAchievements(): void
    {
        // Freeze time
        Carbon::setTestNow(Carbon::now()->startOfSecond());
        $now = Carbon::now()->toISOString();

        /** @var User $user */
        $user = User::factory()->create(['RAPoints' => 10000]);
        /** @var Game $game1 */
        $game1 = Game::factory()->create();
        /** @var Game $game2 */
        $game2 = Game::factory()->create();
        Achievement::factory()->published()->create(['GameID' => $game2->ID]);
        /** @var Game $game3 */
        $game3 = Game::factory()->create();

        $action = new AddGameToListAction();
        $userGameListEntry1 = $action->execute($user, $game1, UserGameListType::AchievementSetRequest);
        $userGameListEntry2 = $action->execute($user, $game2, UserGameListType::AchievementSetRequest);
        $userGameListEntry3 = $action->execute($user, $game3, UserGameListType::AchievementSetRequest);
        $this->assertInstanceOf(UserGameListEntry::class, $userGameListEntry1);
        $this->assertInstanceOf(UserGameListEntry::class, $userGameListEntry2);
        $this->assertInstanceOf(UserGameListEntry::class, $userGameListEntry3);

        $this->assertEquals($user->gameList(UserGameListType::AchievementSetRequest)->get()->toArray(), [
            ['id' => $userGameListEntry1->id, 'User' => $user->User, 'GameID' => $game1->ID, 'Updated' => $now, 'user_id' => $user->ID, 'type' => UserGameListType::AchievementSetRequest, 'created_at' => $now],
            ['id' => $userGameListEntry2->id, 'User' => $user->User, 'GameID' => $game2->ID, 'Updated' => $now, 'user_id' => $user->ID, 'type' => UserGameListType::AchievementSetRequest, 'created_at' => $now],
            ['id' => $userGameListEntry3->id, 'User' => $user->User, 'GameID' => $game3->ID, 'Updated' => $now, 'user_id' => $user->ID, 'type' => UserGameListType::AchievementSetRequest, 'created_at' => $now],
        ]);

        $this->assertEquals($user->gameList(UserGameListType::AchievementSetRequest)->withoutAchievements()->get()->toArray(), [
            ['id' => $userGameListEntry1->id, 'User' => $user->User, 'GameID' => $game1->ID, 'Updated' => $now, 'user_id' => $user->ID, 'type' => UserGameListType::AchievementSetRequest, 'created_at' => $now],
            ['id' => $userGameListEntry3->id, 'User' => $user->User, 'GameID' => $game3->ID, 'Updated' => $now, 'user_id' => $user->ID, 'type' => UserGameListType::AchievementSetRequest, 'created_at' => $now],
        ]);
    }
}
