import { faker } from '@faker-js/faker';
import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import {
  createAchievement,
  createAchievementSet,
  createAggregateAchievementSetCredits,
  createComment,
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
  let originalLocation: Location;

  beforeEach(() => {
    // Mock router methods to prevent actual navigation during tests.
    vi.spyOn(router, 'visit').mockImplementation(() => {});
    vi.spyOn(router, 'replace').mockImplementation(() => {});

    // Mock window.location so the useGameShowTabs hook can read and modify URL params.
    originalLocation = window.location;
    Object.defineProperty(window, 'location', {
      value: {
        href: 'https://retroachievements.org/game/123',
        origin: 'https://retroachievements.org',
        protocol: 'https:',
        host: 'retroachievements.org',
        hostname: 'retroachievements.org',
        port: '',
        pathname: '/game/123',
        search: '',
        hash: '',
        assign: vi.fn(),
        reload: vi.fn(),
        replace: vi.fn(),
        toString: () => 'https://retroachievements.org/game/123',
      },
      writable: true,
      configurable: true,
    });

    const mockIntersectionObserver = vi.fn();
    mockIntersectionObserver.mockReturnValue({
      observe: () => null,
      unobserve: () => null,
      disconnect: () => null,
    });
    window.IntersectionObserver = mockIntersectionObserver;
  });

  afterEach(() => {
    (window.location as any) = originalLocation;
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
    (window.location as any).href = 'https://retroachievements.org/game/123?tab=info';
    window.location.search = '?tab=info';

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

  it('given the game has players but no achievements and the user is on the stats tab, does not render PlaytimeStatistics', () => {
    // ARRANGE
    (window.location as any).href = 'https://retroachievements.org/game/123?tab=stats';
    window.location.search = '?tab=stats';

    const game = createGame({
      badgeUrl: 'badge.jpg',
      gameAchievementSets: [
        createGameAchievementSet({
          achievementSet: createAchievementSet({ achievements: [] }), // !! No achievements.
        }),
      ],
      imageBoxArtUrl: faker.internet.url(),
      imageTitleUrl: faker.internet.url(),
      imageIngameUrl: faker.internet.url(),
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
      playersTotal: 100, // !! Has players.
    });

    render(<GameShowMobileRoot />, {
      jotaiAtoms: [
        [currentTabAtom, 'stats'], // !! On stats tab.
      ],
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hubs: [],
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: true, // !! Viewing published achievements.
        playerAchievementChartBuckets: [],
        recentPlayers: [],
        recentVisibleComments: [],
        topAchievers: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByTestId('playtime-statistics')).not.toBeInTheDocument();
  });

  it('given the game has players but no achievements and the user is on the stats tab, does not render the compare progress component', () => {
    // ARRANGE
    (window.location as any).href = 'https://retroachievements.org/game/123?tab=stats';
    window.location.search = '?tab=stats';

    const game = createGame({
      badgeUrl: 'badge.jpg',
      gameAchievementSets: [
        createGameAchievementSet({
          achievementSet: createAchievementSet({ achievements: [] }), // !! No achievements.
        }),
      ],
      imageBoxArtUrl: faker.internet.url(),
      imageTitleUrl: faker.internet.url(),
      imageIngameUrl: faker.internet.url(),
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
      playersTotal: 100, // !!
    });

    render(<GameShowMobileRoot />, {
      jotaiAtoms: [
        [currentTabAtom, 'stats'], // !!
      ],
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        followedPlayerCompletions: [],
        hubs: [],
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
    expect(screen.queryByTestId('playable-compare-progress')).not.toBeInTheDocument();
  });

  it('given the user changes tabs, does not crash', async () => {
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

    // ACT
    await userEvent.click(screen.getByRole('tab', { name: /info/i }));

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are comments and the user is viewing published achievements, shows the comments preview card', () => {
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
        isViewingPublishedAchievements: true, // !!
        numComments: 5, // !!
        playerAchievementChartBuckets: [],
        recentPlayers: [],
        recentVisibleComments: [createComment()], // !!
        topAchievers: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /view recent comments/i })).toBeVisible();
  });

  it('given the user is not viewing published achievements, does not show the comments preview card', () => {
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
        isViewingPublishedAchievements: false, // !!
        numComments: 5,
        playerAchievementChartBuckets: [],
        recentPlayers: [],
        recentVisibleComments: [createComment()],
        topAchievers: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /view recent comments/i })).not.toBeInTheDocument();
  });
});
