/* eslint-disable testing-library/no-node-access -- need to test explicit classnames */

import userEvent from '@testing-library/user-event';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import {
  createAchievement,
  createGame,
  createPlayerBadge,
  createZiggyProps,
} from '@/test/factories';

import { MasteredProgressIndicator } from './MasteredProgressIndicator';

describe('Component: MasteredProgressIndicator', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<MasteredProgressIndicator achievements={[]} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame({ id: 1 }),
        game: createGame({ id: 1 }),
        playerGameProgressionAwards: {},
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is not authenticated, renders nothing', () => {
    // ARRANGE
    render(<MasteredProgressIndicator achievements={[]} />, {
      pageProps: {
        auth: null,
        backingGame: createGame(),
        game: createGame(),
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByText(/\d+%/)).not.toBeInTheDocument();
  });

  it('given the user has unlocked all achievements in hardcore, shows 100%', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
    ];

    render(<MasteredProgressIndicator achievements={achievements} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        game: createGame(),
        playerGame: { achievementsUnlocked: 2 }, // !!
        playerGameProgressionAwards: {},
        ziggy: createZiggyProps(),
      },
    });

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

    render(<MasteredProgressIndicator achievements={achievements} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        game: createGame(),
        playerGame: { achievementsUnlocked: 2 }, // !! 2 out of 3 = 67% (ceiled)
        playerGameProgressionAwards: {},
        ziggy: createZiggyProps(),
      },
    });

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

    render(<MasteredProgressIndicator achievements={achievements} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        game: createGame(),
        playerGame: { achievementsUnlocked: 2 }, // !! 2 out of 3 = 67%
        playerGameProgressionAwards: {},
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

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

    render(<MasteredProgressIndicator achievements={achievements} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        game: createGame(),
        playerGame: { achievementsUnlocked: 2 }, // !! 2 out of 3 = 67%
        playerGameProgressionAwards: {},
        ziggy: createZiggyProps({ device: 'mobile' }),
      },
    });

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

    render(<MasteredProgressIndicator achievements={achievements} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        game: createGame(),
        playerGame: { achievementsUnlocked: 3 }, // !!
        playerGameProgressionAwards: {},
        ziggy: createZiggyProps(),
      },
    });

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

    render(<MasteredProgressIndicator achievements={achievements} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        game: createGame(),
        playerGame: { achievementsUnlocked: 3 }, // !!
        playerGameProgressionAwards: {},
        ziggy: createZiggyProps(),
      },
    });

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

    render(<MasteredProgressIndicator achievements={achievements} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        game: createGame(),
        playerGame: { achievementsUnlocked: 3 }, // !!
        playerGameProgressionAwards: {},
        ziggy: createZiggyProps(),
      },
    });

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

    render(<MasteredProgressIndicator achievements={achievements} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        game: createGame(),
        playerGame: { achievementsUnlocked: 0 }, // !!
        playerGameProgressionAwards: {},
        ziggy: createZiggyProps(),
      },
    });

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

    render(<MasteredProgressIndicator achievements={achievements} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        game: createGame(),
        playerGame: { achievementsUnlocked: 2 }, // !!
        playerGameProgressionAwards: {},
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.hover(screen.getByText('67%'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/overall set progress/i)[0]).toBeVisible();
    });

    expect(screen.getAllByRole('progressbar')[0]).toBeVisible();
  });

  it('given the user has a mastery award, shows amber color styling', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
    ];

    render(<MasteredProgressIndicator achievements={achievements} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        game: createGame(),
        playerGame: { achievementsUnlocked: 2 }, // !!
        playerGameProgressionAwards: {
          mastered: createPlayerBadge(), // !!
          completed: null,
          beatenHardcore: null,
          beatenSoftcore: null,
        },
        ziggy: createZiggyProps(),
      },
    });

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

    render(<MasteredProgressIndicator achievements={achievements} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        game: createGame(),
        playerGame: { achievementsUnlocked: 2 }, // !!
        playerGameProgressionAwards: {
          mastered: null,
          completed: createPlayerBadge(), // !!
          beatenHardcore: null,
          beatenSoftcore: null,
        },
        ziggy: createZiggyProps(),
      },
    });

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

    render(<MasteredProgressIndicator achievements={achievements} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        game: createGame(),
        playerGame: { achievementsUnlocked: 1 }, // !!
        playerGameProgressionAwards: {
          mastered: null, // !!
          completed: null, // !!
          beatenHardcore: null,
          beatenSoftcore: null,
        },
        ziggy: createZiggyProps(),
      },
    });

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

    render(<MasteredProgressIndicator achievements={achievements} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        game: createGame(),
        playerGame: { achievementsUnlocked: 3 }, // !!
        playerGameProgressionAwards: {},
        ziggy: createZiggyProps({ device: 'mobile' }), // !!
      },
    });

    // ASSERT
    expect(screen.getByText('100%')).toBeVisible();
  });

  it('given the user is on mobile and only hardcore unlocks, shows 100%', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
    ];

    render(<MasteredProgressIndicator achievements={achievements} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame({ id: 1 }),
        game: createGame({ id: 1 }),
        playerGame: { achievementsUnlocked: 2 }, // !!
        playerGameProgressionAwards: {},
        ziggy: createZiggyProps({ device: 'mobile' }), // !!
      },
    });

    // ASSERT
    expect(screen.getByText('100%')).toBeVisible();
  });
});
