import { t } from 'i18next';
import { expect } from 'vitest';

import { render } from '@/test';
import { createGame, createUser } from '@/test/factories';

import { GameSetRequestsRoot } from './GameSetRequestsRoot';

describe('Component: GameSetRequestsRoot', () => {
  it('renders without crashing', () => {
    const fakeGame = createGame();

    // Build Container
    const { container } = render(<GameSetRequestsRoot />, {
      pageProps: {
        deferredRequestors: null,
        initialRequestors: [],
        game: fakeGame,
        totalCount: 0,
      } as App.Community.Data.GameSetRequestsPageProps,
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('shows no one if there are no requestors', () => {
    const fakeGame = createGame();

    // Build Container
    const { container } = render(<GameSetRequestsRoot />, {
      pageProps: {
        deferredRequestors: null,
        initialRequestors: [],
        game: fakeGame,
        totalCount: 0,
      } as App.Community.Data.GameSetRequestsPageProps,
    });

    expect(container).toHaveTextContent(t('There are currently no active requests.'));
  });

  it('shows if there is one requestor', () => {
    const fakeGame = createGame();
    const fakeUser = createUser();

    const { container } = render(<GameSetRequestsRoot />, {
      pageProps: {
        deferredRequestors: null,
        initialRequestors: [fakeUser],
        game: fakeGame,
        totalCount: 1,
      } as App.Community.Data.GameSetRequestsPageProps,
    });

    expect(container).toHaveTextContent(
      'This achievement set has been requested by the following 1 users:',
    );
  });
});
