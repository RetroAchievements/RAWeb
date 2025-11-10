import { expect } from 'vitest';

import { render, screen, within } from '@/test';
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
      'This achievement set has been requested by the following user:',
    );
  });

  it('shows if there is multiple requestors', () => {
    // ARRANGE
    const fakeGame = createGame();
    const fakeUser = createUser();
    const fakeUser2 = createUser();

    // ACT
    const { container } = render(<GameSetRequestsRoot />, {
      pageProps: {
        deferredRequestors: null,
        initialRequestors: [fakeUser, fakeUser2],
        game: fakeGame,
        totalCount: 2,
      } as App.Community.Data.GameSetRequestsPageProps,
    });

    // ASSERT
    expect(container).toHaveTextContent(
      'This achievement set has been requested by the following 2 users:',
    );
  });

  it('given deferred requestors are provided, combines them with initial requestors', () => {
    // ARRANGE
    const fakeGame = createGame();
    const fakeUser1 = createUser({ displayName: 'User1' });
    const fakeUser2 = createUser({ displayName: 'User2' });
    const fakeUser3 = createUser({ displayName: 'User3' });

    render(<GameSetRequestsRoot />, {
      pageProps: {
        deferredRequestors: [fakeUser3], // !! deferred requestor loaded
        initialRequestors: [fakeUser1, fakeUser2],
        game: fakeGame,
        totalCount: 3,
      } as App.Community.Data.GameSetRequestsPageProps,
    });

    // ASSERT
    const requestorsList = screen.getByTestId('requestors-list');
    expect(within(requestorsList).getAllByRole('link')).toHaveLength(3);
  });

  it('given deferred requestors are still loading, shows a loading state', () => {
    // ARRANGE
    const fakeGame = createGame();
    const fakeUser1 = createUser();
    const fakeUser2 = createUser();

    const { container } = render(<GameSetRequestsRoot />, {
      pageProps: {
        deferredRequestors: null, // !! still loading
        initialRequestors: [fakeUser1, fakeUser2],
        game: fakeGame,
        totalCount: 5, // !! more than initial requestors
      } as App.Community.Data.GameSetRequestsPageProps,
    });

    // ASSERT
    expect(container).toHaveTextContent('Loading...');
  });
});
