<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Actions;

use App\Community\Actions\BuildAchievementChecklistAction;
use App\Models\Achievement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class BuildAchievementChecklistActionTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testExecute(): void
    {
        // populate 10 achievements across three games
        $game1 = $this->seedGame(achievements: 3);
        $game2 = $this->seedGame(achievements: 4);
        $game3 = $this->seedGame(achievements: 3);
        $achievement1 = $game1->achievements->get(0);
        $achievement2 = $game1->achievements->get(1);
        $achievement3 = $game1->achievements->get(2);
        $achievement4 = $game2->achievements->get(0);
        $achievement5 = $game2->achievements->get(1);
        $achievement6 = $game2->achievements->get(2);
        $achievement7 = $game2->achievements->get(3);
        $achievement8 = $game3->achievements->get(0);
        $achievement9 = $game3->achievements->get(1);
        $achievement10 = $game3->achievements->get(2);

        // populate 3 users. user1 will have unlocked nothing.
        // user2 will have unlocked even achievements. (achievement 6 only in softcore, achievement 8 promoted from softcore)
        // user3 will have unlocked IDs multiple of 3.
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $time1 = Carbon::create(2024, 2, 6, 14, 15, 16);
        $time2 = Carbon::create(2024, 3, 11, 5, 58, 52);
        $time3 = Carbon::create(2024, 4, 9, 3, 43, 7);
        $time4 = Carbon::create(2024, 5, 16, 13, 26, 28);
        $time5 = Carbon::create(2024, 5, 17, 19, 17, 37);
        $time6 = Carbon::create(2024, 6, 28, 22, 5, 34);

        $this->addHardcoreUnlock($user2, $achievement2, $time3);
        $this->addHardcoreUnlock($user2, $achievement4, $time2);
        $this->addSoftcoreUnlock($user2, $achievement6, $time1);
        $this->addSoftcoreUnlock($user2, $achievement8, $time4);
        $this->addHardcoreUnlock($user2, $achievement8, $time5);
        $this->addHardcoreUnlock($user2, $achievement10, $time6);

        $this->addHardcoreUnlock($user3, $achievement3, $time2);
        $this->addHardcoreUnlock($user3, $achievement6, $time4);
        $this->addHardcoreUnlock($user3, $achievement9, $time6);

        // empty list
        $result = (new BuildAchievementChecklistAction())->execute("", $user2);
        $this->assertEquals([], $result);

        // one achievement, unearned
        $result = (new BuildAchievementChecklistAction())->execute("4", $user1);
        $this->assertAchievementGroups([
            [
                'header' => '',
                'achievements' => [
                    $this->wrapAchievement($achievement4),
                ],
            ],
        ], $result);

        // unheadered CSV
        $result = (new BuildAchievementChecklistAction())->execute("4,5,6", $user2);
        $this->assertAchievementGroups([
            [
                'header' => '',
                'achievements' => [
                    $this->wrapAchievement($achievement4, $time2),
                    $this->wrapAchievement($achievement5),
                    $this->wrapAchievement($achievement6, null, $time1),
                ],
            ],
        ], $result);

        // headered CSV
        $result = (new BuildAchievementChecklistAction())->execute("Some Header:1,3,5", $user3);
        $this->assertAchievementGroups([
            [
                'header' => 'Some Header',
                'achievements' => [
                    $this->wrapAchievement($achievement1),
                    $this->wrapAchievement($achievement3, $time2),
                    $this->wrapAchievement($achievement5),
                ],
            ],
        ], $result);

        // multiple groups, unheadered
        $result = (new BuildAchievementChecklistAction())->execute("1,2|4|7,8,9", $user2);
        $this->assertAchievementGroups([
            [
                'header' => '',
                'achievements' => [
                    $this->wrapAchievement($achievement1),
                    $this->wrapAchievement($achievement2, $time3),
                ],
            ],
            [
                'header' => '',
                'achievements' => [
                    $this->wrapAchievement($achievement4, $time2),
                ],
            ],
            [
                'header' => '',
                'achievements' => [
                    $this->wrapAchievement($achievement7),
                    $this->wrapAchievement($achievement8, $time5, $time4),
                    $this->wrapAchievement($achievement9),
                ],
            ],
        ], $result);

        // multiple groups, headered
        $result = (new BuildAchievementChecklistAction())->execute("First:1|Second:3,4|Third:8,9,10", $user3);
        $this->assertAchievementGroups([
            [
                'header' => 'First',
                'achievements' => [
                    $this->wrapAchievement($achievement1),
                ],
            ],
            [
                'header' => 'Second',
                'achievements' => [
                    $this->wrapAchievement($achievement3, $time2),
                    $this->wrapAchievement($achievement4),
                ],
            ],
            [
                'header' => 'Third',
                'achievements' => [
                    $this->wrapAchievement($achievement8),
                    $this->wrapAchievement($achievement9, $time6),
                    $this->wrapAchievement($achievement10),
                ],
            ],
        ], $result);

        // multiple groups, headered (alternate user)
        $result = (new BuildAchievementChecklistAction())->execute("First:1|Second:3,4|Third:8,9,10", $user2);
        $this->assertAchievementGroups([
            [
                'header' => 'First',
                'achievements' => [
                    $this->wrapAchievement($achievement1),
                ],
            ],
            [
                'header' => 'Second',
                'achievements' => [
                    $this->wrapAchievement($achievement3),
                    $this->wrapAchievement($achievement4, $time2),
                ],
            ],
            [
                'header' => 'Third',
                'achievements' => [
                    $this->wrapAchievement($achievement8, $time5, $time4),
                    $this->wrapAchievement($achievement9),
                    $this->wrapAchievement($achievement10, $time6),
                ],
            ],
        ], $result);

        // empty groups ignored
        $result = (new BuildAchievementChecklistAction())->execute("|First:1||Third:8,9|", $user3);
        $this->assertAchievementGroups([
            [
                'header' => 'First',
                'achievements' => [
                    $this->wrapAchievement($achievement1),
                ],
            ],
            [
                'header' => 'Third',
                'achievements' => [
                    $this->wrapAchievement($achievement8),
                    $this->wrapAchievement($achievement9, $time6),
                ],
            ],
        ], $result);

        // unknown achievements ignored
        $result = (new BuildAchievementChecklistAction())->execute("1,15,3|Other:8,27,9|", $user2);
        $this->assertAchievementGroups([
            [
                'header' => '',
                'achievements' => [
                    $this->wrapAchievement($achievement1),
                    $this->wrapAchievement($achievement3),
                ],
            ],
            [
                'header' => 'Other',
                'achievements' => [
                    $this->wrapAchievement($achievement8, $time5, $time4),
                    $this->wrapAchievement($achievement9),
                ],
            ],
        ], $result);
    }

    private function assertAchievementGroups(array $expected, array $groups): void
    {
        $converted = [];
        foreach ($groups as $group) {
            $converted[] = $group->toArray();
        }

        $this->assertEquals($expected, $converted);
    }

    private function wrapAchievement(Achievement $achievement, ?Carbon $hardcoreUnlock = null, ?Carbon $softcoreUnlock = null): array
    {
        $achievement->loadMissing('game');

        return [
            'id' => $achievement->ID,
            'title' => $achievement->Title,
            'description' => $achievement->Description,
            'points' => $achievement->Points,
            'badgeUnlockedUrl' => $achievement->badgeUnlockedUrl,
            'badgeLockedUrl' => $achievement->badgeLockedUrl,
            'unlockedAt' => $softcoreUnlock ? $softcoreUnlock->format('c') : ($hardcoreUnlock ? $hardcoreUnlock->format('c') : null),
            'unlockedHardcoreAt' => $hardcoreUnlock ? $hardcoreUnlock->format('c') : null,
            'game' => [
                'id' => $achievement->game->id,
                'title' => $achievement->game->title,
                'badgeUrl' => $achievement->game->badgeUrl,
            ],
        ];
    }
}
