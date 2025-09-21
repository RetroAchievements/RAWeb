import { faker } from '@faker-js/faker';

import { render, screen } from '@/test';
import {
  createAchievement,
  createAchievementSet,
  createAggregateAchievementSetCredits,
  createGame,
  createGameAchievementSet,
  createGameSet,
  createSeriesHub,
  createSystem,
  createZiggyProps,
} from '@/test/factories';

import { currentTabAtom } from '../../state/games.atoms';
import { GameShowMobileRoot } from './GameShowMobileRoot';

describe('Component: GameShowMobileRoot', () => {
  beforeEach(() => {
    const mockIntersectionObserver = vi.fn();
    mockIntersectionObserver.mockReturnValue({
      observe: () => null,
      unobserve: () => null,
      disconnect: () => null,
    });
    window.IntersectionObserver = mockIntersectionObserver;
  });

  it('renders without crashing', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: 'badge.jpg',
      gameAchievementSets: [createGameAchievementSet({ achievementSet: createAchievementSet() })],
      imageBoxArtUrl: faker.internet.url(),
      imageTitleUrl: faker.internet.url(),
      imageIngameUrl: faker.internet.url(),
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
    });

    const { container } = render(<GameShowMobileRoot />, {
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hubs: [createGameSet()],
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: true,
        playerAchievementChartBuckets: [],
        recentPlayers: [],
        recentVisibleComments: [],
        topAchievers: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the game is missing required fields, renders nothing', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: undefined, // !!
      gameAchievementSets: [createGameAchievementSet({ achievementSet: createAchievementSet() })],
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
    });

    render(<GameShowMobileRoot />, {
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hubs: [],
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: true,
        playerAchievementChartBuckets: [],
        recentPlayers: [],
        recentVisibleComments: [],
        topAchievers: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByTestId('game-mobile')).not.toBeInTheDocument();
  });

  it('given the game has all required fields, renders the view', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: 'badge.jpg', // !!
      gameAchievementSets: [createGameAchievementSet({ achievementSet: createAchievementSet() })],
      imageBoxArtUrl: faker.internet.url(),
      imageTitleUrl: faker.internet.url(),
      imageIngameUrl: faker.internet.url(),
      system: createSystem({
        iconUrl: 'icon.jpg', // !!
      }),
    });

    render(<GameShowMobileRoot />, {
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hubs: [],
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: true,
        playerAchievementChartBuckets: [],
        recentPlayers: [],
        recentVisibleComments: [],
        topAchievers: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByTestId('game-mobile')).toBeVisible();
  });

  it('given the game page has a content warning, displays the content warning dialog', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: 'badge.jpg',
      gameAchievementSets: [createGameAchievementSet({ achievementSet: createAchievementSet() })],
      imageBoxArtUrl: faker.internet.url(),
      imageTitleUrl: faker.internet.url(),
      imageIngameUrl: faker.internet.url(),
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
    });

    render(<GameShowMobileRoot />, {
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hasMatureContent: true, // !!
        hubs: [],
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: true,
        recentPlayers: [],
        recentVisibleComments: [],
        topAchievers: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByRole('alertdialog', { name: /content warning/i })).toBeVisible();
  });

  it('given the game has no achievements, renders an empty state', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: 'badge.jpg',
      gameAchievementSets: [
        createGameAchievementSet({ achievementSet: createAchievementSet({ achievements: [] }) }), // !!
      ],
      imageBoxArtUrl: faker.internet.url(),
      imageTitleUrl: faker.internet.url(),
      imageIngameUrl: faker.internet.url(),
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
    });

    render(<GameShowMobileRoot />, {
      jotaiAtoms: [
        [currentTabAtom, 'achievements'],
        //
      ],
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hubs: [],
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: true,
        playerAchievementChartBuckets: [],
        recentPlayers: [],
        recentVisibleComments: [],
        setRequestData: {
          hasUserRequestedSet: false,
          totalRequests: 0,
          userRequestsRemaining: 0,
        },
        topAchievers: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByText(/no achievements yet/i)).toBeVisible();
  });

  it('given the game has achievements, does not render an empty state', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: 'badge.jpg',
      gameAchievementSets: [
        createGameAchievementSet({
          achievementSet: createAchievementSet({ achievements: [createAchievement()] }), // !!
        }),
      ],
      imageBoxArtUrl: faker.internet.url(),
      imageTitleUrl: faker.internet.url(),
      imageIngameUrl: faker.internet.url(),
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
      playersTotal: 100,
    });

    render(<GameShowMobileRoot />, {
      jotaiAtoms: [
        [currentTabAtom, 'achievements'],
        //
      ],
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hubs: [],
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: true,
        playerAchievementChartBuckets: [],
        recentPlayers: [],
        recentVisibleComments: [],
        setRequestData: {
          hasUserRequestedSet: false,
          totalRequests: 0,
          userRequestsRemaining: 0,
        },
        topAchievers: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByText(/no achievements yet/i)).not.toBeInTheDocument();
  });

  it('given the game has a series hub, shows the series hub display in the info tab', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: 'badge.jpg',
      gameAchievementSets: [
        createGameAchievementSet({
          achievementSet: createAchievementSet({ achievements: [] }),
        }),
      ],
      imageBoxArtUrl: faker.internet.url(),
      imageTitleUrl: faker.internet.url(),
      imageIngameUrl: faker.internet.url(),
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
    });
    const seriesHub = createSeriesHub();

    render(<GameShowMobileRoot />, {
      jotaiAtoms: [
        [currentTabAtom, 'info'],
        //
      ],
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hubs: [],
        seriesHub, // !!
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: true,
        playerAchievementChartBuckets: [],
        recentPlayers: [],
        recentVisibleComments: [],
        topAchievers: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /series/i })).toBeVisible();
  });

  it('given the game does not have a series hub, does not show the series hub display', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: 'badge.jpg',
      gameAchievementSets: [
        createGameAchievementSet({
          achievementSet: createAchievementSet({ achievements: [] }),
        }),
      ],
      imageBoxArtUrl: faker.internet.url(),
      imageTitleUrl: faker.internet.url(),
      imageIngameUrl: faker.internet.url(),
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
    });

    render(<GameShowMobileRoot />, {
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hubs: [],
        seriesHub: null, // !!
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: true,
        playerAchievementChartBuckets: [],
        recentPlayers: [],
        recentVisibleComments: [],
        topAchievers: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('heading', { name: /series/i })).not.toBeInTheDocument();
  });

  it('given the user is viewing published achievements, shows the community tab', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: 'badge.jpg',
      gameAchievementSets: [
        createGameAchievementSet({
          achievementSet: createAchievementSet({ achievements: [] }),
        }),
      ],
      imageBoxArtUrl: faker.internet.url(),
      imageTitleUrl: faker.internet.url(),
      imageIngameUrl: faker.internet.url(),
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
    });

    render(<GameShowMobileRoot />, {
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hubs: [],
        seriesHub: null,
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: true, // !!
        playerAchievementChartBuckets: [],
        recentPlayers: [],
        recentVisibleComments: [],
        topAchievers: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByRole('tab', { name: /community/i })).toBeVisible();
  });

  it('given the user is not viewing published achievements, the community tab does not display', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: 'badge.jpg',
      gameAchievementSets: [
        createGameAchievementSet({
          achievementSet: createAchievementSet({ achievements: [] }),
        }),
      ],
      imageBoxArtUrl: faker.internet.url(),
      imageTitleUrl: faker.internet.url(),
      imageIngameUrl: faker.internet.url(),
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
    });

    render(<GameShowMobileRoot />, {
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hubs: [],
        seriesHub: null,
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: false, // !!
        playerAchievementChartBuckets: [],
        recentPlayers: [],
        recentVisibleComments: [],
        topAchievers: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('tab', { name: /community/i })).not.toBeInTheDocument();
  });
});
