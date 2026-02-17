<?php

declare(strict_types=1);

use App\Community\Enums\CommentableType;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\Trigger;
use App\Models\User;
use App\Platform\Actions\BuildAchievementChangelogAction;
use App\Platform\Data\AchievementChangelogEntryData;
use App\Platform\Enums\AchievementChangelogEntryType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

/**
 * The `Achievement` model uses Spatie's `LogsActivity` trait, which auto-creates
 * activity entries on model events. This helper function clears those entries
 * so our tests can control exactly which activities exist.
 */
function createAchievementWithoutLog(array $attributes): Achievement
{
    $achievement = Achievement::factory()->create($attributes);
    Activity::query()->delete();

    return $achievement;
}

function createSystemUser(): User
{
    return User::factory()->create(['id' => Comment::SYSTEM_USER_ID]);
}

function createLegacyComment(Achievement $achievement, string $body, string $date): Comment
{
    return Comment::factory()->create([
        'commentable_type' => CommentableType::Achievement->value,
        'commentable_id' => $achievement->id,
        'user_id' => Comment::SYSTEM_USER_ID,
        'body' => $body,
        'created_at' => $date,
    ]);
}

function createActivity(
    Achievement $achievement,
    string $event,
    string $date,
    ?User $causer = null,
    array $attributes = [],
    array $old = [],
): Activity {
    $properties = [];
    if (!empty($attributes)) {
        $properties['attributes'] = $attributes;
    }
    if (!empty($old)) {
        $properties['old'] = $old;
    }

    return Activity::create([
        'subject_type' => 'achievement',
        'subject_id' => $achievement->id,
        'event' => $event,
        'description' => $event,
        'causer_type' => $causer ? 'user' : null,
        'causer_id' => $causer?->id,
        'properties' => $properties,
        'created_at' => $date,
        'updated_at' => $date,
    ]);
}

/**
 * @return AchievementChangelogEntryData[]
 */
function entriesOfType(array $result, AchievementChangelogEntryType $type): array
{
    return array_values(array_filter(
        $result,
        fn (AchievementChangelogEntryData $e) => $e->type === $type,
    ));
}

describe('Activitylog Entries', function () {
    it('maps lifecycle events to the correct entry type', function (string $event, AchievementChangelogEntryType $expectedType) {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createActivity($achievement, $event, '2024-06-15 12:00:00', $user);

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $entries = entriesOfType($result, $expectedType);
        expect($entries)->toHaveCount(1);
    })->with([
        'created' => ['created', AchievementChangelogEntryType::Created],
        'deleted' => ['deleted', AchievementChangelogEntryType::Deleted],
        'restored' => ['restored', AchievementChangelogEntryType::Restored],
    ]);

    it('maps field changes to the correct entry type with old/new values', function (
        string $field,
        mixed $oldVal,
        mixed $newVal,
        AchievementChangelogEntryType $expectedType,
        string $expectedOld,
        string $expectedNew,
    ) {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user,
            attributes: [$field => $newVal],
            old: [$field => $oldVal],
        );

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $entries = entriesOfType($result, $expectedType);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->fieldChanges)->toHaveCount(1);
        expect($entries[0]->fieldChanges[0]->oldValue)->toEqual($expectedOld);
        expect($entries[0]->fieldChanges[0]->newValue)->toEqual($expectedNew);
    })->with([
        'description' => ['description', 'Old desc', 'New desc', AchievementChangelogEntryType::DescriptionUpdated, 'Old desc', 'New desc'],
        'title' => ['title', 'Old Title', 'New Title', AchievementChangelogEntryType::TitleUpdated, 'Old Title', 'New Title'],
        'points' => ['points', 10, 25, AchievementChangelogEntryType::PointsChanged, '10', '25'],
    ]);

    it('maps field changes to the correct entry type without old/new values', function (
        string $field,
        mixed $newVal,
        mixed $oldVal,
        AchievementChangelogEntryType $expectedType,
    ) {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user,
            attributes: [$field => $newVal],
            old: [$field => $oldVal],
        );

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $entries = entriesOfType($result, $expectedType);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->fieldChanges)->toEqual([]);
    })->with([
        'embed_url' => ['embed_url', 'https://example.com/new', 'https://example.com/old', AchievementChangelogEntryType::EmbedUrlUpdated],
        'trigger_definition' => ['trigger_definition', '0x001234', '0x000000', AchievementChangelogEntryType::LogicUpdated],
    ]);

    it('includes badge image URLs in field changes for image_name changes', function () {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user,
            attributes: ['image_name' => '12345'],
            old: ['image_name' => '00000'],
        );

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $entries = entriesOfType($result, AchievementChangelogEntryType::BadgeUpdated);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->fieldChanges)->toHaveCount(1);
        expect($entries[0]->fieldChanges[0]->oldValue)->toContain('/Badge/00000.png');
        expect($entries[0]->fieldChanges[0]->newValue)->toContain('/Badge/12345.png');
    });

    it('includes game titles in field changes for game_id changes', function () {
        // Arrange
        $user = User::factory()->create();
        $oldGame = Game::factory()->create(['title' => 'Super Mario Bros.']);
        $newGame = Game::factory()->create(['title' => 'Super Mario Bros. [Subset - Bonus]']);
        $achievement = createAchievementWithoutLog(['game_id' => $oldGame->id, 'user_id' => $user->id]);

        createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user,
            attributes: ['game_id' => $newGame->id],
            old: ['game_id' => $oldGame->id],
        );

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $entries = entriesOfType($result, AchievementChangelogEntryType::MovedToDifferentGame);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->fieldChanges)->toHaveCount(1);
        expect($entries[0]->fieldChanges[0]->oldValue)->toEqual('Super Mario Bros.');
        expect($entries[0]->fieldChanges[0]->newValue)->toEqual('Super Mario Bros. [Subset - Bonus]');
    });

    it('maps is_promoted changes to promotion or demotion', function (
        int $newVal,
        int $oldVal,
        AchievementChangelogEntryType $expectedType,
    ) {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user,
            attributes: ['is_promoted' => $newVal],
            old: ['is_promoted' => $oldVal],
        );

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        expect(entriesOfType($result, $expectedType))->toHaveCount(1);
    })->with([
        'promoted' => [1, 0, AchievementChangelogEntryType::Promoted],
        'demoted' => [0, 1, AchievementChangelogEntryType::Demoted],
    ]);

    describe('Type field', function () {
        it('maps type changing from null to a value as TypeSet with formatted labels', function () {
            // Arrange
            $user = User::factory()->create();
            $game = Game::factory()->create();
            $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

            createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user,
                attributes: ['type' => 'progression'],
                old: ['type' => null],
            );

            // Act
            $result = (new BuildAchievementChangelogAction())->execute($achievement);

            // Assert
            $entries = entriesOfType($result, AchievementChangelogEntryType::TypeSet);
            expect($entries)->toHaveCount(1);
            expect($entries[0]->fieldChanges)->toHaveCount(1);
            expect($entries[0]->fieldChanges[0]->newValue)->toEqual('Progression');
        });

        it('maps type changing between two values as TypeChanged', function () {
            // Arrange
            $user = User::factory()->create();
            $game = Game::factory()->create();
            $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

            createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user,
                attributes: ['type' => 'win_condition'],
                old: ['type' => 'progression'],
            );

            // Act
            $result = (new BuildAchievementChangelogAction())->execute($achievement);

            // Assert
            $entries = entriesOfType($result, AchievementChangelogEntryType::TypeChanged);
            expect($entries)->toHaveCount(1);
            expect($entries[0]->fieldChanges[0]->oldValue)->toEqual('Progression');
            expect($entries[0]->fieldChanges[0]->newValue)->toEqual('Win Condition');
        });

        it('maps type changing from a value to null as TypeRemoved', function () {
            // Arrange
            $user = User::factory()->create();
            $game = Game::factory()->create();
            $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

            createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user,
                attributes: ['type' => null],
                old: ['type' => 'missable'],
            );

            // Act
            $result = (new BuildAchievementChangelogAction())->execute($achievement);

            // Assert
            expect(entriesOfType($result, AchievementChangelogEntryType::TypeRemoved))->toHaveCount(1);
        });
    });

    it('normalizes legacy PascalCase property names', function (
        string $legacyField,
        mixed $newVal,
        mixed $oldVal,
        AchievementChangelogEntryType $expectedType,
    ) {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user,
            attributes: [$legacyField => $newVal],
            old: [$legacyField => $oldVal],
        );

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        expect(entriesOfType($result, $expectedType))->toHaveCount(1);
    })->with([
        'Description -> DescriptionUpdated' => ['Description', 'New', 'Old', AchievementChangelogEntryType::DescriptionUpdated],
        'BadgeName -> BadgeUpdated' => ['BadgeName', '99999', '00000', AchievementChangelogEntryType::BadgeUpdated],
    ]);

    it('emits one entry per field when an activity has multiple changed fields', function () {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user,
            attributes: ['title' => 'New Title', 'description' => 'New Desc'],
            old: ['title' => 'Old Title', 'description' => 'Old Desc'],
        );

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        expect(entriesOfType($result, AchievementChangelogEntryType::TitleUpdated))->toHaveCount(1);
        expect(entriesOfType($result, AchievementChangelogEntryType::DescriptionUpdated))->toHaveCount(1);
    });

    it('skips activities with empty properties', function () {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user);

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        expect($result)->toHaveCount(1);
        expect($result[0]->type)->toEqual(AchievementChangelogEntryType::Created);
    });
});

describe('Trigger Entries', function () {
    it('creates a LogicUpdated entry for a trigger with a non-null parent_id after the cutoff', function () {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        $parentTrigger = Trigger::factory()->create([
            'triggerable_type' => 'achievement',
            'triggerable_id' => $achievement->id,
            'user_id' => $user->id,
            'parent_id' => null,
            'version' => 1,
            'created_at' => '2025-03-15 12:00:00',
        ]);

        Trigger::factory()->create([
            'triggerable_type' => 'achievement',
            'triggerable_id' => $achievement->id,
            'user_id' => $user->id,
            'parent_id' => $parentTrigger->id,
            'version' => 2,
            'created_at' => '2025-03-15 12:00:00',
        ]);

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $entries = entriesOfType($result, AchievementChangelogEntryType::LogicUpdated);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->user->displayName)->toEqual($user->display_name);
    });

    it('ignores triggers with a null parent_id', function () {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        Trigger::factory()->create([
            'triggerable_type' => 'achievement',
            'triggerable_id' => $achievement->id,
            'user_id' => $user->id,
            'parent_id' => null,
            'created_at' => '2025-03-15 12:00:00',
        ]);

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        expect(entriesOfType($result, AchievementChangelogEntryType::LogicUpdated))->toHaveCount(0);
    });
});

describe('Legacy Comment Entries', function () {
    it('parses comment patterns to the correct entry type', function (
        string $body,
        AchievementChangelogEntryType $expectedType,
    ) {
        // Arrange
        createSystemUser();
        $user = User::factory()->create(['display_name' => 'Scott']);
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createLegacyComment($achievement, $body, '2023-06-15 12:00:00');

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $entries = entriesOfType($result, $expectedType);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->user->displayName)->toEqual('Scott');
    })->with([
        'uploaded' => ['Scott uploaded this achievement.', AchievementChangelogEntryType::Created],
        'promoted' => ['Scott promoted this achievement to the Core set.', AchievementChangelogEntryType::Promoted],
        'demoted' => ['Scott demoted this achievement to Unofficial.', AchievementChangelogEntryType::Demoted],
        'badge' => ["Scott edited this achievement's badge.", AchievementChangelogEntryType::BadgeUpdated],
        'points' => ["Scott edited this achievement's points.", AchievementChangelogEntryType::PointsChanged],
        'wording' => ["Scott edited this achievement's wording.", AchievementChangelogEntryType::DescriptionUpdated],
        'edited' => ['Scott edited this achievement.', AchievementChangelogEntryType::Edited],
        'embed URL' => ["Scott set this achievement's embed URL.", AchievementChangelogEntryType::EmbedUrlUpdated],
        'type removed' => ["Scott removed this achievement's type.", AchievementChangelogEntryType::TypeRemoved],
    ]);

    it('parses quoted display names from older legacy comments', function () {
        // Arrange
        createSystemUser();
        $user = User::factory()->create(['display_name' => 'meleu']);
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createLegacyComment($achievement, '"meleu" promoted this achievement to the Core set.', '2020-06-15 12:00:00');

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $entries = entriesOfType($result, AchievementChangelogEntryType::Promoted);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->user->displayName)->toEqual('meleu');
    });

    it('parses "edited this achievement\'s description, logic" as two separate entries', function () {
        // Arrange
        createSystemUser();
        $user = User::factory()->create(['display_name' => 'Scott']);
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createLegacyComment($achievement, "Scott edited this achievement's description, logic.", '2023-06-15 12:00:00');

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        expect(entriesOfType($result, AchievementChangelogEntryType::DescriptionUpdated))->toHaveCount(1);
        expect(entriesOfType($result, AchievementChangelogEntryType::LogicUpdated))->toHaveCount(1);
    });

    it('parses "set this achievement\'s type to Progression" as TypeSet with fieldChanges', function () {
        // Arrange
        createSystemUser();
        $user = User::factory()->create(['display_name' => 'Scott']);
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createLegacyComment($achievement, "Scott set this achievement's type to Progression.", '2023-06-15 12:00:00');

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $entries = entriesOfType($result, AchievementChangelogEntryType::TypeSet);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->fieldChanges)->toHaveCount(1);
        expect($entries[0]->fieldChanges[0]->newValue)->toEqual('Progression');
    });

    it('falls back to Edited with null user for unrecognized comment formats', function () {
        // Arrange
        createSystemUser();
        $game = Game::factory()->create();
        $user = User::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createLegacyComment($achievement, 'Something completely unexpected happened.', '2023-06-15 12:00:00');

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $entries = entriesOfType($result, AchievementChangelogEntryType::Edited);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->user)->toBeNull();
    });

    it('does not include comments after the 2024-02-01 cutoff as legacy entries', function () {
        // Arrange
        createSystemUser();
        $user = User::factory()->create(['display_name' => 'Scott']);
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createLegacyComment($achievement, 'Scott uploaded this achievement.', '2024-06-15 12:00:00');

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        // ... the only "Created" should be the synthesized one, not the post-cutoff comment ...
        $created = entriesOfType($result, AchievementChangelogEntryType::Created);
        expect($created)->toHaveCount(1);
        expect($created[0]->user->displayName)->toEqual($user->display_name);

        expect($created[0]->createdAt->toDateTimeString())->toEqual($achievement->created_at->toDateTimeString());
    });
});

describe('Merging and Sorting', function () {
    it('sorts entries from all three sources descending by date', function () {
        // Arrange
        createSystemUser();
        $user = User::factory()->create(['display_name' => 'Scott']);
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'created_at' => '2020-01-01 00:00:00',
        ]);

        createLegacyComment($achievement, "Scott edited this achievement's badge.", '2023-01-15 12:00:00');

        createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user,
            attributes: ['title' => 'New'],
            old: ['title' => 'Old'],
        );

        $parentTrigger = Trigger::factory()->create([
            'triggerable_type' => 'achievement',
            'triggerable_id' => $achievement->id,
            'user_id' => $user->id,
            'parent_id' => null,
            'version' => 1,
            'created_at' => '2025-03-15 12:00:00',
        ]);
        Trigger::factory()->create([
            'triggerable_type' => 'achievement',
            'triggerable_id' => $achievement->id,
            'user_id' => $user->id,
            'parent_id' => $parentTrigger->id,
            'version' => 2,
            'created_at' => '2025-03-15 12:00:00',
        ]);

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        expect($result[0]->type)->toEqual(AchievementChangelogEntryType::LogicUpdated);
        expect($result[1]->type)->toEqual(AchievementChangelogEntryType::TitleUpdated);
        expect($result[2]->type)->toEqual(AchievementChangelogEntryType::BadgeUpdated);
        expect($result[3]->type)->toEqual(AchievementChangelogEntryType::Created);
    });
});

describe('Collapsing Consecutive Entries', function () {
    it('collapses consecutive generic edited entries of the same user into a single entry with a count', function () {
        // Arrange
        createSystemUser();
        $user = User::factory()->create(['display_name' => 'Scott']);
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createLegacyComment($achievement, 'Scott edited this achievement.', '2023-06-15 12:00:00');
        createLegacyComment($achievement, 'Scott edited this achievement.', '2023-06-14 12:00:00');
        createLegacyComment($achievement, 'Scott edited this achievement.', '2023-06-13 12:00:00');

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $entries = entriesOfType($result, AchievementChangelogEntryType::Edited);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->count)->toEqual(3);
    });

    it('does not collapse consecutive entries without field changes', function () {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user,
            attributes: ['embed_url' => 'https://example.com/a'],
            old: ['embed_url' => 'https://example.com/b'],
        );
        createActivity($achievement, 'updated', '2024-06-15 12:01:00', $user,
            attributes: ['embed_url' => 'https://example.com/c'],
            old: ['embed_url' => 'https://example.com/a'],
        );
        createActivity($achievement, 'updated', '2024-06-15 12:02:00', $user,
            attributes: ['embed_url' => 'https://example.com/d'],
            old: ['embed_url' => 'https://example.com/c'],
        );

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $entries = entriesOfType($result, AchievementChangelogEntryType::EmbedUrlUpdated);
        expect($entries)->toHaveCount(3);
    });

    it('does not collapse consecutive entries of different types', function () {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createActivity($achievement, 'updated', '2024-06-15 12:01:00', $user,
            attributes: ['image_name' => '11111'],
            old: ['image_name' => '00000'],
        );
        createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user,
            attributes: ['title' => 'New'],
            old: ['title' => 'Old'],
        );

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        expect(entriesOfType($result, AchievementChangelogEntryType::BadgeUpdated))->toHaveCount(1);
        expect(entriesOfType($result, AchievementChangelogEntryType::TitleUpdated))->toHaveCount(1);
    });

    it('does not collapse consecutive entries from different users', function () {
        // Arrange
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $userA->id]);

        createActivity($achievement, 'updated', '2024-06-15 12:01:00', $userA,
            attributes: ['image_name' => '11111'],
            old: ['image_name' => '00000'],
        );
        createActivity($achievement, 'updated', '2024-06-15 12:00:00', $userB,
            attributes: ['image_name' => '22222'],
            old: ['image_name' => '11111'],
        );

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        expect(entriesOfType($result, AchievementChangelogEntryType::BadgeUpdated))->toHaveCount(2);
    });

    it('merges field changes during collapse, keeping oldest oldValue and newest newValue', function () {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createActivity($achievement, 'updated', '2024-06-15 12:01:00', $user,
            attributes: ['points' => 50],
            old: ['points' => 25],
        );
        createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user,
            attributes: ['points' => 25],
            old: ['points' => 10],
        );

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $entries = entriesOfType($result, AchievementChangelogEntryType::PointsChanged);
        expect($entries)->toHaveCount(1);

        expect($entries[0]->fieldChanges[0]->oldValue)->toEqual('10');
        expect($entries[0]->fieldChanges[0]->newValue)->toEqual('50');
    });
});

describe('Net-Zero Removal', function () {
    it('removes entries where collapsing produces identical old and new values', function () {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        // ... 10 -> 25 -> 10, this is a net-zero change ...
        createActivity($achievement, 'updated', '2024-06-15 12:01:00', $user,
            attributes: ['points' => 10],
            old: ['points' => 25],
        );
        createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user,
            attributes: ['points' => 25],
            old: ['points' => 10],
        );

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        expect(entriesOfType($result, AchievementChangelogEntryType::PointsChanged))->toHaveCount(0);
    });
});

describe('Created Entry Anchor', function () {
    it('appends a Created entry at the bottom when none exists', function () {
        // Arrange
        $developer = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog([
            'game_id' => $game->id,
            'user_id' => $developer->id,
            'created_at' => '2020-06-15 12:00:00',
        ]);

        createActivity($achievement, 'updated', '2024-06-15 12:00:00', $developer,
            attributes: ['title' => 'New'],
            old: ['title' => 'Old'],
        );

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $lastEntry = end($result);
        expect($lastEntry->type)->toEqual(AchievementChangelogEntryType::Created);
        expect($lastEntry->user->displayName)->toEqual($developer->display_name);
        expect($lastEntry->createdAt->toDateTimeString())->toEqual('2020-06-15 12:00:00');
    });

    it('does not duplicate when Created already exists from the activitylog', function () {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createActivity($achievement, 'created', '2024-06-15 12:00:00', $user);

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        expect(entriesOfType($result, AchievementChangelogEntryType::Created))->toHaveCount(1);
    });

    it('does not duplicate when Created already exists from legacy comments', function () {
        // Arrange
        createSystemUser();
        $user = User::factory()->create(['display_name' => 'Scott']);
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createLegacyComment($achievement, 'Scott uploaded this achievement.', '2023-06-15 12:00:00');

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        expect(entriesOfType($result, AchievementChangelogEntryType::Created))->toHaveCount(1);
    });

    it('sets user to null when the achievement has no developer', function () {
        // Arrange
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog([
            'game_id' => $game->id,
            'user_id' => null,
        ]);

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        expect($result)->toHaveCount(1);
        expect($result[0]->type)->toEqual(AchievementChangelogEntryType::Created);
        expect($result[0]->user)->toBeNull();
    });
});

describe('User Backfilling', function () {
    it('backfills a missing causer from a nearby activitylog entry within 5 minutes', function () {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        // ... this entry has a causer ...
        createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user,
            attributes: ['title' => 'New'],
            old: ['title' => 'Old'],
        );

        // ... this entry has no causer, but is within 5 minutes ...
        createActivity($achievement, 'updated', '2024-06-15 12:02:00', null,
            attributes: ['description' => 'New desc'],
            old: ['description' => 'Old desc'],
        );

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $entries = entriesOfType($result, AchievementChangelogEntryType::DescriptionUpdated);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->user)->not->toBeNull();
        expect($entries[0]->user->displayName)->toEqual($user->display_name);
    });

    it('does not backfill from an activitylog entry more than 5 minutes away', function () {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        // ... this entry has a causer but is more than 5 minutes away ...
        createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user,
            attributes: ['title' => 'New'],
            old: ['title' => 'Old'],
        );

        // ... this entry has no causer and is more than 5 min from the other ...
        createActivity($achievement, 'updated', '2024-06-15 12:10:00', null,
            attributes: ['description' => 'New desc'],
            old: ['description' => 'Old desc'],
        );

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $entries = entriesOfType($result, AchievementChangelogEntryType::DescriptionUpdated);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->user)->toBeNull();
    });

    it('backfills a missing user from a post-cutoff legacy comment within 5 minutes', function () {
        // Arrange
        createSystemUser();
        $user = User::factory()->create(['display_name' => 'Scott']);
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        // ... this activity has no causer ...
        createActivity($achievement, 'updated', '2024-06-15 12:00:00', null,
            attributes: ['title' => 'New'],
            old: ['title' => 'Old'],
        );

        // ... this is a post-cutoff legacy comment within 5 min that names the user ...
        createLegacyComment($achievement, "Scott edited this achievement's title.", '2024-06-15 12:01:00');

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $entries = entriesOfType($result, AchievementChangelogEntryType::TitleUpdated);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->user)->not->toBeNull();
        expect($entries[0]->user->displayName)->toEqual('Scott');
    });
});

describe('Edge Cases', function () {
    it('returns only a synthesized Created entry when there is no changelog data', function () {
        // Arrange
        $developer = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $developer->id]);

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        expect($result)->toHaveCount(1);
        expect($result[0]->type)->toEqual(AchievementChangelogEntryType::Created);
        expect($result[0]->user->displayName)->toEqual($developer->display_name);
    });

    it('resolves a soft-deleted user as the activitylog causer', function () {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createActivity($achievement, 'updated', '2024-06-15 12:00:00', $user,
            attributes: ['title' => 'New'],
            old: ['title' => 'Old'],
        );

        $user->delete();

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $entries = entriesOfType($result, AchievementChangelogEntryType::TitleUpdated);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->user)->not->toBeNull();
        expect($entries[0]->user->displayName)->toEqual($user->display_name);
    });

    it('resolves a soft-deleted user in a legacy comment', function () {
        // Arrange
        createSystemUser();
        $user = User::factory()->create(['display_name' => 'Scott']);
        $game = Game::factory()->create();
        $achievement = createAchievementWithoutLog(['game_id' => $game->id, 'user_id' => $user->id]);

        createLegacyComment($achievement, "Scott edited this achievement's title.", '2023-06-15 12:00:00');

        $user->delete();

        // Act
        $result = (new BuildAchievementChangelogAction())->execute($achievement);

        // Assert
        $entries = entriesOfType($result, AchievementChangelogEntryType::TitleUpdated);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->user)->not->toBeNull();
        expect($entries[0]->user->displayName)->toEqual('Scott');
    });
});
