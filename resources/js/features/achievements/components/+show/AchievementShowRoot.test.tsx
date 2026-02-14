import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createAchievement, createComment, createGame, createSystem } from '@/test/factories';

import { AchievementShowRoot } from './AchievementShowRoot';

describe('Component: AchievementShowRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    const { container } = render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays breadcrumbs with the achievement game when there is no backing game', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ title: 'Sonic the Hedgehog', system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    const breadcrumbNav = screen.getByRole('navigation', { name: /breadcrumb/i });
    expect(breadcrumbNav).toHaveTextContent(/sonic the hedgehog/i);
  });

  it('displays breadcrumbs with the backing game when the achievement belongs to a subset', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ title: 'Sonic the Hedgehog [Subset - Bonus]', system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    const backingGame = createGame({ title: 'Sonic the Hedgehog', system: createSystem() });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(screen.getAllByText(/sonic the hedgehog/i)[0]).toBeVisible();
  });

  it('defaults to showing the comments tab', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 2,
        recentVisibleComments: [createComment({ payload: 'Great achievement!' })],
      },
    });

    // ASSERT
    expect(screen.getByText(/great achievement!/i)).toBeVisible();
  });

  it('allows switching to the unlocks tab', async () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('tab', { name: /unlocks/i }));

    // ASSERT
    expect(screen.getByText(/AchievementRecentUnlocks/i)).toBeVisible();
  });

  it('allows switching to the changelog tab', async () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('tab', { name: /changelog/i }));

    // ASSERT
    expect(screen.getByText(/AchievementChangelog/i)).toBeVisible();
  });
});
