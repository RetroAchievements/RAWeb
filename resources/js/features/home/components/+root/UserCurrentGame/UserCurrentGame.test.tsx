import { render, screen } from '@/test';
import { createGame } from '@/test/factories';

import { UserCurrentGame } from './UserCurrentGame';

describe('Component: UserCurrentGame', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<UserCurrentGame />, {
      pageProps: { userCurrentGame: createGame() },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no current game, does not render anything', () => {
    // ARRANGE
    render(<UserCurrentGame />, {
      pageProps: { userCurrentGame: null },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /in game/i })).not.toBeInTheDocument();
  });

  it('given there is a current active game, displays the game information', () => {
    // ARRANGE
    render(<UserCurrentGame />, {
      pageProps: {
        userCurrentGame: createGame({ title: 'Super Mario Bros.' }),
        userCurrentGameMinutesAgo: 0,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /in game/i })).toBeVisible();
    expect(screen.getByText('Super Mario Bros.')).toBeVisible();
    expect(screen.getByRole('img')).toBeVisible();
  });

  it('given there is a recent active game, displays the game information', () => {
    // ARRANGE
    render(<UserCurrentGame />, {
      pageProps: {
        userCurrentGame: createGame({ title: 'Super Mario Bros.' }),
        userCurrentGameMinutesAgo: 10,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /recently played/i })).toBeVisible();
    expect(screen.getByText('Super Mario Bros.')).toBeVisible();
    expect(screen.getByRole('img')).toBeVisible();
  });

  it('given there is a current game, links to the game page', () => {
    // ARRANGE
    const game = createGame({ id: 12345 });

    render(<UserCurrentGame />, {
      pageProps: { userCurrentGame: game },
    });

    // ASSERT
    const linkEl = screen.getByRole('link');
    expect(linkEl.getAttribute('href')).toEqual(expect.stringContaining('game.show'));
  });
});
