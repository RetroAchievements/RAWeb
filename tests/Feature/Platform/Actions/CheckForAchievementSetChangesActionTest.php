<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\Achievement;
use App\Models\AchievementSet;
use App\Platform\Actions\CheckForAchievementSetChangesAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

class CheckForAchievementSetChangesActionTestHelpers
{
    /**
     * Attach a freshly created achievement to a set and return it.
     */
    public static function attachAchievement(AchievementSet $set, int $points, bool $isPromoted, ?string $type = null): Achievement
    {
        $achievement = Achievement::factory()->create([
            'points' => $points,
            'is_promoted' => $isPromoted,
            'type' => $type,
        ]);

        $set->achievements()->attach($achievement->id);

        return $achievement;
    }
}

describe('unversioned', function () {
    test('does not create version if no achievements exist', function () {
        $achievementSet = AchievementSet::factory()->create([
            'achievements_published' => 0,
            'achievements_unpublished' => 0,
        ]);

        (new CheckForAchievementSetChangesAction())->execute($achievementSet);

        $this->assertEquals(0, $achievementSet->versions()->count());
    });

    test('does not create version if only unpublished achievements exist', function () {
        $achievementSet = AchievementSet::factory()->create([
            'achievements_published' => 0,
            'achievements_unpublished' => 8,
        ]);

        (new CheckForAchievementSetChangesAction())->execute($achievementSet);

        $this->assertEquals(0, $achievementSet->versions()->count());
    });

    test('creates first version with a non-null definition if achievements published', function () {
        $achievementSet = AchievementSet::factory()->create([
            'achievements_published' => 12,
            'achievements_unpublished' => 0,
            'players_total' => 0,
            'players_hardcore' => 0,
            'points_total' => 60,
        ]);
        for ($i = 0; $i < 12; $i++) {
            CheckForAchievementSetChangesActionTestHelpers::attachAchievement($achievementSet, points: 5, isPromoted: true);
        }

        (new CheckForAchievementSetChangesAction())->execute($achievementSet);

        $this->assertEquals(1, $achievementSet->versions()->count());
        $version = $achievementSet->versions()->first();
        $this->assertEquals(1, $version->version);
        $this->assertNull($version->parent_id);
        $this->assertEquals(0, $version->players_total);
        $this->assertEquals(0, $version->players_hardcore);

        $this->assertNotNull($version->definition);
        $this->assertCount(12, $version->definition['achievements']);
    });
});

describe('versioned', function () {
    test('updates existing version if no changes to published achievements', function () {
        $achievementSet = AchievementSet::factory()->create([
            'achievements_published' => 12,
            'points_total' => 60,
        ]);
        for ($i = 0; $i < 12; $i++) {
            CheckForAchievementSetChangesActionTestHelpers::attachAchievement($achievementSet, points: 5, isPromoted: true);
        }
        (new CheckForAchievementSetChangesAction())->execute($achievementSet);
        $firstVersion = $achievementSet->versions()->first();

        $achievementSet->players_total = 5;
        $achievementSet->players_hardcore = 3;

        (new CheckForAchievementSetChangesAction())->execute($achievementSet);

        $this->assertEquals(1, $achievementSet->versions()->count());
        $firstVersion->refresh();
        $this->assertEquals(5, $firstVersion->players_total);
        $this->assertEquals(3, $firstVersion->players_hardcore);
        $this->assertEquals(60, $firstVersion->points_total);
    });

    test('updates existing version if unpublished achievement added', function () {
        $achievementSet = AchievementSet::factory()->create([
            'achievements_published' => 12,
            'points_total' => 60,
        ]);
        for ($i = 0; $i < 12; $i++) {
            CheckForAchievementSetChangesActionTestHelpers::attachAchievement($achievementSet, points: 5, isPromoted: true);
        }
        (new CheckForAchievementSetChangesAction())->execute($achievementSet);
        $firstVersion = $achievementSet->versions()->first();
        $this->assertCount(12, $firstVersion->definition['achievements']);

        CheckForAchievementSetChangesActionTestHelpers::attachAchievement($achievementSet, points: 25, isPromoted: false);

        (new CheckForAchievementSetChangesAction())->execute($achievementSet);

        $this->assertEquals(1, $achievementSet->versions()->count());
        $firstVersion->refresh();
        $this->assertEquals(1, $firstVersion->achievements_unpublished); // derived from the snapshot
        $this->assertEquals(12, $firstVersion->achievements_published);
        $this->assertCount(13, $firstVersion->definition['achievements']);
        $unpublished = collect($firstVersion->definition['achievements'])->firstWhere('is_promoted', false);
        $this->assertNotNull($unpublished);
        $this->assertEquals(25, $unpublished['points']);
    });

    test('creates new version on an equal-count, equal-points demote+promote swap', function () {
        $achievementSet = AchievementSet::factory()->create([
            'achievements_published' => 12,
            'points_total' => 60,
        ]);
        $promoted = [];
        for ($i = 0; $i < 12; $i++) {
            $promoted[] = CheckForAchievementSetChangesActionTestHelpers::attachAchievement($achievementSet, points: 5, isPromoted: true);
        }
        $draft = CheckForAchievementSetChangesActionTestHelpers::attachAchievement($achievementSet, points: 5, isPromoted: false);
        (new CheckForAchievementSetChangesAction())->execute($achievementSet);
        $this->assertEquals(1, $achievementSet->versions()->count());

        Achievement::withoutEvents(function () use ($promoted, $draft) {
            $promoted[0]->update(['is_promoted' => false]);
            $draft->update(['is_promoted' => true]);
        });

        (new CheckForAchievementSetChangesAction())->execute($achievementSet);

        $this->assertEquals(2, $achievementSet->versions()->count());
        $newVersion = $achievementSet->versions()->orderByDesc('version')->first();
        $this->assertEquals(2, $newVersion->version);
        $this->assertEquals($achievementSet->versions()->orderBy('version')->first()->id, $newVersion->parent_id);
        $this->assertEquals(12, $newVersion->achievements_published);
        $this->assertEquals(60, $newVersion->points_total);
    });

    test('does not overwrite the previous version definition when a published change creates a new version', function () {
        $achievementSet = AchievementSet::factory()->create([
            'achievements_published' => 12,
            'points_total' => 60,
        ]);
        for ($i = 0; $i < 12; $i++) {
            CheckForAchievementSetChangesActionTestHelpers::attachAchievement($achievementSet, points: 5, isPromoted: true);
        }
        (new CheckForAchievementSetChangesAction())->execute($achievementSet);
        $firstVersion = $achievementSet->versions()->first();
        $this->assertCount(12, $firstVersion->definition['achievements']);

        CheckForAchievementSetChangesActionTestHelpers::attachAchievement($achievementSet, points: 5, isPromoted: true);

        (new CheckForAchievementSetChangesAction())->execute($achievementSet);

        $this->assertEquals(2, $achievementSet->versions()->count());

        $firstVersion->refresh();
        $this->assertCount(12, $firstVersion->definition['achievements']);

        $newVersion = $achievementSet->versions()->orderByDesc('version')->first();
        $this->assertEquals(13, $newVersion->achievements_published);
        $this->assertCount(13, $newVersion->definition['achievements']);
    });

    test('creates new version if total points change', function () {
        $achievementSet = AchievementSet::factory()->create([
            'achievements_published' => 12,
            'points_total' => 60,
        ]);
        $promoted = [];
        for ($i = 0; $i < 12; $i++) {
            $promoted[] = CheckForAchievementSetChangesActionTestHelpers::attachAchievement($achievementSet, points: 5, isPromoted: true);
        }
        (new CheckForAchievementSetChangesAction())->execute($achievementSet);
        $firstVersion = $achievementSet->versions()->first();

        $achievementSet->players_total = 5;
        $achievementSet->players_hardcore = 3;
        Achievement::withoutEvents(fn () => $promoted[0]->update(['points' => 1])); // 60 -> 56

        (new CheckForAchievementSetChangesAction())->execute($achievementSet);

        $this->assertEquals(2, $achievementSet->versions()->count());

        $firstVersion->refresh();
        $this->assertEquals(5, $firstVersion->players_total);
        $this->assertEquals(3, $firstVersion->players_hardcore);
        $this->assertEquals(60, $firstVersion->points_total);

        $newVersion = $achievementSet->versions()->orderByDesc('version')->first();
        $this->assertEquals(2, $newVersion->version);
        $this->assertEquals($firstVersion->id, $newVersion->parent_id);
        $this->assertEquals(12, $newVersion->achievements_published);
        $this->assertEquals(56, $newVersion->points_total);
    });

    test('creates new version if entire set demoted', function () {
        $achievementSet = AchievementSet::factory()->create([
            'achievements_published' => 12,
            'points_total' => 60,
        ]);
        $promoted = [];
        for ($i = 0; $i < 12; $i++) {
            $promoted[] = CheckForAchievementSetChangesActionTestHelpers::attachAchievement($achievementSet, points: 5, isPromoted: true);
        }
        (new CheckForAchievementSetChangesAction())->execute($achievementSet);
        $firstVersion = $achievementSet->versions()->first();

        Achievement::withoutEvents(function () use ($promoted) {
            foreach ($promoted as $achievement) {
                $achievement->update(['is_promoted' => false]);
            }
        });

        (new CheckForAchievementSetChangesAction())->execute($achievementSet);

        $this->assertEquals(2, $achievementSet->versions()->count());
        $newVersion = $achievementSet->versions()->orderByDesc('version')->first();
        $this->assertEquals(2, $newVersion->version);
        $this->assertEquals($firstVersion->id, $newVersion->parent_id);
        $this->assertEquals(0, $newVersion->achievements_published);
        $this->assertEquals(0, $newVersion->points_total);
    });
});
