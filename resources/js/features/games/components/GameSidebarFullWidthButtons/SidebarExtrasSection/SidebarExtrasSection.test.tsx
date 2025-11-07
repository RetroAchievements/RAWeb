import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createGame, createSystem } from '@/test/factories';

import { SidebarExtrasSection } from './SidebarExtrasSection';

describe('Component: SidebarExtrasSection', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SidebarExtrasSection game={createGame()} />, {
      pageProps: {
        backingGame: createGame(),
        can: {},
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is authenticated, renders the Extras section', () => {
    // ARRANGE
    render(<SidebarExtrasSection game={createGame({ system: createSystem({ active: true }) })} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        can: {},
      },
    });

    // ASSERT
    expect(screen.getByText(/extras/i)).toBeVisible();
    expect(screen.getByRole('link', { name: /memory/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /tickets/i })).toBeVisible();
  });

  it('given the user is authenticated and the backing game has a guide, renders the Guide link', () => {
    // ARRANGE
    render(<SidebarExtrasSection game={createGame()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame({ guideUrl: 'google.com' }), // !!
        can: {},
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /guide/i })).toBeVisible();
  });

  it('given the game system is not active, never displays the memory and tickets buttons', () => {
    // ARRANGE
    render(
      <SidebarExtrasSection game={createGame({ system: createSystem({ active: false }) })} />, // !!
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          can: { manageGames: false }, // !!
        },
      },
    );

    // ASSERT
    expect(screen.queryByRole('link', { name: /memory/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /tickets/i })).not.toBeInTheDocument();
  });

  it('given the game and backing game are different, shows subset indicator on Tickets button', () => {
    // ARRANGE
    const game = createGame({ id: 1, system: createSystem({ active: true }) }); // !!
    const backingGame = createGame({ id: 2 }); // !!

    render(<SidebarExtrasSection game={game} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame,
        can: {},
      },
    });

    // ASSERT
    expect(screen.getByRole('img', { name: /subset/i })).toBeVisible();
  });

  it('given the game and backing game are the same, does not show subset indicator on Tickets button', () => {
    // ARRANGE
    const game = createGame({ id: 1, system: createSystem({ active: true }) }); // !!
    const backingGame = createGame({ id: 1 }); // !!

    render(<SidebarExtrasSection game={game} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame,
        can: {},
      },
    });

    // ASSERT
    expect(screen.queryByRole('img', { name: /subset/i })).not.toBeInTheDocument();
  });
});
