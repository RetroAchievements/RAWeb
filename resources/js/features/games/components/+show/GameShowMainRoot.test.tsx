import { faker } from '@faker-js/faker';

import { render, screen } from '@/test';
import {
  createAchievement,
  createAchievementSet,
  createAggregateAchievementSetCredits,
  createGame,
  createGameAchievementSet,
  createGameSet,
  createPageBanner,
  createSystem,
  createZiggyProps,
} from '@/test/factories';

import { currentListViewAtom } from '../../state/games.atoms';
import { GameShowMainRoot } from './GameShowMainRoot';

describe('Component: GameShowMainRoot', () => {
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

    const { container } = render(<GameShowMainRoot />, {
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hubs: [createGameSet()],
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: true,
        recentPlayers: [],
        recentVisibleComments: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the game is missing required media, does not render', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: undefined,
      gameAchievementSets: [createGameAchievementSet({ achievementSet: createAchievementSet() })],
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
    });

    render(<GameShowMainRoot />, {
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hubs: [],
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: true,
        recentPlayers: [],
        recentVisibleComments: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByTestId('game-show')).not.toBeInTheDocument();
  });

  it('given the game has all required media, shows an accessible heading', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: 'badge.jpg',
      gameAchievementSets: [createGameAchievementSet({ achievementSet: createAchievementSet() })],
      imageBoxArtUrl: faker.internet.url(),
      imageTitleUrl: faker.internet.url(),
      imageIngameUrl: faker.internet.url(),

      system: createSystem({
        iconUrl: 'icon.jpg',
        name: 'Nintendo Switch',
      }),

      title: 'Super Mario Odyssey',
    });

    render(<GameShowMainRoot />, {
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hubs: [],
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: true,
        recentPlayers: [],
        recentVisibleComments: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: 'Super Mario Odyssey' })).toBeVisible();
  });

  it('given the game has media URLs, shows them in the desktop media viewer', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: 'badge.jpg',
      gameAchievementSets: [createGameAchievementSet({ achievementSet: createAchievementSet() })],
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
      imageIngameUrl: 'ingame.jpg',
      imageTitleUrl: 'title.jpg',
    });

    render(<GameShowMainRoot />, {
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hubs: [],
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: true,
        recentPlayers: [],
        recentVisibleComments: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    const mediaImages = screen.getAllByRole('img');
    const imageUrls = mediaImages.map((img) => img.getAttribute('src'));
    expect(imageUrls).toContain('ingame.jpg');
    expect(imageUrls).toContain('title.jpg');
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

    render(<GameShowMainRoot />, {
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
        recentVisibleComments: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByRole('alertdialog', { name: /content warning/i })).toBeVisible();
  });

  it('given the game does not have a content warning, does not display the content warning dialog', () => {
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

    render(<GameShowMainRoot />, {
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hasMatureContent: false, // !!
        hubs: [],
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: true,
        recentVisibleComments: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('alertdialog', { name: /content warning/i })).not.toBeInTheDocument();
  });

  it('given the game has no achievements and the user is viewing achievements, renders an empty state', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [
        createGameAchievementSet({ achievementSet: createAchievementSet({ achievements: [] }) }), // !!
      ],
      imageTitleUrl: faker.internet.url(),
    });

    render(<GameShowMainRoot />, {
      jotaiAtoms: [
        [currentListViewAtom, 'achievements'], // !!
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
        recentPlayers: [],
        recentVisibleComments: [],
        setRequestData: {
          hasUserRequestedSet: false,
          totalRequests: 0,
          userRequestsRemaining: 0,
        },
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByText(/no achievements yet/i)).toBeVisible();
  });

  it('given the game has no achievements and the user is viewing leaderboards, does not render an empty state', () => {
    // ARRANGE
    const game = createGame({
      gameAchievementSets: [
        createGameAchievementSet({ achievementSet: createAchievementSet({ achievements: [] }) }), // !!
      ],
      imageTitleUrl: faker.internet.url(),
    });

    render(<GameShowMainRoot />, {
      jotaiAtoms: [
        [currentListViewAtom, 'leaderboards'], // !!
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
        recentPlayers: [],
        recentVisibleComments: [],
        setRequestData: {
          hasUserRequestedSet: false,
          totalRequests: 0,
          userRequestsRemaining: 0,
        },
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByText(/no achievements yet/i)).not.toBeInTheDocument();
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
      title: 'Test Game',
    });

    render(<GameShowMainRoot />, {
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hubs: [],
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: true,
        recentPlayers: [],
        recentVisibleComments: [],
        setRequestData: {
          hasUserRequestedSet: false,
          totalRequests: 0,
          userRequestsRemaining: 0,
        },
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByText(/no achievements yet/i)).not.toBeInTheDocument();
  });

  it('given the user is viewing unpublished achievements, does not show recent players or comments', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: 'badge.jpg',
      gameAchievementSets: [
        createGameAchievementSet({
          achievementSet: createAchievementSet({ achievements: [createAchievement()] }),
        }),
      ],
      imageBoxArtUrl: faker.internet.url(),
      imageTitleUrl: faker.internet.url(),
      imageIngameUrl: faker.internet.url(),
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
      title: 'Test Game',
    });

    render(<GameShowMainRoot />, {
      pageProps: {
        game,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hubs: [],
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: false, // !!
        recentPlayers: [],
        recentVisibleComments: [],
        setRequestData: {
          hasUserRequestedSet: false,
          totalRequests: 0,
          userRequestsRemaining: 0,
        },
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByText(/recent players/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/comments/i)).not.toBeInTheDocument();
  });

  it('given the game has a custom banner, does not render the inline breadcrumbs and header', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: 'badge.jpg',
      gameAchievementSets: [
        createGameAchievementSet({
          achievementSet: createAchievementSet({ achievements: [createAchievement()] }),
        }),
      ],
      imageBoxArtUrl: faker.internet.url(),
      imageTitleUrl: faker.internet.url(),
      imageIngameUrl: faker.internet.url(),
      system: createSystem({
        iconUrl: 'icon.jpg',
        name: 'Nintendo 64',
      }),
      title: 'Super Mario 64',
    });

    const banner = createPageBanner({
      desktopMdWebp: 'https://example.com/banner.webp',
    });

    render(<GameShowMainRoot />, {
      pageProps: {
        game,
        banner,
        achievementSetClaims: [],
        aggregateCredits: createAggregateAchievementSetCredits(),
        backingGame: game,
        can: {},
        hubs: [],
        selectableGameAchievementSets: [],
        isViewingPublishedAchievements: true,
        recentPlayers: [],
        recentVisibleComments: [],
        setRequestData: {
          hasUserRequestedSet: false,
          totalRequests: 0,
          userRequestsRemaining: 0,
        },
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('heading', { name: 'Super Mario 64' })).not.toBeInTheDocument();
    expect(screen.queryByText('Nintendo 64')).not.toBeInTheDocument();
  });
});
