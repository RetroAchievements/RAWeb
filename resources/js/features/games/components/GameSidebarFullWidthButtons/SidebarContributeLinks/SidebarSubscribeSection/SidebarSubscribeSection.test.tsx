import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createGame } from '@/test/factories';

import { SidebarSubscribeSection } from './SidebarSubscribeSection';

describe('Component: SidebarSubscribeSection', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SidebarSubscribeSection game={createGame()} />, {
      pageProps: {
        backingGame: createGame(),
        isSubscribedToAchievementComments: false,
        isSubscribedToTickets: false,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is a developer, shows the Subscribe section with Achievement Comments and Tickets buttons', () => {
    // ARRANGE
    const backingGame = createGame({ id: 123 });

    render(<SidebarSubscribeSection game={createGame()} />, {
      pageProps: {
        achievementSetClaims: [],
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) }, // !!
        backingGame,
        game: createGame({ gameAchievementSets: [] }),
        can: {},
        isSubscribedToAchievementComments: false,
        isSubscribedToTickets: false,
      },
    });

    // ASSERT
    expect(screen.getByText(/subscribe/i)).toBeVisible();
    expect(screen.getByRole('button', { name: /achievement comments/i })).toBeVisible();
    expect(screen.getByRole('button', { name: 'Tickets' })).toBeVisible();
  });

  it('given the user is a junior developer, shows the Subscribe section with Achievement Comments and Tickets buttons', () => {
    // ARRANGE
    const backingGame = createGame({ id: 123 });

    render(<SidebarSubscribeSection game={createGame()} />, {
      pageProps: {
        achievementSetClaims: [],
        auth: { user: createAuthenticatedUser({ roles: ['developer-junior'] }) }, // !!
        backingGame,
        game: createGame({ gameAchievementSets: [] }),
        can: {},
        isSubscribedToAchievementComments: false,
        isSubscribedToTickets: false,
      },
    });

    // ASSERT
    expect(screen.getByText(/subscribe/i)).toBeVisible();
    expect(screen.getByRole('button', { name: /achievement comments/i })).toBeVisible();
    expect(screen.getByRole('button', { name: 'Tickets' })).toBeVisible();
  });

  it('given the game and backing game are different, shows subset icons on both subscription toggles', () => {
    // ARRANGE
    const game = createGame({ id: 1 }); // !!
    const backingGame = createGame({ id: 2 }); // !!

    render(<SidebarSubscribeSection game={game} />, {
      pageProps: {
        backingGame,
        isSubscribedToAchievementComments: false,
        isSubscribedToTickets: false,
      },
    });

    // ASSERT
    expect(screen.getAllByRole('img', { name: /subset/i })).toHaveLength(2);
  });

  it('given the game and backing game are the same, does not show subset icons', () => {
    // ARRANGE
    const game = createGame({ id: 1 }); // !!
    const backingGame = createGame({ id: 1 }); // !!

    render(<SidebarSubscribeSection game={game} />, {
      pageProps: {
        backingGame,
        isSubscribedToAchievementComments: false,
        isSubscribedToTickets: false,
      },
    });

    // ASSERT
    expect(screen.queryByRole('img', { name: /subset/i })).not.toBeInTheDocument();
  });
});
