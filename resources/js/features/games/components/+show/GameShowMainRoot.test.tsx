import { faker } from '@faker-js/faker';

import { render, screen } from '@/test';
import { createGame, createGameSet, createSystem } from '@/test/factories';

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
        can: {},
        hubs: [createGameSet()],
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the game is missing required media, does not render', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: undefined,
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
    });

    render(<GameShowMainRoot />, {
      pageProps: {
        game,
        can: {},
        hubs: [],
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(screen.queryByTestId('game-show')).not.toBeInTheDocument();
  });

  it('given the game has all required media, shows an accessible heading', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: 'badge.jpg',
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
        can: {},
        hubs: [],
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: 'Super Mario Odyssey' })).toBeVisible();
  });

  it('given the game has media URLs, shows them in the desktop media viewer', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: 'badge.jpg',
      system: createSystem({
        iconUrl: 'icon.jpg',
      }),
      imageIngameUrl: 'ingame.jpg',
      imageTitleUrl: 'title.jpg',
    });

    render(<GameShowMainRoot />, {
      pageProps: {
        game,
        can: {},
        hubs: [],
        recentVisibleComments: [],
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
        can: {},
        hasMatureContent: true, // !!
        hubs: [],
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(screen.getByRole('alertdialog', { name: /content warning/i })).toBeVisible();
  });

  it('given the game does not have a content warning, does not display the content warning dialog', () => {
    // ARRANGE
    const game = createGame({
      badgeUrl: 'badge.jpg',
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
        can: {},
        hasMatureContent: false, // !!
        hubs: [],
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(screen.queryByRole('alertdialog', { name: /content warning/i })).not.toBeInTheDocument();
  });
});
