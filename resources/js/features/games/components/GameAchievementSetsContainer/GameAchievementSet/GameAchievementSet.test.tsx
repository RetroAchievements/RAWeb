import {
  currentAchievementSortAtom,
  isLockedOnlyFilterEnabledAtom,
  isMissableOnlyFilterEnabledAtom,
} from '@/features/games/state/games.atoms';
import { render, screen } from '@/test';
import {
  createAchievement,
  createAchievementSet,
  createGame,
  createGameAchievementSet,
} from '@/test/factories';

import { GameAchievementSet } from './GameAchievementSet';

describe('Component: GameAchievementSet', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <GameAchievementSet
        achievements={[]}
        gameAchievementSet={createGameAchievementSet()}
        isOnlySetForGame={false}
      />,
      {
        jotaiAtoms: [
          [currentAchievementSortAtom, 'normal'],
          //
        ],
        pageProps: {
          game: createGame(),
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an empty achievements array, renders an empty achievement list', () => {
    // ARRANGE
    render(
      <GameAchievementSet
        achievements={[]}
        gameAchievementSet={createGameAchievementSet()}
        isOnlySetForGame={false}
      />,
      {
        jotaiAtoms: [
          [currentAchievementSortAtom, 'normal'],
          //
        ],
        pageProps: {
          game: createGame(),
        },
      },
    );

    // ASSERT
    expect(screen.queryByRole('listitem', { name: /achievement/i })).not.toBeInTheDocument();
  });

  it('given an achievement list longer than 50 items, renders all items', () => {
    // ARRANGE
    const achievements = Array.from({ length: 51 }, (_, i) =>
      createAchievement({
        id: i + 1,
        title: `Achievement ${i + 1}`,
      }),
    );

    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements,
      }),
    });

    render(
      <GameAchievementSet
        achievements={achievements}
        gameAchievementSet={gameAchievementSet}
        isOnlySetForGame={false}
      />,
      {
        jotaiAtoms: [
          [currentAchievementSortAtom, 'normal'],
          //
        ],
        pageProps: {
          game: createGame(),
        },
      },
    );

    // ASSERT
    expect(screen.getAllByRole('listitem').length).toBeGreaterThanOrEqual(50);
  });

  it('given the collapsible is initially opened, shows achievements', () => {
    // ARRANGE
    const achievement = createAchievement({ title: 'Visible Achievement' });
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements: [achievement],
      }),
    });

    render(
      <GameAchievementSet
        achievements={[achievement]}
        gameAchievementSet={gameAchievementSet}
        isOnlySetForGame={false}
      />,
      {
        jotaiAtoms: [
          [currentAchievementSortAtom, 'normal'],
          //
        ],
        pageProps: {
          game: createGame(),
        },
      },
    );

    // ASSERT
    expect(screen.getByText('Visible Achievement')).toBeVisible();
  });

  it('given the current sort changes, re-renders the achievement list', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ title: 'B Achievement' }),
      createAchievement({ title: 'A Achievement' }),
    ];

    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements,
      }),
    });

    const { rerender } = render(
      <GameAchievementSet
        achievements={achievements}
        gameAchievementSet={gameAchievementSet}
        isOnlySetForGame={false}
      />,
      {
        jotaiAtoms: [
          [currentAchievementSortAtom, 'normal'],
          //
        ],
        pageProps: {
          game: createGame(),
        },
      },
    );

    // ACT
    rerender(
      <GameAchievementSet
        achievements={achievements}
        gameAchievementSet={gameAchievementSet}
        isOnlySetForGame={false}
      />,
    );

    // ASSERT
    const items = screen.getAllByRole('listitem');
    expect(items.length).toBeGreaterThanOrEqual(2);
  });

  it('given no missable achievements, does not filter by missable even when the filter is enabled', () => {
    /**
     * This can happen if some achievements are set to missable, the user
     * persists the filter, and then a dev changes their type to not be
     * missable anymore.
     */

    // ARRANGE
    const achievements = [
      createAchievement({ title: 'Normal Achievement 1', type: null }), // !! not missable
      createAchievement({ title: 'Normal Achievement 2', type: null }), // !! not missable
    ];

    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements,
      }),
    });

    render(
      <GameAchievementSet
        achievements={achievements}
        gameAchievementSet={gameAchievementSet}
        isOnlySetForGame={false}
      />,
      {
        jotaiAtoms: [
          [currentAchievementSortAtom, 'normal'],
          [isMissableOnlyFilterEnabledAtom, true], // !!
        ],
        pageProps: {
          game: createGame(),
        },
      },
    );

    // ASSERT
    // ... all achievements should be visible since there are no missable achievements ...
    expect(screen.getByText('Normal Achievement 1')).toBeVisible();
    expect(screen.getByText('Normal Achievement 2')).toBeVisible();
  });

  it('given missable achievements exist but the filter is disabled, shows all achievements', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ title: 'Normal Achievement', type: null }), // !! not missable
      createAchievement({ title: 'Missable Achievement', type: 'missable' }), // !! missable
    ];

    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements,
      }),
    });

    render(
      <GameAchievementSet
        achievements={achievements}
        gameAchievementSet={gameAchievementSet}
        isOnlySetForGame={false}
      />,
      {
        jotaiAtoms: [
          [currentAchievementSortAtom, 'normal'],
          [isMissableOnlyFilterEnabledAtom, false], // !!
        ],
        pageProps: {
          game: createGame(),
        },
      },
    );

    // ASSERT
    expect(screen.getByText('Normal Achievement')).toBeVisible();
    expect(screen.getByText('Missable Achievement')).toBeVisible();
  });

  it('given locked achievements exist, the user has unlocked achievements, and the locked only filter is enabled, shows only locked achievements', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ title: 'Locked Achievement', unlockedAt: undefined }),
      createAchievement({ title: 'Unlocked Achievement 1', unlockedAt: new Date().toISOString() }),
      createAchievement({ title: 'Unlocked Achievement 2', unlockedAt: new Date().toISOString() }),
    ];

    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements,
      }),
    });

    render(
      <GameAchievementSet
        achievements={achievements}
        gameAchievementSet={gameAchievementSet}
        isOnlySetForGame={false}
      />,
      {
        jotaiAtoms: [
          [currentAchievementSortAtom, 'normal'],
          [isLockedOnlyFilterEnabledAtom, true], // !!
        ],
        pageProps: {
          game: createGame(),
        },
      },
    );

    // ASSERT
    // ... only locked achievements should be visible ...
    expect(screen.queryByText('Unlocked Achievement 1')).not.toBeInTheDocument();
    expect(screen.queryByText('Unlocked Achievement 2')).not.toBeInTheDocument();
    expect(screen.getByText('Locked Achievement')).toBeVisible();
  });

  it('given missable achievements exist and filter is enabled, shows only missable achievements', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ title: 'Normal Achievement', type: null }), // !! not missable
      createAchievement({ title: 'Missable Achievement 1', type: 'missable' }), // !! missable
      createAchievement({ title: 'Missable Achievement 2', type: 'missable' }), // !! missable
    ];

    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements,
      }),
    });

    render(
      <GameAchievementSet
        achievements={achievements}
        gameAchievementSet={gameAchievementSet}
        isOnlySetForGame={false}
      />,
      {
        jotaiAtoms: [
          [currentAchievementSortAtom, 'normal'],
          [isMissableOnlyFilterEnabledAtom, true], // !!
        ],
        pageProps: {
          game: createGame(),
        },
      },
    );

    // ASSERT
    // ... only missable achievements should be visible ...
    expect(screen.queryByText('Normal Achievement')).not.toBeInTheDocument();
    expect(screen.getByText('Missable Achievement 1')).toBeVisible();
    expect(screen.getByText('Missable Achievement 2')).toBeVisible();
  });
});
