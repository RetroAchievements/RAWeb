import { render, screen } from '@/test';
import {
  createAchievementSet,
  createGame,
  createGameAchievementSet,
  createGameHash,
} from '@/test/factories';

import { HashesMainRoot } from './HashesMainRoot';

describe('Component: HashesMainRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.GameHashesPageProps>(<HashesMainRoot />, {
      pageProps: {
        can: { manageGameHashes: false },
        game: createGame(),
        hashes: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user can manage hashes, shows a manage link', () => {
    // ARRANGE
    render<App.Platform.Data.GameHashesPageProps>(<HashesMainRoot />, {
      pageProps: {
        can: { manageGameHashes: true },
        game: createGame(),
        hashes: [],
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /manage hashes/i })).toBeVisible();
  });

  it('given the game has no forum topic, does not render a forum topic link', () => {
    // ARRANGE
    render<App.Platform.Data.GameHashesPageProps>(<HashesMainRoot />, {
      pageProps: {
        can: { manageGameHashes: true },
        game: createGame({ forumTopicId: undefined }),
        hashes: [],
      },
    });

    // ASSERT
    expect(screen.queryByText(/official forum topic/i)).not.toBeInTheDocument();
  });

  it('given there is only a single hash, pluralizes correctly', () => {
    // ARRANGE
    render<App.Platform.Data.GameHashesPageProps>(<HashesMainRoot />, {
      pageProps: {
        can: { manageGameHashes: true },
        game: createGame({ forumTopicId: undefined }),
        hashes: [createGameHash()],
      },
    });

    // ASSERT
    expect(
      screen.getByText(/supported game file hash registered for this achievement set/i),
    ).toBeVisible();
  });

  it('given there are multiple hashes, pluralizes correctly', () => {
    // ARRANGE
    render<App.Platform.Data.GameHashesPageProps>(<HashesMainRoot />, {
      pageProps: {
        can: { manageGameHashes: true },
        game: createGame({ forumTopicId: undefined }),
        hashes: [createGameHash(), createGameHash()],
      },
    });

    // ASSERT
    expect(
      screen.getByText(/supported game file hashes registered for this achievement set/i),
    ).toBeVisible();
  });

  it('given there are no incompatible hashes, renders nothing', async () => {
    // ARRANGE
    render<App.Platform.Data.GameHashesPageProps>(<HashesMainRoot />, {
      pageProps: {
        can: { manageGameHashes: true },
        game: createGame({ forumTopicId: undefined }),
        hashes: [createGameHash(), createGameHash()],
      },
    });

    // ASSERT
    const button = screen.queryByRole('button', { name: /other known hashes/i });
    expect(button).toBeNull();
  });

  it('given there are incompatible hashes, renders correctly', async () => {
    // ARRANGE
    render<App.Platform.Data.GameHashesPageProps>(<HashesMainRoot />, {
      pageProps: {
        can: { manageGameHashes: true },
        game: createGame({ forumTopicId: undefined }),
        hashes: [createGameHash(), createGameHash()],
        incompatibleHashes: [createGameHash()],
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /other known hashes/i })).toBeVisible();
  });

  it("given there is no target achievement set, uses the game's badge in the heading", () => {
    // ARRANGE
    const game = createGame({ badgeUrl: 'https://example.com/game-badge.png' });

    render<App.Platform.Data.GameHashesPageProps>(<HashesMainRoot />, {
      pageProps: {
        can: { manageGameHashes: false },
        game,
        hashes: [],
        targetAchievementSet: undefined,
      },
    });

    // ASSERT
    const headingImage = screen.getByRole('img', { name: game.title });
    expect(headingImage).toHaveAttribute('src', 'https://example.com/game-badge.png');
  });

  it("given there is a target achievement set, uses the achievement set's badge in the heading", () => {
    // ARRANGE
    const game = createGame({ badgeUrl: 'https://example.com/game-badge.png' });
    const targetAchievementSet = createGameAchievementSet({
      title: 'Low%',
      achievementSet: createAchievementSet({
        imageAssetPathUrl: 'https://example.com/subset-badge.png',
      }),
    });

    render<App.Platform.Data.GameHashesPageProps>(<HashesMainRoot />, {
      pageProps: {
        can: { manageGameHashes: false },
        game,
        hashes: [],
        targetAchievementSet,
      },
    });

    // ASSERT
    const headingImage = screen.getByRole('img', { name: game.title });
    expect(headingImage).toHaveAttribute('src', 'https://example.com/subset-badge.png');
  });
});
