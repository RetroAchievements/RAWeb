import { expect } from 'vitest';

import { render } from '@/test';
import { createGame, createUser } from '@/test/factories';

import { GameSetRequestsRoot } from './GameSetRequestsRoot';

describe('Component: GameSetRequestsRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const fakeGame = createGame();

    // ACT
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
    // ARRANGE
    const fakeGame = createGame();

    // ACT
    const { container } = render(<GameSetRequestsRoot />, {
      pageProps: {
        deferredRequestors: null,
        initialRequestors: [],
        game: fakeGame,
        totalCount: 0,
      } as App.Community.Data.GameSetRequestsPageProps,
    });

    // ASSERT
    expect(container).toHaveTextContent('There are currently no active requests.');
  });

  it('shows if there is one requestor', () => {
    // ARRANGE
    const fakeGame = createGame();
    const fakeUser = createUser();

    // ACT
    const { container } = render(<GameSetRequestsRoot />, {
      pageProps: {
        deferredRequestors: null,
        initialRequestors: [fakeUser],
        game: fakeGame,
        totalCount: 1,
      } as App.Community.Data.GameSetRequestsPageProps,
    });

    // ASSERT
    expect(container).toHaveTextContent(
      'This achievement set has been requested by the following 1 users:',
    );
  });
});
