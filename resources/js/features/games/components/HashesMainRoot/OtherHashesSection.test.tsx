import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createGame, createGameHash } from '@/test/factories';

import { OtherHashesSection } from './OtherHashesSection';

describe('Component: HashesMainRoot', () => {
  it('given there are no incompatible hashes, renders nothing', async () => {
    // ARRANGE
    render<App.Platform.Data.GameHashesPageProps>(<OtherHashesSection />, {
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
    render<App.Platform.Data.GameHashesPageProps>(<OtherHashesSection />, {
      pageProps: {
        can: { manageGameHashes: true },
        game: createGame({ forumTopicId: undefined }),
        hashes: [createGameHash(), createGameHash()],
        incompatibleHashes: [createGameHash()],
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /other known hashes/i }));

    // ASSERT
    expect(screen.getByText(/these game file hashes are known to be incompatible/i)).toBeVisible();
  });

  it('given there are untested hashes, renders correctly', async () => {
    // ARRANGE
    render<App.Platform.Data.GameHashesPageProps>(<OtherHashesSection />, {
      pageProps: {
        can: { manageGameHashes: true },
        game: createGame({ forumTopicId: undefined }),
        hashes: [createGameHash(), createGameHash()],
        untestedHashes: [createGameHash()],
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /other known hashes/i }));

    // ASSERT
    expect(
      screen.getByText(
        /these game file hashes are recognized, but it is unknown whether or not they are compatible/i,
      ),
    ).toBeVisible();
  });

  it('given there are patch required hashes, renders correctly', async () => {
    // ARRANGE
    render<App.Platform.Data.GameHashesPageProps>(<OtherHashesSection />, {
      pageProps: {
        can: { manageGameHashes: true },
        game: createGame({ forumTopicId: undefined }),
        hashes: [createGameHash(), createGameHash()],
        patchRequiredHashes: [createGameHash()],
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /other known hashes/i }));

    // ASSERT
    expect(
      screen.getByText(/these game file hashes require a patch to be compatible/i),
    ).toBeVisible();
  });

  it('given there are all types of hashes, renders correctly', async () => {
    // ARRANGE
    render<App.Platform.Data.GameHashesPageProps>(<OtherHashesSection />, {
      pageProps: {
        can: { manageGameHashes: true },
        game: createGame({ forumTopicId: undefined }),
        hashes: [createGameHash(), createGameHash()],
        incompatibleHashes: [createGameHash()],
        untestedHashes: [createGameHash()],
        patchRequiredHashes: [createGameHash()],
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /other known hashes/i }));

    // ASSERT
    expect(screen.getByText(/these game file hashes are known to be incompatible/i)).toBeVisible();
    expect(
      screen.getByText(
        /these game file hashes are recognized, but it is unknown whether or not they are compatible/i,
      ),
    ).toBeVisible();
    expect(
      screen.getByText(/these game file hashes require a patch to be compatible/i),
    ).toBeVisible();
  });
});
