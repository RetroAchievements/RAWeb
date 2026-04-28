<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\AchievementSet;
use App\Models\AchievementSetVersion;
use App\Platform\Actions\CheckForAchievementSetChangesAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

class CheckForAchievementSetChangesActionTestHelpers
{
    public static function createInitialVersion(): AchievementSetVersion
    {
        $achievementSet = AchievementSet::factory()->create([
            'achievements_published' => 12,
            'achievements_unpublished' => 0,
            'players_total' => 0,
            'players_hardcore' => 0,
            'points_total' => 75,
            'points_weighted' => 177,
        ]);

        return $achievementSet->versions()->create([
            'version' => 1,
            'achievements_published' => 12,
            'achievements_unpublished' => 0,
            'players_total' => 0,
            'players_hardcore' => 0,
            'points_total' => 75,
            'points_weighted' => 177,
        ]);
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

    test('creates first version if achievements published', function () {
        $achievementSet = AchievementSet::factory()->create([
            'achievements_published' => 12,
            'achievements_unpublished' => 0,
            'players_total' => 0,
            'players_hardcore' => 0,
            'points_total' => 75,
            'points_weighted' => 177,
        ]);

        (new CheckForAchievementSetChangesAction())->execute($achievementSet);

        $this->assertEquals(1, $achievementSet->versions()->count());
        $version = $achievementSet->versions()->first();
        $this->assertEquals(1, $version->version);
        $this->assertNull($version->parent_id);
        $this->assertEquals(12, $version->achievements_published);
        $this->assertEquals(0, $version->achievements_unpublished);
        $this->assertEquals(0, $version->players_total);
        $this->assertEquals(0, $version->players_hardcore);
        $this->assertEquals(75, $version->points_total);
        $this->assertEquals(177, $version->points_weighted);
    });
});

describe('versioned', function () {
    test('updates existing version if no changes to published achievements', function () {
        $firstVersion = CheckForAchievementSetChangesActionTestHelpers::createInitialVersion();
        $achievementSet = $firstVersion->achievementSet;

        $achievementSet->players_total = 5;
        $achievementSet->players_hardcore = 3;
        $achievementSet->points_weighted = 172;

        (new CheckForAchievementSetChangesAction())->execute($achievementSet);

        $this->assertEquals(1, $achievementSet->versions()->count());
        $firstVersion->refresh();
        $this->assertEquals(1, $firstVersion->version);
        $this->assertNull($firstVersion->parent_id);
        $this->assertEquals(12, $firstVersion->achievements_published);
        $this->assertEquals(0, $firstVersion->achievements_unpublished);
        $this->assertEquals(5, $firstVersion->players_total);
        $this->assertEquals(3, $firstVersion->players_hardcore);
        $this->assertEquals(75, $firstVersion->points_total);
        $this->assertEquals(172, $firstVersion->points_weighted);
    });

    test('updates existing version if unpublished achievement added', function () {
        $firstVersion = CheckForAchievementSetChangesActionTestHelpers::createInitialVersion();
        $achievementSet = $firstVersion->achievementSet;

        $achievementSet->players_total = 5;
        $achievementSet->players_hardcore = 3;
        $achievementSet->points_weighted = 172;
        $achievementSet->achievements_unpublished = 1;

        (new CheckForAchievementSetChangesAction())->execute($achievementSet);

        $this->assertEquals(1, $achievementSet->versions()->count());
        $firstVersion->refresh();
        $this->assertEquals(1, $firstVersion->version);
        $this->assertNull($firstVersion->parent_id);
        $this->assertEquals(12, $firstVersion->achievements_published);
        $this->assertEquals(1, $firstVersion->achievements_unpublished);
        $this->assertEquals(5, $firstVersion->players_total);
        $this->assertEquals(3, $firstVersion->players_hardcore);
        $this->assertEquals(75, $firstVersion->points_total);
        $this->assertEquals(172, $firstVersion->points_weighted);
    });

    test('creates new version if points change', function () {
        $firstVersion = CheckForAchievementSetChangesActionTestHelpers::createInitialVersion();
        $achievementSet = $firstVersion->achievementSet;

        $achievementSet->players_total = 5;
        $achievementSet->players_hardcore = 3;
        $achievementSet->achievements_published = 12;
        $achievementSet->points_total = 70;
        $achievementSet->points_weighted = 167;

        (new CheckForAchievementSetChangesAction())->execute($achievementSet);

        $this->assertEquals(2, $achievementSet->versions()->count());

        // player stats on first version should update
        $firstVersion->refresh();
        $this->assertEquals(1, $firstVersion->version);
        $this->assertNull($firstVersion->parent_id);
        $this->assertEquals(12, $firstVersion->achievements_published);
        $this->assertEquals(0, $firstVersion->achievements_unpublished);
        $this->assertEquals(5, $firstVersion->players_total);
        $this->assertEquals(3, $firstVersion->players_hardcore);
        $this->assertEquals(75, $firstVersion->points_total);
        $this->assertEquals(177, $firstVersion->points_weighted); // weighted points not updated on old version

        // new version should inclue points changes
        $newVersion = $achievementSet->versions()->skip(1)->first();
        $this->assertEquals(2, $newVersion->version);
        $this->assertEquals($firstVersion->id, $newVersion->parent_id);
        $this->assertEquals(12, $newVersion->achievements_published);
        $this->assertEquals(0, $newVersion->achievements_unpublished);
        $this->assertEquals(5, $newVersion->players_total);
        $this->assertEquals(3, $newVersion->players_hardcore);
        $this->assertEquals(70, $newVersion->points_total);
        $this->assertEquals(167, $newVersion->points_weighted);
    });

    test('creates new version if achievement demoted', function () {
        $firstVersion = CheckForAchievementSetChangesActionTestHelpers::createInitialVersion();
        $achievementSet = $firstVersion->achievementSet;

        $achievementSet->players_total = 5;
        $achievementSet->players_hardcore = 3;
        $achievementSet->achievements_published = 11;
        $achievementSet->points_total = 70;
        $achievementSet->points_weighted = 167;

        (new CheckForAchievementSetChangesAction())->execute($achievementSet);

        $this->assertEquals(2, $achievementSet->versions()->count());

        // player stats on first version should update
        $firstVersion->refresh();
        $this->assertEquals(1, $firstVersion->version);
        $this->assertNull($firstVersion->parent_id);
        $this->assertEquals(12, $firstVersion->achievements_published);
        $this->assertEquals(0, $firstVersion->achievements_unpublished);
        $this->assertEquals(5, $firstVersion->players_total);
        $this->assertEquals(3, $firstVersion->players_hardcore);
        $this->assertEquals(75, $firstVersion->points_total);
        $this->assertEquals(177, $firstVersion->points_weighted); // weighted points not updated on old version

        // new version should inclue points changes
        $newVersion = $achievementSet->versions()->skip(1)->first();
        $this->assertEquals(2, $newVersion->version);
        $this->assertEquals($firstVersion->id, $newVersion->parent_id);
        $this->assertEquals(11, $newVersion->achievements_published);
        $this->assertEquals(0, $newVersion->achievements_unpublished);
        $this->assertEquals(5, $newVersion->players_total);
        $this->assertEquals(3, $newVersion->players_hardcore);
        $this->assertEquals(70, $newVersion->points_total);
        $this->assertEquals(167, $newVersion->points_weighted);
    });

    test('creates new version if achievement added', function () {
        $firstVersion = CheckForAchievementSetChangesActionTestHelpers::createInitialVersion();
        $achievementSet = $firstVersion->achievementSet;

        $achievementSet->players_total = 5;
        $achievementSet->players_hardcore = 3;
        $achievementSet->achievements_published = 13;
        $achievementSet->points_total = 80;
        $achievementSet->points_weighted = 183;

        (new CheckForAchievementSetChangesAction())->execute($achievementSet);

        $this->assertEquals(2, $achievementSet->versions()->count());

        // player stats on first version should update
        $firstVersion->refresh();
        $this->assertEquals(1, $firstVersion->version);
        $this->assertNull($firstVersion->parent_id);
        $this->assertEquals(12, $firstVersion->achievements_published);
        $this->assertEquals(0, $firstVersion->achievements_unpublished);
        $this->assertEquals(5, $firstVersion->players_total);
        $this->assertEquals(3, $firstVersion->players_hardcore);
        $this->assertEquals(75, $firstVersion->points_total);
        $this->assertEquals(177, $firstVersion->points_weighted); // weighted points not updated on old version

        // new version should inclue points changes
        $newVersion = $achievementSet->versions()->skip(1)->first();
        $this->assertEquals(2, $newVersion->version);
        $this->assertEquals($firstVersion->id, $newVersion->parent_id);
        $this->assertEquals(13, $newVersion->achievements_published);
        $this->assertEquals(0, $newVersion->achievements_unpublished);
        $this->assertEquals(5, $newVersion->players_total);
        $this->assertEquals(3, $newVersion->players_hardcore);
        $this->assertEquals(80, $newVersion->points_total);
        $this->assertEquals(183, $newVersion->points_weighted);
    });

    test('creates new version if entire set demoted', function () {
        $firstVersion = CheckForAchievementSetChangesActionTestHelpers::createInitialVersion();
        $achievementSet = $firstVersion->achievementSet;

        $achievementSet->players_total = 5;
        $achievementSet->players_hardcore = 3;
        $achievementSet->achievements_published = 0;
        $achievementSet->points_total = 0;
        $achievementSet->points_weighted = 0;

        (new CheckForAchievementSetChangesAction())->execute($achievementSet);

        $this->assertEquals(2, $achievementSet->versions()->count());

        // player stats on first version should update
        $firstVersion->refresh();
        $this->assertEquals(1, $firstVersion->version);
        $this->assertNull($firstVersion->parent_id);
        $this->assertEquals(12, $firstVersion->achievements_published);
        $this->assertEquals(0, $firstVersion->achievements_unpublished);
        $this->assertEquals(5, $firstVersion->players_total);
        $this->assertEquals(3, $firstVersion->players_hardcore);
        $this->assertEquals(75, $firstVersion->points_total);
        $this->assertEquals(177, $firstVersion->points_weighted); // weighted points not updated on old version

        // new version should inclue points changes
        $newVersion = $achievementSet->versions()->skip(1)->first();
        $this->assertEquals(2, $newVersion->version);
        $this->assertEquals($firstVersion->id, $newVersion->parent_id);
        $this->assertEquals(0, $newVersion->achievements_published);
        $this->assertEquals(0, $newVersion->achievements_unpublished);
        $this->assertEquals(5, $newVersion->players_total);
        $this->assertEquals(3, $newVersion->players_hardcore);
        $this->assertEquals(0, $newVersion->points_total);
        $this->assertEquals(0, $newVersion->points_weighted);
    });
});
