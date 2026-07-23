<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Components;

use App\Community\Enums\Rank;
use App\Community\Enums\RankType;
use App\Enums\Permissions;
use App\Models\PlayerGlobalRanking;
use App\Models\PlayerGlobalRankingTotal;
use App\Models\Role;
use App\Models\User;
use App\Platform\Enums\GlobalRankingMode;
use App\Platform\Enums\GlobalRankingWindow;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCardTest extends TestCase
{
    use RefreshDatabase;

    public function testItRendersRegisteredUserData(): void
    {
        $user = User::factory()->create([
            'username' => 'mockUser',
            'motto' => 'mockMotto',
            'points_hardcore' => 5000,
            'points' => 50,
            'points_weighted' => 6500,
            'unranked_at' => null,
            'Permissions' => Permissions::Registered,
            'created_at' => '2023-07-01 00:00:00',
            'last_activity_at' => '2023-07-10 00:00:00',
        ]);
        $this->createRanking($user, RankType::Hardcore, GlobalRankingMode::Hardcore);

        $view = $this->blade('<x-user-card user="mockUser" />');

        $view->assertSeeText("mockUser"); // Name
        $view->assertSeeText("mockMotto"); // Motto
        $view->assertSeeText("5,000"); // Points
        $view->assertSeeText("(6,500)"); // RetroPoints
        $view->assertSeeText("#1"); // Site Rank
        $view->assertDontSeeText(Permissions::toString(Permissions::Registered));
        $view->assertDontSeeText("Casual Points");
    }

    public function testItDisplaysUserRoleWhenAppropriate(): void
    {
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create([
            'username' => 'mockUser',
            'motto' => 'mockMotto',
            'points_hardcore' => 5000,
            'points' => 50,
            'points_weighted' => 6500,
            'unranked_at' => null,
            'created_at' => '2023-07-01 00:00:00',
            'last_activity_at' => '2023-07-10 00:00:00',
        ]);
        $user->assignRole(Role::DEVELOPER_JUNIOR);
        $this->createRanking($user, RankType::Hardcore, GlobalRankingMode::Hardcore);

        $view = $this->blade('<x-user-card user="mockUser" />');

        $view->assertSeeText('Junior Developer');
    }

    public function testItDoesntDisplayIfUserIsBanned(): void
    {
        $user = User::factory()->create([
            'username' => 'mockUser',
            'motto' => 'mockMotto',
            'points_hardcore' => 5000,
            'points' => 50,
            'points_weighted' => 6500,
            'unranked_at' => null,
            'Permissions' => Permissions::Banned,
            'created_at' => '2023-07-01 00:00:00',
            'last_activity_at' => '2023-07-10 00:00:00',
        ]);
        $this->createRanking($user, RankType::Hardcore, GlobalRankingMode::Hardcore);

        $view = $this->blade('<x-user-card user="mockUser" />');

        $view->assertDontSeeText(Permissions::toString(Permissions::Banned));
    }

    public function testItShowsCasualStandingsWhenAppropriate(): void
    {
        $user = User::factory()->create([
            'username' => 'mockUser',
            'motto' => 'mockMotto',
            'points_hardcore' => 50,
            'points' => 5000,
            'points_weighted' => 6500,
            'unranked_at' => null,
            'Permissions' => Permissions::Banned,
            'created_at' => '2023-07-01 00:00:00',
            'last_activity_at' => '2023-07-10 00:00:00',
        ]);
        $this->createRanking($user, RankType::Casual, GlobalRankingMode::Casual);

        $view = $this->blade('<x-user-card user="mockUser" />');

        $view->assertSeeText("Casual Points");
        $view->assertSeeText("5,000");
    }

    public function testItSaysIfUserIsUntracked(): void
    {
        User::factory()->create([
            'username' => 'mockUser',
            'motto' => 'mockMotto',
            'points_hardcore' => 5000,
            'points' => 50,
            'points_weighted' => 6500,
            'unranked_at' => now(),
            'Permissions' => Permissions::Banned,
            'created_at' => '2023-07-01 00:00:00',
            'last_activity_at' => '2023-07-10 00:00:00',
        ]);

        $view = $this->blade('<x-user-card user="mockUser" />');

        $view->assertSeeText("Untracked");
    }

    public function testItSaysIfUserDoesntMeetRankMinimumPointsThreshold(): void
    {
        User::factory()->create([
            'username' => 'mockUser',
            'motto' => 'mockMotto',
            'points_hardcore' => 1,
            'points' => 1,
            'points_weighted' => 1,
            'unranked_at' => null,
            'Permissions' => Permissions::Banned,
            'created_at' => '2023-07-01 00:00:00',
            'last_activity_at' => '2023-07-10 00:00:00',
        ]);

        $view = $this->blade('<x-user-card user="mockUser" />');

        $view->assertSeeText("Needs at least " . Rank::MIN_POINTS . " points");
    }

    public function testItSaysWhenUserRankIsUpdating(): void
    {
        User::factory()->create([
            'username' => 'mockUser',
            'points_hardcore' => Rank::MIN_POINTS,
            'points' => 0,
            'unranked_at' => null,
        ]);

        $view = $this->blade('<x-user-card user="mockUser" />');

        $view->assertSeeText('Will appear shortly.');
        $view->assertDontSeeText('Needs at least ' . Rank::MIN_POINTS . ' points');
    }

    private function createRanking(User $user, RankType $rankType, GlobalRankingMode $mode): void
    {
        PlayerGlobalRanking::factory()->create([
            'user_id' => $user->id,
            'window' => GlobalRankingWindow::AllTime,
            'mode' => $mode,
            'rank_number' => 1,
        ]);
        PlayerGlobalRankingTotal::query()->create([
            'rank_type' => $rankType,
            'total' => 1,
        ]);
    }
}
