import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createGame, createGameListEntry, createSystem } from '@/test/factories';

import { GameListItemElement } from './GameListItemElement';

// Suppress "Missing `Description` or `aria-describedby={undefined}` for {DialogContent}."
console.warn = vi.fn();

describe('Component: GameListItemElement', () => {
  let originalUrl: string;

  beforeEach(() => {
    originalUrl = window.location.href;

    Object.defineProperty(window, 'location', {
      writable: true,
      value: { href: 'http://localhost?param1=oldValue1&param2=oldValue2' },
    });
  });

  afterEach(() => {
    window.location.href = originalUrl;
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameListItemElement gameListEntry={createGameListEntry()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('always displays an accessible avatar and title for the game', () => {
    // ARRANGE
    const system = createSystem({ id: 1, nameShort: 'MD' });
    const game = createGame({ system, id: 1, title: 'Sonic the Hedgehog' });

    render(<GameListItemElement gameListEntry={createGameListEntry({ game })} />);

    // ASSERT
    expect(screen.getByRole('img', { name: /sonic the hedgehog/i })).toBeVisible();
    expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
    expect(screen.getByRole('separator')).toBeVisible();
  });

  it('always has one or more clickable links to the game page', () => {
    // ARRANGE
    const system = createSystem({ id: 1, nameShort: 'MD' });
    const game = createGame({ system, id: 1, title: 'Sonic the Hedgehog' });

    render(<GameListItemElement gameListEntry={createGameListEntry({ game })} />);

    // ASSERT
    const linkEls = screen.getAllByRole('link', { name: /sonic the hedgehog/i });

    expect(linkEls.length).toBeGreaterThanOrEqual(1);
    expect(linkEls[0]).toBeVisible();
    expect(linkEls[0]).toHaveAttribute('href', `game.show,${{ game: 1 }}`);
  });

  it('given the game system is known, displays the system short name', () => {
    // ARRANGE
    const system = createSystem({ id: 1, nameShort: 'MD' });
    const game = createGame({ system, id: 1, title: 'Sonic the Hedgehog' });

    render(<GameListItemElement gameListEntry={createGameListEntry({ game })} />);

    // ASSERT
    expect(screen.getByText(/md/i)).toBeVisible();
  });

  it('given the system is unknown, renders without crashing', () => {
    // ARRANGE
    const game = createGame({ system: undefined, id: 1, title: 'Sonic the Hedgehog' });

    const { container } = render(
      <GameListItemElement gameListEntry={createGameListEntry({ game })} />,
    );

    // ASSERT
    expect(screen.queryByText(/md/i)).not.toBeInTheDocument();
    expect(container).toBeTruthy();
  });

  it('given this is the last item, does not render a horizontal rule', () => {
    // ARRANGE
    const system = createSystem({ id: 1, nameShort: 'MD' });
    const game = createGame({ system, id: 1, title: 'Sonic the Hedgehog' });

    render(<GameListItemElement gameListEntry={createGameListEntry({ game })} isLastItem={true} />);

    // ASSERT
    expect(screen.queryByRole('separator')).not.toBeInTheDocument();
  });

  it('given the user clicks the open game details button, pops a drawer', async () => {
    // ARRANGE
    const system = createSystem({ id: 1, nameShort: 'MD' });
    const game = createGame({ system, id: 1, title: 'Sonic the Hedgehog' });

    render(<GameListItemElement gameListEntry={createGameListEntry({ game })} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /open game details/i }));

    // ASSERT
    expect(await screen.findByText('Game Details')).toBeVisible();
  });

  it('the game details drawer contains a backlog toggle button and an open game button', async () => {
    // ARRANGE
    const system = createSystem({ id: 1, nameShort: 'MD' });
    const game = createGame({ system, id: 1, title: 'Sonic the Hedgehog' });

    render(<GameListItemElement gameListEntry={createGameListEntry({ game })} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /open game details/i }));

    // ASSERT
    expect(await screen.findByRole('button', { name: /want to play/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /open game/i })).toBeVisible();
  });

  it('given there is a sort field id, shows that sort field value in the component', () => {
    // ARRANGE
    const system = createSystem({ id: 1, nameShort: 'MD' });
    const game = createGame({ system, id: 1, title: 'Sonic the Hedgehog', pointsTotal: 123 });

    render(
      <GameListItemElement
        gameListEntry={createGameListEntry({ game })}
        sortFieldId="pointsTotal"
      />,
    );

    // ASSERT
    expect(screen.getByText(/123/i)).toBeVisible();
  });

  it('given the user is unauthenticated and clicks the backlog toggle button, redirects them to login', async () => {
    // ARRANGE
    const system = createSystem({ id: 1, nameShort: 'MD' });
    const game = createGame({ system, id: 1, title: 'Sonic the Hedgehog' });

    render(
      <GameListItemElement gameListEntry={createGameListEntry({ game, isInBacklog: false })} />,
      {
        pageProps: { auth: null },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /add to want to play games/i }));

    // ASSERT
    expect(window.location.href).toEqual(['login']);
  });

  it("given the user is authenticated, the game is not currently in the user's backlog, and the user clicks the backlog toggle button, makes a call to add the game to the user's backlog", async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    const system = createSystem({ id: 1, nameShort: 'MD' });
    const game = createGame({ system, id: 1, title: 'Sonic the Hedgehog' });

    render(
      <GameListItemElement gameListEntry={createGameListEntry({ game, isInBacklog: false })} />,
      {
        pageProps: { auth: { user: createAuthenticatedUser() } },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /add to want to play games/i }));

    // ASSERT
    expect(postSpy).toHaveBeenCalledTimes(1);
    expect(postSpy).toHaveBeenCalledWith(['api.user-game-list.store', 1], {
      userGameListType: 'play',
    });
  });

  it("given the user is authenticated, the game is present in the user's backlog, and the user clicks the backlog toggle button, makes a call to remove the game from the user's backlog", async () => {
    // ARRANGE
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    const system = createSystem({ id: 1, nameShort: 'MD' });
    const game = createGame({ system, id: 1, title: 'Sonic the Hedgehog' });

    render(
      <GameListItemElement gameListEntry={createGameListEntry({ game, isInBacklog: true })} />,
      {
        pageProps: { auth: { user: createAuthenticatedUser() } },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /remove from want to play games/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledTimes(1);
    expect(deleteSpy).toHaveBeenCalledWith(['api.user-game-list.destroy', 1], {
      data: { userGameListType: 'play' },
    });
  });
});
