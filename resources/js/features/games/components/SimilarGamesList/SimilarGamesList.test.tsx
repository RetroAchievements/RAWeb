import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createGame, createSystem } from '@/test/factories';

import { SimilarGamesList } from './SimilarGamesList';

describe('Component: SimilarGamesList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const system = createSystem();
    const similarGames = [createGame({ system })];

    const { container } = render(<SimilarGamesList similarGames={similarGames} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no similar games, renders nothing', () => {
    // ARRANGE
    render(<SimilarGamesList similarGames={[]} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
      },
    });

    // ASSERT
    expect(screen.queryByText(/similar games/i)).not.toBeInTheDocument();
  });

  it('given null for similar games, renders nothing', () => {
    // ARRANGE
    render(<SimilarGamesList similarGames={null as any} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
      },
    });

    // ASSERT
    expect(screen.queryByText(/similar games/i)).not.toBeInTheDocument();
  });

  it('given similar games, renders the correct heading', () => {
    // ARRANGE
    const system = createSystem();
    const similarGames = [createGame({ system })];
    render(<SimilarGamesList similarGames={similarGames} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/similar games/i)).toBeVisible();
  });

  it('given similar games, renders a list item for each game', () => {
    // ARRANGE
    const system = createSystem();
    const similarGames = [
      createGame({ system, id: 1, title: 'Game One' }),
      createGame({ system, id: 2, title: 'Game Two' }),
      createGame({ system, id: 3, title: 'Game Three' }),
    ];

    render(<SimilarGamesList similarGames={similarGames} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
      },
    });

    // ASSERT
    expect(screen.getByTestId('similar-games-list')).toBeVisible();

    expect(screen.getAllByText('Game One')[0]).toBeVisible();
    expect(screen.getAllByText('Game Two')[0]).toBeVisible();
    expect(screen.getAllByText('Game Three')[0]).toBeVisible();

    const listItems = screen.getAllByRole('listitem');
    expect(listItems).toHaveLength(3);
  });
});
