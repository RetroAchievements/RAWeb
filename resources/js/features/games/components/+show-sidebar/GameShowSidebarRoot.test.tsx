import { faker } from '@faker-js/faker';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import {
  createAchievement,
  createAchievementSet,
  createGame,
  createGameAchievementSet,
  createGameSet,
  createSeriesHub,
  createSystem,
} from '@/test/factories';

import { GameShowSidebarRoot } from './GameShowSidebarRoot';

describe('Component: GameShowSidebarRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: 'badge.jpg',
      imageBoxArtUrl: faker.internet.url(),
      imageTitleUrl: faker.internet.url(),
      imageIngameUrl: faker.internet.url(),
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
      gameAchievementSets: [
        createGameAchievementSet({
          achievementSet: createAchievementSet(),
        }),
      ],
    });

    const { container } = render(<GameShowSidebarRoot />, {
      pageProps: {
        game,
        backingGame: game,
        can: {},
        hubs: [createGameSet()],
        playerAchievementChartBuckets: [],
        topAchievers: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the game has a series hub, renders the series hub component', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [
        createGameAchievementSet({
          achievementSet: createAchievementSet({ achievements: [] }),
        }),
      ],
    });
    const seriesHub = createSeriesHub();

    render(<GameShowSidebarRoot />, {
      pageProps: {
        game,
        backingGame: game,
        seriesHub, // !!
        can: {},
        hubs: [],
        playerAchievementChartBuckets: [],
        topAchievers: [],
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /series/i }));
  });

  it('given the game does not have a series hub, does not render the series hub component', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [
        createGameAchievementSet({
          achievementSet: createAchievementSet({ achievements: [] }),
        }),
      ],
    });

    render(<GameShowSidebarRoot />, {
      pageProps: {
        game,
        backingGame: game,
        seriesHub: null, // !!
        can: {},
        hubs: [],
        playerAchievementChartBuckets: [],
        topAchievers: [],
      },
    });

    // ASSERT
    expect(screen.queryByRole('heading', { name: /series/i })).not.toBeInTheDocument();
  });

  it('given the game is flagged for having mature content, shows an indicator', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [
        createGameAchievementSet({
          achievementSet: createAchievementSet({ achievements: [] }),
        }),
      ],
    });

    render(<GameShowSidebarRoot />, {
      pageProps: {
        game,
        backingGame: game,
        can: {},
        hasMatureContent: true, // !!
        hubs: [],
        playerAchievementChartBuckets: [],
        topAchievers: [],
      },
    });

    // ASSERT
    expect(screen.getByText(/mature content/i)).toBeVisible();
  });

  it('given the game is not flagged for having mature content, does not show an indicator', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [
        createGameAchievementSet({
          achievementSet: createAchievementSet({ achievements: [] }),
        }),
      ],
    });

    render(<GameShowSidebarRoot />, {
      pageProps: {
        game,
        backingGame: game,
        can: {},
        hasMatureContent: false, // !!
        hubs: [],
        playerAchievementChartBuckets: [],
        topAchievers: [],
      },
    });

    // ASSERT
    expect(screen.queryByText(/mature content/i)).not.toBeInTheDocument();
  });

  it('given there are published achievements and the user is authenticated, shows the compare progress component', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [
        createGameAchievementSet({
          achievementSet: createAchievementSet({ achievements: [createAchievement()] }), // !!
        }),
      ],
    });

    render(<GameShowSidebarRoot />, {
      pageProps: {
        game,
        auth: { user: createAuthenticatedUser() },
        backingGame: game,
        can: {},
        followedPlayerCompletions: [],
        hasMatureContent: false, // !!
        hubs: [],
        playerAchievementChartBuckets: [],
        topAchievers: [],
      },
    });

    // ASSERT
    expect(screen.getByText(/compare progress/i)).toBeVisible();
  });
});
