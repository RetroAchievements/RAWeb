import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import {
  createAchievement,
  createAchievementSet,
  createGame,
  createGameAchievementSet,
  createZiggyProps,
} from '@/test/factories';

import { GameAchievementSetProgress } from './GameAchievementSetProgress';

describe('Component: GameAchievementSetProgress', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <GameAchievementSetProgress
        achievements={[]}
        gameAchievementSet={createGameAchievementSet()}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is not authenticated, renders nothing', () => {
    // ARRANGE
    render(
      <GameAchievementSetProgress
        achievements={[]}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: null, // !!
          game: createGame({ id: 1 }),
          backingGame: createGame({ id: 2 }),
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.queryByText(/mastered/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/beaten/i)).not.toBeInTheDocument();
  });

  it('given the achievement set is a subset, does not show the beaten indicator', () => {
    // ARRANGE
    render(
      <GameAchievementSetProgress
        achievements={[]}
        gameAchievementSet={createGameAchievementSet()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          game: createGame({ id: 1 }),
          backingGame: createGame({ id: 2 }), // !!
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.queryByText(/beaten/i)).not.toBeInTheDocument();
  });

  it('given the achievement set is not a subset, shows the beaten and playtime indicators', () => {
    // ARRANGE
    const achievements = [createAchievement(), createAchievement()];

    render(
      <GameAchievementSetProgress
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet({
          achievementSet: createAchievementSet({ achievements }),
        })}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          game: createGame({ id: 1 }),
          backingGame: createGame({ id: 1 }),
          isViewingPublishedAchievements: true,
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.getByText(/beaten/i)).toBeVisible();
    expect(screen.getByLabelText('No playtime recorded.')).toBeVisible();
  });

  it('does not show mastered or beaten indicators when viewing unpublished achievements', () => {
    // ARRANGE
    const achievements = [createAchievement(), createAchievement()];

    render(
      <GameAchievementSetProgress
        achievements={achievements}
        gameAchievementSet={createGameAchievementSet({
          achievementSet: createAchievementSet({ achievements }),
        })}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          game: createGame({ id: 1 }),
          backingGame: createGame({ id: 1 }),
          isViewingPublishedAchievements: false,
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.queryByText(/mastered/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/beaten/i)).not.toBeInTheDocument();
    expect(screen.getByLabelText('No playtime recorded.')).toBeVisible();
  });

  it('does not show mastered or beaten indicators when the achievement set has no achievements', () => {
    // ARRANGE
    render(
      <GameAchievementSetProgress
        achievements={[]}
        gameAchievementSet={createGameAchievementSet({
          achievementSet: createAchievementSet({ achievements: [] }),
        })}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          game: createGame({ id: 1 }),
          backingGame: createGame({ id: 1 }),
          isViewingPublishedAchievements: true,
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.queryByText(/mastered/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/beaten/i)).not.toBeInTheDocument();
    expect(screen.getByLabelText('No playtime recorded.')).toBeVisible();
  });
});
