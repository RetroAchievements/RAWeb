import { render, screen } from '@/test';
import { createGame, createGameHash } from '@/test/factories';

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
    expect(screen.getByText(/supported game file hash registered for this game/i)).toBeVisible();
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
    expect(screen.getByText(/supported game file hashes registered for this game/i)).toBeVisible();
  });

  it('given there are incompatible hashes, renders correctly', () => {
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
    expect(screen.getByText(/these game file hashes are known to be incompatible/i)).toBeVisible();
  });

  it('given there are untested hashes, renders correctly', () => {
    // ARRANGE
    render<App.Platform.Data.GameHashesPageProps>(<HashesMainRoot />, {
      pageProps: {
        can: { manageGameHashes: true },
        game: createGame({ forumTopicId: undefined }),
        hashes: [createGameHash(), createGameHash()],
        untestedHashes: [createGameHash()],
      },
    });

    // ASSERT
    expect(screen.getByText(/these game file hashes are recognized, but it is unknown whether or not they are compatible/i)).toBeVisible();
  });

  it('given there are patch required hashes, renders correctly', () => {
    // ARRANGE
    render<App.Platform.Data.GameHashesPageProps>(<HashesMainRoot />, {
      pageProps: {
        can: { manageGameHashes: true },
        game: createGame({ forumTopicId: undefined }),
        hashes: [createGameHash(), createGameHash()],
        patchRequiredHashes: [createGameHash()],
      },
    });

    // ASSERT
    expect(screen.getByText(/these game file hashes require a patch to be compatible/i)).toBeVisible();
  });
});
