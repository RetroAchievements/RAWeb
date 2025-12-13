import * as useIsHydratedModule from '@/common/hooks/useIsHydrated';
import { createAuthenticatedUser } from '@/common/models';
import {
  currentListViewAtom,
  currentPlayableListSortAtom,
  isLockedOnlyFilterEnabledAtom,
  isMissableOnlyFilterEnabledAtom,
} from '@/features/games/state/games.atoms';
import { render, screen } from '@/test';
import {
  createAchievement,
  createAchievementSet,
  createAggregateAchievementSetCredits,
  createGame,
  createGameAchievementSet,
  createLeaderboard,
  createZiggyProps,
} from '@/test/factories';

import { GameAchievementSet } from './GameAchievementSet';

describe('Component: GameAchievementSet', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const game = createGame();

    const { container } = render(
      <GameAchievementSet achievements={[]} gameAchievementSet={createGameAchievementSet()} />,
      {
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'normal'],
          //
        ],
        pageProps: {
          game,
          achievementSetClaims: [],
          aggregateCredits: createAggregateAchievementSetCredits(),
          backingGame: game,
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an empty achievements array, renders an empty achievement list', () => {
    // ARRANGE
    const game = createGame();

    render(
      <GameAchievementSet achievements={[]} gameAchievementSet={createGameAchievementSet()} />,
      {
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'normal'],
          //
        ],
        pageProps: {
          game,
          achievementSetClaims: [],
          aggregateCredits: createAggregateAchievementSetCredits(),
          backingGame: game,
        },
      },
    );

    // ASSERT
    expect(screen.queryByRole('listitem', { name: /achievement/i })).not.toBeInTheDocument();
  });

  it('given an achievement list longer than 50 items, renders all items', () => {
    // ARRANGE
    const game = createGame();

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
      <GameAchievementSet achievements={achievements} gameAchievementSet={gameAchievementSet} />,
      {
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'normal'],
          //
        ],
        pageProps: {
          game,
          achievementSetClaims: [],
          aggregateCredits: createAggregateAchievementSetCredits(),
          backingGame: game,
        },
      },
    );

    // ASSERT
    expect(screen.getAllByRole('listitem').length).toBeGreaterThanOrEqual(50);
    expect(screen.getByTestId('game-achievement-set-toolbar')).toBeVisible();
  });

  it('given the collapsible is initially opened, shows achievements', () => {
    // ARRANGE
    const game = createGame();
    const achievement = createAchievement({ title: 'Visible Achievement' });
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements: [achievement],
      }),
    });

    render(
      <GameAchievementSet achievements={[achievement]} gameAchievementSet={gameAchievementSet} />,
      {
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'normal'],
          //
        ],
        pageProps: {
          game,
          achievementSetClaims: [],
          aggregateCredits: createAggregateAchievementSetCredits(),
          backingGame: game,
        },
      },
    );

    // ASSERT
    expect(screen.getByText('Visible Achievement')).toBeVisible();
  });

  it('given the current sort changes, re-renders the achievement list', () => {
    // ARRANGE
    const game = createGame();
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
      <GameAchievementSet achievements={achievements} gameAchievementSet={gameAchievementSet} />,
      {
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'normal'],
          //
        ],
        pageProps: {
          game,
          achievementSetClaims: [],
          aggregateCredits: createAggregateAchievementSetCredits(),
          backingGame: game,
        },
      },
    );

    // ACT
    rerender(
      <GameAchievementSet achievements={achievements} gameAchievementSet={gameAchievementSet} />,
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
    const game = createGame();
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
      <GameAchievementSet achievements={achievements} gameAchievementSet={gameAchievementSet} />,
      {
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'normal'],
          [isMissableOnlyFilterEnabledAtom, true], // !!
        ],
        pageProps: {
          game,
          achievementSetClaims: [],
          aggregateCredits: createAggregateAchievementSetCredits(),
          backingGame: game,
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
    const game = createGame();
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
      <GameAchievementSet achievements={achievements} gameAchievementSet={gameAchievementSet} />,
      {
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'normal'],
          [isMissableOnlyFilterEnabledAtom, false], // !!
        ],
        pageProps: {
          game,
          achievementSetClaims: [],
          aggregateCredits: createAggregateAchievementSetCredits(),
          backingGame: game,
        },
      },
    );

    // ASSERT
    expect(screen.getByText('Normal Achievement')).toBeVisible();
    expect(screen.getByText('Missable Achievement')).toBeVisible();
  });

  it('given locked achievements exist, the user has unlocked achievements, and the locked only filter is enabled, shows only locked achievements', () => {
    // ARRANGE
    const game = createGame();
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
      <GameAchievementSet achievements={achievements} gameAchievementSet={gameAchievementSet} />,
      {
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'normal'],
          [isLockedOnlyFilterEnabledAtom, true], // !!
        ],
        pageProps: {
          game,
          achievementSetClaims: [],
          aggregateCredits: createAggregateAchievementSetCredits(),
          backingGame: game,
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
    const game = createGame();
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
      <GameAchievementSet achievements={achievements} gameAchievementSet={gameAchievementSet} />,
      {
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'normal'],
          [isMissableOnlyFilterEnabledAtom, true], // !!
        ],
        pageProps: {
          game,
          achievementSetClaims: [],
          aggregateCredits: createAggregateAchievementSetCredits(),
          backingGame: game,
        },
      },
    );

    // ASSERT
    // ... only missable achievements should be visible ...
    expect(screen.queryByText('Normal Achievement')).not.toBeInTheDocument();
    expect(screen.getByText('Missable Achievement 1')).toBeVisible();
    expect(screen.getByText('Missable Achievement 2')).toBeVisible();
  });

  it('given the set has no achievements, does not display the sort/filter toolbar', () => {
    // ARRANGE
    const game = createGame();
    const achievements: App.Platform.Data.Achievement[] = []; // !!

    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements,
      }),
    });

    render(
      <GameAchievementSet achievements={achievements} gameAchievementSet={gameAchievementSet} />,
      {
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'normal'],
          [isMissableOnlyFilterEnabledAtom, true], // !!
        ],
        pageProps: {
          game,
          achievementSetClaims: [],
          aggregateCredits: createAggregateAchievementSetCredits(),
          backingGame: game,
        },
      },
    );

    // ASSERT
    expect(screen.queryByTestId('game-achievement-set-toolbar')).not.toBeInTheDocument();
  });

  it('given the current view is leaderboards, shows leaderboards instead of achievements', () => {
    // ARRANGE
    const game = createGame();
    const achievements = [
      createAchievement({ title: 'Test Achievement 1' }),
      createAchievement({ title: 'Test Achievement 2' }),
    ];
    const allLeaderboards = [
      createLeaderboard({ title: 'High Score' }),
      createLeaderboard({ title: 'Speed Run' }),
    ];

    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({
        achievements,
      }),
    });

    render(
      <GameAchievementSet achievements={achievements} gameAchievementSet={gameAchievementSet} />,
      {
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'normal'],
          [currentListViewAtom, 'leaderboards'], // !!
        ],
        pageProps: {
          allLeaderboards,
          game,
          achievementSetClaims: [],
          aggregateCredits: createAggregateAchievementSetCredits(),
          backingGame: game,
          numLeaderboards: 2,
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.queryByText('Test Achievement 1')).not.toBeInTheDocument();
    expect(screen.queryByText('Test Achievement 2')).not.toBeInTheDocument();

    expect(screen.getByText('High Score')).toBeVisible();
    expect(screen.getByText('Speed Run')).toBeVisible();
  });

  it('given the user is authenticated and there are achievements, shows progress indicators', () => {
    // ARRANGE
    const game = createGame();

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
      <GameAchievementSet achievements={achievements} gameAchievementSet={gameAchievementSet} />,
      {
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'normal'],
          //
        ],
        pageProps: {
          game,
          achievementSetClaims: [],
          auth: { user: createAuthenticatedUser() },
          aggregateCredits: createAggregateAchievementSetCredits(),
          backingGame: game,
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.getAllByText(/0%/i)[0]).toBeVisible();
  });

  it('given the component has not hydrated yet, limits rendered achievements and shows a placeholder', () => {
    // ARRANGE
    vi.spyOn(useIsHydratedModule, 'useIsHydrated').mockReturnValue(false); // we're in SSR

    const game = createGame();
    const achievements = Array.from({ length: 30 }, (_, i) =>
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
      <GameAchievementSet achievements={achievements} gameAchievementSet={gameAchievementSet} />,
      {
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'normal'],
          //
        ],
        pageProps: {
          game,
          achievementSetClaims: [],
          aggregateCredits: createAggregateAchievementSetCredits(),
          backingGame: game,
        },
      },
    );

    // ASSERT
    // ... only the first 20 achievements should be rendered ...
    expect(screen.getByText('Achievement 1')).toBeVisible();
    expect(screen.getByText('Achievement 20')).toBeVisible();
    expect(screen.queryByText('Achievement 21')).not.toBeInTheDocument();
    expect(screen.queryByText('Achievement 30')).not.toBeInTheDocument();

    // ... and there should be an invisible placeholder for the remaining 10 achievements ...
    const placeholder = screen.getByTestId('invisible-placeholder');
    expect(placeholder).toBeInTheDocument();
    expect(placeholder).toHaveStyle({ height: '950px' }); // (10 * 96) - 10 = 950
  });

  it('given the component has not hydrated yet but there are 20 or fewer achievements, does not show a placeholder', () => {
    // ARRANGE
    vi.spyOn(useIsHydratedModule, 'useIsHydrated').mockReturnValue(false); // we're in SSR

    const game = createGame();
    const achievements = Array.from({ length: 15 }, (_, i) =>
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
      <GameAchievementSet achievements={achievements} gameAchievementSet={gameAchievementSet} />,
      {
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'normal'],
          //
        ],
        pageProps: {
          game,
          achievementSetClaims: [],
          aggregateCredits: createAggregateAchievementSetCredits(),
          backingGame: game,
        },
      },
    );

    // ASSERT
    // ... all 15 achievements should be rendered since they fit within the SSR limit ...
    expect(screen.getByText('Achievement 1')).toBeVisible();
    expect(screen.getByText('Achievement 15')).toBeVisible();

    // ... no placeholder needed since all achievements are rendered ...
    expect(screen.queryByTestId('invisible-placeholder')).not.toBeInTheDocument();
  });

  it('given a leaderboard view, shows active and disabled leaderboards with separator', () => {
    // ARRANGE
    const game = createGame();
    const achievements = [createAchievement()];
    const allLeaderboards = [
      createLeaderboard({ id: 1, title: 'Active Leaderboard 1', state: 'active' }),
      createLeaderboard({ id: 2, title: 'Active Leaderboard 2', state: 'active' }),
      createLeaderboard({ id: 3, title: 'Disabled Leaderboard', state: 'disabled' }),
    ];
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ achievements }),
    });

    render(
      <GameAchievementSet achievements={achievements} gameAchievementSet={gameAchievementSet} />,
      {
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'normal'],
          [currentListViewAtom, 'leaderboards'],
        ],
        pageProps: {
          allLeaderboards,
          game,
          numLeaderboards: 3,
          achievementSetClaims: [],
          aggregateCredits: createAggregateAchievementSetCredits(),
          backingGame: game,
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    // ... active and disabled leaderboards should be visible with separator ...
    expect(screen.getByText('Active Leaderboard 1')).toBeVisible();
    expect(screen.getByText('Active Leaderboard 2')).toBeVisible();
    expect(screen.getByText('Disabled Leaderboard')).toBeVisible();

    expect(screen.getByTestId('disabled-separator')).toBeVisible();
  });

  it('given a leaderboard view, shows unpublished leaderboards without disabled leaderboards or separator', () => {
    // ARRANGE
    const game = createGame();
    const achievements = [createAchievement()];
    const allLeaderboards = [
      createLeaderboard({ id: 1, title: 'Unpublished Leaderboard 1', state: 'unpublished' }),
      createLeaderboard({ id: 2, title: 'Unpublished Leaderboard 2', state: 'unpublished' }),
      createLeaderboard({ id: 3, title: 'Disabled Leaderboard', state: 'disabled' }),
    ];
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ achievements }),
    });

    render(
      <GameAchievementSet achievements={achievements} gameAchievementSet={gameAchievementSet} />,
      {
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'normal'],
          [currentListViewAtom, 'leaderboards'],
        ],
        pageProps: {
          allLeaderboards,
          game,
          numLeaderboards: 3,
          achievementSetClaims: [],
          aggregateCredits: createAggregateAchievementSetCredits(),
          backingGame: game,
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    // ... only unpublished leaderboards should be visible with no separator ...
    expect(screen.getByText('Unpublished Leaderboard 1')).toBeVisible();
    expect(screen.getByText('Unpublished Leaderboard 2')).toBeVisible();
    expect(screen.queryByText('Disabled Leaderboard')).not.toBeInTheDocument();

    expect(screen.queryByTestId('disabled-separator')).not.toBeInTheDocument();
  });

  it('given a leaderboard view, shows only disabled leaderboards when no active ones exist', () => {
    // ARRANGE
    const game = createGame();
    const achievements = [createAchievement()];
    const allLeaderboards = [
      createLeaderboard({ id: 1, title: 'Disabled Leaderboard 1', state: 'disabled' }),
      createLeaderboard({ id: 2, title: 'Disabled Leaderboard 2', state: 'disabled' }),
    ];
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ achievements }),
    });

    render(
      <GameAchievementSet achievements={achievements} gameAchievementSet={gameAchievementSet} />,
      {
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'normal'],
          [currentListViewAtom, 'leaderboards'],
        ],
        pageProps: {
          allLeaderboards,
          game,
          numLeaderboards: 2,
          achievementSetClaims: [],
          aggregateCredits: createAggregateAchievementSetCredits(),
          backingGame: game,
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    // ... only disabled leaderboards should be visible with no separator ...
    expect(screen.getByText('Disabled Leaderboard 1')).toBeVisible();
    expect(screen.getByText('Disabled Leaderboard 2')).toBeVisible();

    expect(screen.queryByTestId('disabled-separator')).not.toBeInTheDocument();
  });
});
