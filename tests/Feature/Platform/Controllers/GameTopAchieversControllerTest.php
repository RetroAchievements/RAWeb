<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Controllers;

use App\Community\Enums\AwardType;
use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\UnrankedUser;
use App\Models\User;
use App\Platform\Actions\UpdateGameMetricsAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GameTopAchieversControllerTest extends TestCase
{
    use RefreshDatabase;

    private function addMastery(User $user, Game $game, Carbon $when): void
    {
        PlayerGame::factory()->create(['user_id' => $user->id,
            'achievements_unlocked_hardcore' => $game->achievements_published,
            'points_hardcore' => $game->points_total,
            'beaten_hardcore_at' => $when->clone()->subMinutes(5),
            'completed_hardcore_at' => $when,
            'last_unlock_hardcore_at' => $when,
        ]);
    }

    private function addBeaten(User $user, Game $game, Carbon $when, int $missingPoints): void
    {
        PlayerGame::factory()->create(['user_id' => $user->id,
            'achievements_unlocked_hardcore' => $game->achievements_published - 1,
            'points_hardcore' => $game->points_total - $missingPoints,
            'beaten_hardcore_at' => $when,
            'last_unlock_hardcore_at' => $when,
        ]);
    }

    private function addNotBeaten(User $user, Game $game, Carbon $when, int $missingPoints): void
    {
        PlayerGame::factory()->create(['user_id' => $user->id,
            'achievements_unlocked_hardcore' => $game->achievements_published - 1,
            'points_hardcore' => $game->points_total - $missingPoints,
            'last_unlock_hardcore_at' => $when,
        ]);
    }

    private function addCompleted(User $user, Game $game, Carbon $when): void
    {
        PlayerGame::factory()->create(['user_id' => $user->id,
            'achievements_unlocked' => $game->achievements_published,
            'points' => $game->points_total,
            'beaten_at' => $when->clone()->subMinutes(5),
            'completed_at' => $when,
            'last_unlock_at' => $when,
        ]);
    }

    public function testIndexReturnsCorrectInertiaResponse(): void
    {
        $game = $this->seedGame(achievements: 6);

        (new UpdateGameMetricsAction())->execute($game);

        $date1 = Carbon::parse('2024-01-23 05:55');
        $date2 = Carbon::parse('2024-02-11 15:36');
        $date3 = Carbon::parse('2024-03-28 15:02');
        $date4 = Carbon::parse('2024-04-09 03:14');
        $date5 = Carbon::parse('2024-04-10 04:43');
        $date6 = Carbon::parse('2024-10-03 16:08');
        $date7 = Carbon::parse('2024-10-06 21:43');
        $date8 = Carbon::parse('2024-11-22 09:55');

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        $user4 = User::factory()->create();
        $user5 = User::factory()->create();
        $user6 = User::factory()->create(['Untracked' => true, 'unranked_at' => $date2]);
        $user7 = User::factory()->create();
        $user8 = User::factory()->create();

        UnrankedUser::create([
            'user_id' => $user6->id,
        ]);

        // user1 mastery
        $this->addMastery($user1, $game, $date4);
        // user2 beaten, missing 2 points
        $this->addBeaten($user2, $game, $date5, 8);
        // user3 not beaten, missing 8 points
        $this->addNotBeaten($user3, $game, $date3, 6);
        // user4 completion (softcore mastery) - will not be returned
        $this->addCompleted($user4, $game, $date7);
        // user5 mastery
        $this->addMastery($user5, $game, $date2);
        // user6 mastery (untracked) - will not be returned
        $this->addMastery($user6, $game, $date1);
        // user7 beaten, missing 4 points
        $this->addBeaten($user7, $game, $date8, 4);
        // user8 not beaten, missing 8 points
        $this->addNotBeaten($user8, $game, $date6, 6);

        $game->players_hardcore = 6; // user1+user2+user3+user5+user7+user8
        $game->save();

        // expected:
        //  1 $user5 $date2 mastered
        //  1 $user1 $date4 mastered
        //  3 $user7 $date8 beaten -4
        //  4 $user3 $date3 -6
        //  4 $user8 $date6 -6
        //  6 $user2 $date5 beaten -8

        $response = $this->get(route('game.top-achievers.index', $game->id));
        $response->assertInertia(fn (Assert $page) => $page
            ->where('game.id', $game->id)
            ->where('paginatedUsers.total', $game->players_hardcore)

            ->where('paginatedUsers.items.0.rank', 1)
            ->where('paginatedUsers.items.0.user.displayName', $user5->display_name)
            ->where('paginatedUsers.items.0.score', $game->points_total)
            ->where('paginatedUsers.items.0.badge.awardType', AwardType::Mastery)
            ->where('paginatedUsers.items.0.badge.awardDate', $date2->toIso8601String())

            ->where('paginatedUsers.items.1.rank', 1)
            ->where('paginatedUsers.items.1.user.displayName', $user1->display_name)
            ->where('paginatedUsers.items.1.score', $game->points_total)
            ->where('paginatedUsers.items.1.badge.awardType', AwardType::Mastery)
            ->where('paginatedUsers.items.1.badge.awardDate', $date4->toIso8601String())

            ->where('paginatedUsers.items.2.rank', 3)
            ->where('paginatedUsers.items.2.user.displayName', $user7->display_name)
            ->where('paginatedUsers.items.2.score', $game->points_total - 4)
            ->where('paginatedUsers.items.2.badge.awardType', AwardType::GameBeaten)
            ->where('paginatedUsers.items.2.badge.awardDate', $date8->toIso8601String())

            ->where('paginatedUsers.items.3.rank', 4)
            ->where('paginatedUsers.items.3.user.displayName', $user3->display_name)
            ->where('paginatedUsers.items.3.score', $game->points_total - 6)
            ->where('paginatedUsers.items.3.badge', null)

            ->where('paginatedUsers.items.4.rank', 4)
            ->where('paginatedUsers.items.4.user.displayName', $user8->display_name)
            ->where('paginatedUsers.items.4.score', $game->points_total - 6)
            ->where('paginatedUsers.items.4.badge', null)

            ->where('paginatedUsers.items.5.rank', 6)
            ->where('paginatedUsers.items.5.user.displayName', $user2->display_name)
            ->where('paginatedUsers.items.5.score', $game->points_total - 8)
            ->where('paginatedUsers.items.5.badge.awardType', AwardType::GameBeaten)
            ->where('paginatedUsers.items.5.badge.awardDate', $date5->toIso8601String())
        );
    }
}
