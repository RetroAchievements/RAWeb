/* eslint-disable testing-library/no-node-access -- need to test explicit classnames */

import userEvent from '@testing-library/user-event';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import {
  createAchievement,
  createAchievementSet,
  createGame,
  createGameAchievementSet,
  createPlayerAchievementSet,
  createPlayerBadge,
  createZiggyProps,
} from '@/test/factories';

import { MasteredProgressIndicator } from './MasteredProgressIndicator';

describe('Component: MasteredProgressIndicator', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <MasteredProgressIndicator
        achievements={[]}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame({ id: 1 }),
          game: createGame({ id: 1 }),
          playerAchievementSets: {},
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is not authenticated, renders nothing', () => {
    // ARRANGE
    render(
      <MasteredProgressIndicator
        achievements={[]}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: null,
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {},
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.queryByText(/\d+%/)).not.toBeInTheDocument();
  });

  it('given the user has unlocked all achievements in hardcore, shows 100%', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
    ];

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {},
          playerGame: { achievementsUnlocked: 2 }, // !!
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.getByText('100%')).toBeVisible();
  });

  it('given the user has partial progress, shows the correct percentage', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
    ];

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {},
          playerGame: { achievementsUnlocked: 2 }, // !! 2 out of 3 = 67% (ceiled)
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.getByText('67%')).toBeVisible();
  });

  it('given the device is desktop, hovering shows the tooltip with progress information', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
    ];

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {},
          playerGame: { achievementsUnlocked: 2 }, // !! 2 out of 3 = 67%
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps({ device: 'desktop' }),
        },
      },
    );

    // ACT
    await userEvent.hover(screen.getByText('67%'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/overall set progress/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText('2/3')[0]).toBeVisible();
  });

  it('given the device is mobile, tapping shows the popover with progress information', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
    ];

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {},
          playerGame: { achievementsUnlocked: 2 }, // !! 2 out of 3 = 67%
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps({ device: 'mobile' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByText('67%'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/overall set progress/i)).toBeVisible();
    });
    expect(screen.getByText('2/3')).toBeVisible();
  });

  it('given all achievements are unlocked in hardcore, shows only hardcore progress', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
    ];

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {},
          playerGame: { achievementsUnlocked: 3 }, // !!
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps(),
        },
      },
    );

    // ACT
    await userEvent.hover(screen.getByText('100%'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/overall set progress/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText('3/3')[0]).toBeVisible();

    // ... should not show the breakdown since there's no mixed progress ...
    expect(screen.queryByText(/hardcore/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/softcore/i)).not.toBeInTheDocument();
  });

  it('given all achievements are unlocked in softcore only, shows only softcore progress', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: undefined }),
    ];

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {},
          playerGame: { achievementsUnlocked: 3 }, // !!
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps(),
        },
      },
    );

    // ACT
    await userEvent.hover(screen.getByText('100%'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/overall set progress/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText('3/3')[0]).toBeVisible();

    // ... should not show the breakdown since there's no mixed progress ...
    expect(screen.queryByText(/hardcore/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/softcore/i)).not.toBeInTheDocument();
  });

  it('given a mix of hardcore and softcore achievements, shows mixed progress labels', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
    ];

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {},
          playerGame: { achievementsUnlocked: 3 }, // !!
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps(),
        },
      },
    );

    // ACT
    await userEvent.hover(screen.getByText('75%')); // !! 3 out of 4

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/overall set progress/i)[0]).toBeVisible();
    });

    // ... total progress ...
    expect(screen.getAllByText('3/4')[0]).toBeVisible();

    // ... mixed progress should show both ...
    expect(screen.getAllByText(/hardcore/i)[0]).toBeVisible();
    expect(screen.getAllByText(/softcore/i)[0]).toBeVisible();
  });

  it('given no achievements are unlocked, shows 0 progress', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
    ];

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {},
          playerGame: { achievementsUnlocked: 0 }, // !!
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps(),
        },
      },
    );

    // ACT
    await userEvent.hover(screen.getByText('0%'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/overall set progress/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText('0/2')[0]).toBeVisible();
  });

  it('renders a progress bar in the tooltip content', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
    ];

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {},
          playerGame: { achievementsUnlocked: 2 }, // !!
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps(),
        },
      },
    );

    // ACT
    await userEvent.hover(screen.getByText('67%'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/overall set progress/i)[0]).toBeVisible();
    });

    expect(screen.getAllByRole('progressbar')[0]).toBeVisible();
  });

  it('given the user has progress, displays a Manage progress button', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
    ];

    const { container } = render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {},
          playerGame: { achievementsUnlocked: 2 }, // !!
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps(),
        },
      },
    );

    // ACT
    await userEvent.hover(screen.getByText('67%'));

    await waitFor(() => {
      expect(screen.getAllByText(/overall set progress/i)[0]).toBeVisible();
    });

    await userEvent.click(screen.getAllByRole('button', { name: /manage progress/i })[0]);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user has a mastery award, shows amber color styling', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
    ];

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {},
          playerGame: { achievementsUnlocked: 2 }, // !!
          playerGameProgressionAwards: {
            mastered: createPlayerBadge(), // !!
            completed: null,
            beatenHardcore: null,
            beatenSoftcore: null,
          },
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    const masteredElement = screen.getByText('100%').parentElement;
    expect(masteredElement).toHaveClass('text-amber-400');
  });

  it('given the user has a completion award but not mastered, shows silver color styling', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: undefined }),
    ];

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {},
          playerGame: { achievementsUnlocked: 2 }, // !!
          playerGameProgressionAwards: {
            mastered: null,
            completed: createPlayerBadge(), // !!
            beatenHardcore: null,
            beatenSoftcore: null,
          },
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    const completedElement = screen.getByText('100%').parentElement;
    expect(completedElement).toHaveClass('text-neutral-200');
  });

  it('given the user has no awards, shows faded color styling', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
    ];

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {},
          playerGame: { achievementsUnlocked: 1 }, // !!
          playerGameProgressionAwards: {
            mastered: null, // !!
            completed: null, // !!
            beatenHardcore: null,
            beatenSoftcore: null,
          },
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    const labelElement = screen.getByText('50%').parentElement; // !! 1 out of 2 = 50%
    expect(labelElement).toHaveClass('text-neutral-300/30');
  });

  it('given the user is on mobile and has any softcore unlocks, shows the "Completed" label', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
    ];

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {},
          playerGame: { achievementsUnlocked: 3 }, // !!
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps({ device: 'mobile' }), // !!
        },
      },
    );

    // ASSERT
    expect(screen.getByText('100%')).toBeVisible();
  });

  it('given the user is on mobile and only hardcore unlocks, shows 100%', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
    ];

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame({ id: 1 }),
          game: createGame({ id: 1 }),
          playerAchievementSets: {},
          playerGame: { achievementsUnlocked: 2 }, // !!
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps({ device: 'mobile' }), // !!
        },
      },
    );

    // ASSERT
    expect(screen.getByText('100%')).toBeVisible();
  });

  it('given rawPercentage is 99.5%, shows 99% (Math.floor)', () => {
    // ARRANGE
    const achievements = Array.from({ length: 200 }, () =>
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
    );
    // ... unlock 199 out of 200 achievements for exactly 99.5% ...
    for (let i = 0; i < 199; i++) {
      achievements[i] = createAchievement({ unlockedAt: '2024-01-01T00:00:00Z' });
    }

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {},
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.getByText('99%')).toBeVisible();
  });

  it('given rawPercentage is 98.5%, shows 99% (Math.ceil)', () => {
    // ARRANGE
    const achievements = Array.from({ length: 200 }, () =>
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
    );
    // ... unlock 197 out of 200 achievements for exactly 98.5% ...
    for (let i = 0; i < 197; i++) {
      achievements[i] = createAchievement({ unlockedAt: '2024-01-01T00:00:00Z' });
    }

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {},
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.getByText('99%')).toBeVisible();
  });

  it('given the set is mastered, shows the mastery date and time', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
    ];

    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ id: 123 }),
    });

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={gameAchievementSet}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {
            123: createPlayerAchievementSet({
              completedAt: null,
              completedHardcoreAt: '2024-12-28T04:37:26.000000Z', // !!
              timeTaken: null,
              timeTakenHardcore: 11442, // !!
            }),
          },
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps(),
        },
      },
    );

    // ACT
    await userEvent.hover(screen.getByText('100%'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/mastered on/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText(/time to master/i)[0]).toBeVisible();
    expect(screen.getAllByText(/dec 28, 2024/i)[0]).toBeVisible();
    expect(screen.getAllByText(/3h 10m/i)[0]).toBeVisible();
  });

  it('given the set is completed in softcore only, shows the completion date and time', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: undefined }),
    ];

    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ id: 456 }),
    });

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={gameAchievementSet}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {
            456: createPlayerAchievementSet({
              completedAt: '2024-05-15T14:30:00.000000Z', // !!
              completedHardcoreAt: null,
              timeTaken: 7200, // !!
              timeTakenHardcore: null,
            }),
          },
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps(),
        },
      },
    );

    // ACT
    await userEvent.hover(screen.getByText('100%'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/completed on/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText(/time to complete/i)[0]).toBeVisible();
    expect(screen.getAllByText(/may 15, 2024/i)[0]).toBeVisible();
    expect(screen.getAllByText(/2h/i)[0]).toBeVisible();
  });

  it('given the set has both completion and mastery, prioritizes mastery stats', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
    ];

    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ id: 789 }),
    });

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={gameAchievementSet}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {
            789: createPlayerAchievementSet({
              completedAt: '2024-05-15T14:30:00.000000Z',
              completedHardcoreAt: '2024-05-16T18:45:00.000000Z',
              timeTaken: 5000,
              timeTakenHardcore: 6000,
            }),
          },
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps(),
        },
      },
    );

    // ACT
    await userEvent.hover(screen.getByText('100%'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/mastered on/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText(/time to master/i)[0]).toBeVisible();
    expect(screen.queryByText(/completed on/i)).not.toBeInTheDocument();
  });

  it('given the set is not completed, does not show completion stats', async () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
    ];

    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ id: 999 }),
    });

    render(
      <MasteredProgressIndicator
        achievements={achievements}
        gameAchievementSet={gameAchievementSet}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          game: createGame(),
          playerAchievementSets: {}, // !!
          playerGameProgressionAwards: {},
          ziggy: createZiggyProps(),
        },
      },
    );

    // ACT
    await userEvent.hover(screen.getByText('50%'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/overall set progress/i)[0]).toBeVisible();
    });
    expect(screen.queryByText(/mastered on/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/completed on/i)).not.toBeInTheDocument();
  });
});
