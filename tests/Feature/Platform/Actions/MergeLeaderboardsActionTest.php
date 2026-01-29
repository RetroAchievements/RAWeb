<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\Role;
use App\Models\User;
use App\Platform\Actions\MergeLeaderboardsAction;
use App\Platform\Enums\LeaderboardState;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesTableSeeder::class);
});

function createDeveloper(): User
{
    $developer = User::factory()->create();
    $developer->assignRole(Role::DEVELOPER);

    return $developer;
}

function createLeaderboardPair(
    Game $game,
    string $format = 'VALUE',
    bool $rankAsc = false,
): array {
    $parent = Leaderboard::factory()->create([
        'game_id' => $game->id,
        'format' => $format,
        'rank_asc' => $rankAsc,
    ]);

    $child = Leaderboard::factory()->create([
        'game_id' => $game->id,
        'format' => $format,
        'rank_asc' => $rankAsc,
    ]);

    return [$parent, $child];
}

describe('Entry Transfer', function () {
    it('given entries exist in the child, transfers them to the parent', function () {
        // Arrange
        $developer = createDeveloper();
        $player1 = User::factory()->create();
        $player2 = User::factory()->create();
        $game = Game::factory()->create();

        [$parent, $child] = createLeaderboardPair($game);

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $child->id,
            'user_id' => $player1->id,
            'score' => 100,
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $child->id,
            'user_id' => $player2->id,
            'score' => 200,
        ]);

        // Act
        $result = (new MergeLeaderboardsAction())->execute($parent, $child, $developer);

        // Assert
        expect($result['entries_transferred'])->toEqual(2);
        expect($result['entries_merged'])->toEqual(0);
        expect($result['entries_skipped'])->toEqual(0);
        expect(LeaderboardEntry::where('leaderboard_id', $parent->id)->count())->toEqual(2);
        expect(LeaderboardEntry::where('leaderboard_id', $child->id)->count())->toEqual(0);
    });

    it('given an empty child leaderboard, succeeds even though there are zero entry transfers', function () {
        // Arrange
        $developer = createDeveloper();
        $game = Game::factory()->create();

        [$parent, $child] = createLeaderboardPair($game);

        // Act
        $result = (new MergeLeaderboardsAction())->execute($parent, $child, $developer);

        // Assert
        expect($result['entries_transferred'])->toEqual(0);
        expect($result['entries_merged'])->toEqual(0);
        expect($result['entries_skipped'])->toEqual(0);

        $child->refresh();
        expect($child->state)->toEqual(LeaderboardState::Unpromoted);
    });

    it('given a user exists in both leaderboards, correctly soft deletes the child entry', function () {
        // Arrange
        $developer = createDeveloper();
        $player = User::factory()->create();
        $game = Game::factory()->create();

        [$parent, $child] = createLeaderboardPair($game);

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $parent->id,
            'user_id' => $player->id,
            'score' => 100,
        ]);

        $childEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $child->id,
            'user_id' => $player->id,
            'score' => 200,
        ]);

        // Act
        (new MergeLeaderboardsAction())->execute($parent, $child, $developer);

        // Assert
        $this->assertSoftDeleted('leaderboard_entries', ['id' => $childEntry->id]);
    });
});

describe('Score Resolution', function () {
    it('given a user exists in both leaderboards and the child entry has a better score (higher is better), updates the parent entry', function () {
        // Arrange
        $developer = createDeveloper();
        $player = User::factory()->create();
        $game = Game::factory()->create();

        [$parent, $child] = createLeaderboardPair(
            $game,
            rankAsc: false // !!
        );

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $parent->id,
            'user_id' => $player->id,
            'score' => 100,
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $child->id,
            'user_id' => $player->id,
            'score' => 200, // !! better score
        ]);

        // Act
        $result = (new MergeLeaderboardsAction())->execute($parent, $child, $developer);

        // Assert
        expect($result['entries_transferred'])->toEqual(0);
        expect($result['entries_merged'])->toEqual(1);
        expect($result['entries_skipped'])->toEqual(0);

        $parentEntry = LeaderboardEntry::where('leaderboard_id', $parent->id)
            ->where('user_id', $player->id)
            ->first();
        expect($parentEntry->score)->toEqual(200);
    });

    it('given a user exists in both leaderboards and the child entry has better score (lower is better), updates the parent entry', function () {
        // Arrange
        $developer = createDeveloper();
        $player = User::factory()->create();
        $game = Game::factory()->create();

        [$parent, $child] = createLeaderboardPair(
            $game,
            rankAsc: true // !!
        );

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $parent->id,
            'user_id' => $player->id,
            'score' => 200,
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $child->id,
            'user_id' => $player->id,
            'score' => 100, // !! better score (lower is better)
        ]);

        // Act
        $result = (new MergeLeaderboardsAction())->execute($parent, $child, $developer);

        // Assert
        expect($result['entries_transferred'])->toEqual(0);
        expect($result['entries_merged'])->toEqual(1);
        expect($result['entries_skipped'])->toEqual(0);

        $parentEntry = LeaderboardEntry::where('leaderboard_id', $parent->id)
            ->where('user_id', $player->id)
            ->first();
        expect($parentEntry->score)->toEqual(100);
    });

    it('given a user exists in both leaderboards and the parent entry has a better score, keeps the parent score', function () {
        // Arrange
        $developer = createDeveloper();
        $player = User::factory()->create();
        $game = Game::factory()->create();

        [$parent, $child] = createLeaderboardPair($game, rankAsc: false);

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $parent->id,
            'user_id' => $player->id,
            'score' => 200, // !! better, should be kept
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $child->id,
            'user_id' => $player->id,
            'score' => 100,
        ]);

        // Act
        $result = (new MergeLeaderboardsAction())->execute($parent, $child, $developer);

        // Assert
        expect($result['entries_transferred'])->toEqual(0);
        expect($result['entries_merged'])->toEqual(0);
        expect($result['entries_skipped'])->toEqual(1);

        $parentEntry = LeaderboardEntry::where('leaderboard_id', $parent->id)
            ->where('user_id', $player->id)
            ->first();
        expect($parentEntry->score)->toEqual(200);
    });

    it('given equal scores and the child entry was created earlier, keeps the child entry', function () {
        // Arrange
        $developer = createDeveloper();
        $player = User::factory()->create();
        $game = Game::factory()->create();

        [$parent, $child] = createLeaderboardPair($game);

        $parentEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $parent->id,
            'user_id' => $player->id,
            'score' => 100,
            'created_at' => now(),
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $child->id,
            'user_id' => $player->id,
            'score' => 100, // !! same score, but created earlier
            'created_at' => now()->subMonth(),
        ]);

        // Act
        $result = (new MergeLeaderboardsAction())->execute($parent, $child, $developer);

        // Assert
        expect($result['entries_merged'])->toEqual(1);
        expect($result['entries_skipped'])->toEqual(0);

        $parentEntry->refresh();
        expect($parentEntry->created_at->format('Y-m'))->toEqual(now()->subMonth()->format('Y-m'));
    });

    it('given equal scores and the parent entry was created earlier, keeps the parent entry', function () {
        // Arrange
        $developer = createDeveloper();
        $player = User::factory()->create();
        $game = Game::factory()->create();

        [$parent, $child] = createLeaderboardPair($game);

        $parentEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $parent->id,
            'user_id' => $player->id,
            'score' => 100,
            'created_at' => now()->subMonth(), // !! created earlier
        ]);
        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $child->id,
            'user_id' => $player->id,
            'score' => 100,
            'created_at' => now(),
        ]);

        // Act
        $result = (new MergeLeaderboardsAction())->execute($parent, $child, $developer);

        // Assert
        expect($result['entries_merged'])->toEqual(0);
        expect($result['entries_skipped'])->toEqual(1);

        $parentEntry->refresh();
        expect($parentEntry->created_at->format('Y-m'))->toEqual(now()->subMonth()->format('Y-m'));
    });
});

describe('Child Leaderboard State', function () {
    it('given a merge completes, sets the child leaderboard to unpromoted', function () {
        // Arrange
        $developer = createDeveloper();
        $game = Game::factory()->create();

        [$parent, $child] = createLeaderboardPair($game);
        $child->update(['state' => LeaderboardState::Active]);

        // Act
        (new MergeLeaderboardsAction())->execute($parent, $child, $developer);

        // Assert
        $child->refresh();
        expect($child->state)->toEqual(LeaderboardState::Unpromoted);
    });

    it('clears the child leaderboard top entry id after a successful merge', function () {
        // Arrange
        $developer = createDeveloper();
        $player = User::factory()->create();
        $game = Game::factory()->create();

        [$parent, $child] = createLeaderboardPair($game);

        $childEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $child->id,
            'user_id' => $player->id,
            'score' => 100,
        ]);

        $child->top_entry_id = $childEntry->id;
        $child->save();

        // Act
        (new MergeLeaderboardsAction())->execute($parent, $child, $developer);

        // Assert
        $child->refresh();
        expect($child->top_entry_id)->toBeNull();
    });
});

describe('Parent Leaderboard State', function () {
    it('given entries are transferred, always recalculates the parent top entry id', function () {
        // Arrange
        $developer = createDeveloper();
        $player = User::factory()->create();
        $game = Game::factory()->create();

        [$parent, $child] = createLeaderboardPair($game);
        $parent->top_entry_id = null;
        $parent->save();

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $child->id, // !! on the child. the parent has 0 entries
            'user_id' => $player->id,
            'score' => 500,
        ]);

        // Act
        (new MergeLeaderboardsAction())->execute($parent, $child, $developer);

        // Assert
        $parent->refresh();
        expect($parent->top_entry_id)->not->toBeNull();

        $topEntry = LeaderboardEntry::find($parent->top_entry_id);
        expect($topEntry->score)->toEqual(500);
        expect($topEntry->user_id)->toEqual($player->id);
    });
});

describe('Activity Logging', function () {
    it('given a merge completes, logs activity on the parent leaderboard', function () {
        // Arrange
        $developer = createDeveloper();
        $player = User::factory()->create();
        $game = Game::factory()->create();

        [$parent, $child] = createLeaderboardPair($game);

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $child->id,
            'user_id' => $player->id,
            'score' => 100,
        ]);

        // Act
        (new MergeLeaderboardsAction())->execute($parent, $child, $developer);

        // Assert
        $activity = Activity::where('subject_type', 'leaderboard')
            ->where('subject_id', $parent->id)
            ->where('event', 'mergedFromLeaderboard')
            ->first();

        expect($activity)->not->toBeNull();
        expect($activity->causer_id)->toEqual($developer->id);

        $properties = $activity->properties->toArray();
        expect($properties['child_leaderboard_id'])->toEqual($child->id);
        expect($properties['attributes']['child_leaderboard_title'])->toEqual($child->title);
        expect($properties['attributes']['entries_transferred'])->toEqual(1);
    });
});

describe('Validation', function () {
    it('given the same leaderboard as parent and child, throws an exception', function () {
        // Arrange
        $developer = createDeveloper();
        $game = Game::factory()->create();

        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'format' => 'VALUE',
            'rank_asc' => false,
        ]);

        // Assert
        expect(fn () => (new MergeLeaderboardsAction())->execute($leaderboard, $leaderboard, $developer))
            ->toThrow(InvalidArgumentException::class, 'Cannot merge a leaderboard with itself.');
    });

    it('given different leaderboard formats, throws an exception', function () {
        // Arrange
        $developer = createDeveloper();
        $game = Game::factory()->create();

        $parent = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'format' => 'VALUE',
            'rank_asc' => false,
        ]);
        $child = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'format' => 'TIME',
            'rank_asc' => false,
        ]);

        // Assert
        expect(fn () => (new MergeLeaderboardsAction())->execute($parent, $child, $developer))
            ->toThrow(InvalidArgumentException::class, 'Leaderboard formats do not match');
    });

    it('given different rank directions, throws an exception', function () {
        // Arrange
        $developer = createDeveloper();
        $game = Game::factory()->create();

        $parent = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'format' => 'VALUE',
            'rank_asc' => false,
        ]);
        $child = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'format' => 'VALUE',
            'rank_asc' => true,
        ]);

        // Assert
        expect(fn () => (new MergeLeaderboardsAction())->execute($parent, $child, $developer))
            ->toThrow(InvalidArgumentException::class, 'Leaderboard rank directions do not match');
    });
});

describe('Cross-Game Merge', function () {
    it('given leaderboards from different games, still allows a merge', function () {
        // Arrange
        $developer = createDeveloper();
        $player = User::factory()->create();

        $game1 = Game::factory()->create();
        $game2 = Game::factory()->create();

        $parent = Leaderboard::factory()->create([
            'game_id' => $game1->id,
            'format' => 'VALUE',
            'rank_asc' => false,
        ]);
        $child = Leaderboard::factory()->create([
            'game_id' => $game2->id,
            'format' => 'VALUE',
            'rank_asc' => false,
        ]);

        LeaderboardEntry::factory()->create([
            'leaderboard_id' => $child->id,
            'user_id' => $player->id,
            'score' => 100,
        ]);

        // Act
        $result = (new MergeLeaderboardsAction())->execute($parent, $child, $developer);

        // Assert
        expect($result['entries_transferred'])->toEqual(1);
        expect(LeaderboardEntry::where('leaderboard_id', $parent->id)->count())->toEqual(1);
    });
});
