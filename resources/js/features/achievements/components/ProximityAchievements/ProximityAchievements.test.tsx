import { route } from 'ziggy-js';

import { render, screen } from '@/test';
import {
  createAchievement,
  createAchievementSet,
  createGame,
  createGameAchievementSet,
} from '@/test/factories';

import { ProximityAchievements } from './ProximityAchievements';

describe('Component: ProximityAchievements', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 1 }),
      createAchievement({ id: 2 }),
      createAchievement({ id: 3 }),
      createAchievement({ id: 4 }),
    ];

    const { container } = render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 10,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no proximity achievements, renders nothing', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame() });

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements: null,
        promotedAchievementCount: 0,
      },
    });

    // ASSERT
    expect(screen.queryByRole('heading', { name: /more from this set/i })).not.toBeInTheDocument();
  });

  it('given an empty proximity achievements list, renders nothing', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame() });

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements: [],
        promotedAchievementCount: 0,
      },
    });

    // ASSERT
    expect(screen.queryByRole('heading', { name: /more from this set/i })).not.toBeInTheDocument();
  });

  it('shows the heading label', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame() });
    const proximityAchievements = [createAchievement({ id: 1 }), createAchievement({ id: 2 })];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 10,
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /more from this set/i })).toBeVisible();
  });

  it('renders all proximity achievement titles', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 1, title: 'Alpha Achievement' }),
      createAchievement({ id: 2, title: 'Beta Achievement' }),
      createAchievement({ id: 3, title: 'Gamma Achievement' }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 20,
      },
    });

    // ASSERT
    expect(screen.getByText('Alpha Achievement')).toBeVisible();
    expect(screen.getByText('Beta Achievement')).toBeVisible();
    expect(screen.getByText('Gamma Achievement')).toBeVisible();
  });

  it('renders each achievement as a link', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 1, title: 'Alpha Achievement' }),
      createAchievement({ id: 2, title: 'Beta Achievement' }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 10,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /alpha achievement/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /beta achievement/i })).toBeVisible();
  });

  it('shows a gray checkmark for softcore-unlocked achievements', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1, game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 2, title: 'Locked One' }),
      createAchievement({
        id: 3,
        title: 'Softcore One',
        unlockedAt: '2024-01-15T00:00:00Z',
        unlockedHardcoreAt: undefined,
      }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 10,
      },
    });

    // ASSERT
    const checkIcon = screen.getByTestId('unlock-check-3');
    expect(checkIcon).toBeVisible();
    expect(checkIcon.className).toContain('text-neutral-400');
    expect(checkIcon).toHaveAttribute('aria-label', 'Unlocked');
  });

  it('shows a gold checkmark for hardcore-unlocked achievements', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1, game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 2, title: 'Locked One' }),
      createAchievement({
        id: 3,
        title: 'Hardcore One',
        unlockedAt: '2024-01-15T00:00:00Z',
        unlockedHardcoreAt: '2024-01-15T00:00:00Z',
      }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 10,
      },
    });

    // ASSERT
    const checkIcon = screen.getByTestId('unlock-check-3');
    expect(checkIcon).toBeVisible();
    expect(checkIcon.className).toContain('text-[gold]');
    expect(checkIcon).toHaveAttribute('aria-label', 'Unlocked in hardcore');
  });

  it('does not show a checkmark for locked achievements', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1, game: createGame() });
    const proximityAchievements = [
      createAchievement({
        id: 2,
        title: 'Locked One',
        unlockedAt: undefined,
        unlockedHardcoreAt: undefined,
      }),
      createAchievement({ id: 3, title: 'Another Locked' }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 10,
      },
    });

    // ASSERT
    expect(screen.queryByTestId('unlock-check-2')).not.toBeInTheDocument();
  });

  it('shows "View all N achievements" with the correct count', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame({ id: 100 }) });
    const proximityAchievements = [createAchievement({ id: 1 }), createAchievement({ id: 2 })];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 35,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /view all 35 achievements/i })).toBeVisible();
  });

  it('given a subset backing game, links "View all" to the backing game with the set param', () => {
    // ARRANGE
    const backingGame = createGame({ id: 200 });
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ id: 300 }),
    });
    const achievement = createAchievement({ game: createGame({ id: 999 }) });
    const proximityAchievements = [createAchievement({ id: 1 }), createAchievement({ id: 2 })];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 15,
        backingGame,
        gameAchievementSet,
      },
    });

    // ASSERT
    expect(vi.mocked(route)).toHaveBeenCalledWith('game.show', {
      game: 200,
      _query: { set: 300 },
    });
  });

  it('given promotedAchievementCount is zero, does not show the "View all" link', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame() });
    const proximityAchievements = [createAchievement({ id: 1 }), createAchievement({ id: 2 })];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 0,
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /view all/i })).not.toBeInTheDocument();
  });

  it('given no backing game, links "View all" to the right game page', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame({ id: 500 }) });
    const proximityAchievements = [createAchievement({ id: 1 }), createAchievement({ id: 2 })];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 20,
      },
    });

    // ASSERT
    expect(vi.mocked(route)).toHaveBeenCalledWith('game.show', { game: 500 });
  });

  it('shows points and unlock percentage per item', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 1, title: 'Test Ach', points: 25, unlockPercentage: '0.852' }),
      createAchievement({ id: 2, points: 10 }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 10,
      },
    });

    // ASSERT
    expect(screen.getByText(/25 points/)).toBeVisible();
    expect(screen.getByText(/85\.2%/)).toBeVisible();
  });

  it('given an event game where all achievements are one point, hides the points display', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 1, title: 'Test Ach', points: 1, unlockPercentage: '0.239' }),
      createAchievement({ id: 2, points: 1 }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 10,
        isEventGame: true,
        areAllAchievementsOnePoint: true,
      },
    });

    // ASSERT
    expect(screen.queryByText(/1 point/)).not.toBeInTheDocument();
    expect(screen.getByText(/23\.9%/)).toBeVisible();
  });

  it('given an event game with a 0% unlock rate achievement, hides the percentage for that achievement', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 1, title: 'Active Ach', points: 1, unlockPercentage: '0.457' }),
      createAchievement({ id: 2, title: 'Upcoming Ach', points: 1, unlockPercentage: '0' }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 10,
        isEventGame: true,
        areAllAchievementsOnePoint: true,
      },
    });

    // ASSERT
    expect(screen.getByText(/45\.7%/)).toBeVisible();
    expect(screen.queryByText(/0\.0%/)).not.toBeInTheDocument();
  });

  it('given the achievement is for an event game, shows "More from this event" heading', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame() });
    const proximityAchievements = [createAchievement({ id: 1 }), createAchievement({ id: 2 })];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 5,
        isEventGame: true,
      },
    });

    // ASSERT
    expect(screen.getByText('More from this event')).toBeVisible();
  });
});
