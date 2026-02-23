import { render, screen } from '@/test';
import { createAchievement, createAchievementRecentUnlock, createUser } from '@/test/factories';

import { AchievementRecentUnlocks } from './AchievementRecentUnlocks';

describe('Component: AchievementRecentUnlocks', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AchievementRecentUnlocks />, {
      pageProps: {
        achievement: createAchievement({ unlocksTotal: 0 }),
        recentUnlocks: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given recentUnlocks is undefined, renders placeholder rows matching the count', () => {
    // ARRANGE
    render(<AchievementRecentUnlocks />, {
      pageProps: {
        achievement: createAchievement({ unlocksTotal: 3 }),
        recentUnlocks: undefined,
      },
    });

    // ASSERT
    expect(screen.getAllByRole('row')).toHaveLength(4); // 1 header + 3 placeholders
  });

  it('given unlocksTotal is undefined, treats it as 0 and shows an empty state message', () => {
    // ARRANGE
    render(<AchievementRecentUnlocks />, {
      pageProps: {
        achievement: createAchievement({ unlocksTotal: undefined }),
      },
    });

    // ASSERT
    expect(screen.getByText(/no unlocks found/i)).toBeVisible();
  });

  it('given unlocksTotal is 0, shows an empty state message without waiting for the deferred prop', () => {
    // ARRANGE
    render(<AchievementRecentUnlocks />, {
      pageProps: {
        achievement: createAchievement({ unlocksTotal: 0 }),
      },
    });

    // ASSERT
    expect(screen.getByText(/no unlocks found/i)).toBeVisible();
  });

  it('given recentUnlocks resolves to an empty array, shows an empty state message', () => {
    // ARRANGE
    render(<AchievementRecentUnlocks />, {
      pageProps: {
        achievement: createAchievement({ unlocksTotal: 5 }),
        recentUnlocks: [],
      },
    });

    // ASSERT
    expect(screen.getByText(/no unlocks found/i)).toBeVisible();
  });

  it('given an unlock with isHardcore true, displays "Hardcore"', () => {
    // ARRANGE
    const unlock = createAchievementRecentUnlock({ isHardcore: true });

    render(<AchievementRecentUnlocks />, {
      pageProps: {
        achievement: createAchievement({ unlocksTotal: 1 }),
        recentUnlocks: [unlock],
      },
    });

    // ASSERT
    expect(screen.getByText('Hardcore')).toBeVisible();
  });

  it('given an unlock with isHardcore false, does not display any mode label', () => {
    // ARRANGE
    const unlock = createAchievementRecentUnlock({ isHardcore: false });

    render(<AchievementRecentUnlocks />, {
      pageProps: {
        achievement: createAchievement({ unlocksTotal: 1 }),
        recentUnlocks: [unlock],
      },
    });

    // ASSERT
    expect(screen.queryByText('Softcore')).not.toBeInTheDocument();
  });

  it('renders the user display name as a link', () => {
    // ARRANGE
    const user = createUser({ displayName: 'Scott' });
    const unlock = createAchievementRecentUnlock({ user });

    render(<AchievementRecentUnlocks />, {
      pageProps: {
        achievement: createAchievement({ unlocksTotal: 1 }),
        recentUnlocks: [unlock],
      },
    });

    // ASSERT
    const link = screen.getByRole('link', { name: /scott/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', expect.stringContaining('Scott'));
  });

  it('renders the unlock timestamp', () => {
    // ARRANGE
    const unlock = createAchievementRecentUnlock({
      unlockedAt: new Date('2026-02-06T14:32:00Z').toISOString(),
      isHardcore: false,
    });

    render(<AchievementRecentUnlocks />, {
      pageProps: {
        achievement: createAchievement({ unlocksTotal: 1 }),
        recentUnlocks: [unlock],
      },
    });

    // ASSERT
    expect(screen.getByText(/Feb 6, 2026/i)).toBeVisible();
  });
});
