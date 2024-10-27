import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createGame } from '@/test/factories';

import { GameListItemDrawerBacklogToggleButton } from './GameListItemDrawerBacklogToggleButton';

describe('Component: GameListItemDrawerBacklogToggleButton', () => {
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
    const { container } = render(
      <GameListItemDrawerBacklogToggleButton game={createGame()} isInBacklog={false} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it("given the game is not in the user's backlog, renders an accessible button that allows them to add it", () => {
    // ARRANGE
    render(<GameListItemDrawerBacklogToggleButton game={createGame()} isInBacklog={false} />);

    // ASSERT
    expect(screen.getByRole('button', { name: /add to want to play games/i })).toBeVisible();
    expect(screen.queryByText(/remove from/i)).not.toBeInTheDocument();
  });

  it("given the game is currently in the user's backlog, renders an accessible button that allows them to remove it", () => {
    // ARRANGE
    render(<GameListItemDrawerBacklogToggleButton game={createGame()} isInBacklog={true} />);

    // ASSERT
    expect(screen.getByRole('button', { name: /remove from want to play games/i })).toBeVisible();
    expect(screen.queryByText(/add to/i)).not.toBeInTheDocument();
  });

  it('given the user is not authenticated and presses the button, redirects them to login', async () => {
    // ARRANGE
    render(<GameListItemDrawerBacklogToggleButton game={createGame()} isInBacklog={false} />, {
      pageProps: { auth: null },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /add to want to play games/i }));

    // ASSERT
    expect(window.location.href).toEqual(['login']);
  });

  it("given the game is not currently in the user's backlog and the user presses the button, makes the call to add the game to the user's backlog", async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    const game = createGame({ id: 1 });

    render(<GameListItemDrawerBacklogToggleButton game={game} isInBacklog={false} />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /add to want to play games/i }));

    // ASSERT
    expect(postSpy).toHaveBeenCalledTimes(1);
    expect(postSpy).toHaveBeenCalledWith(['api.user-game-list.store', 1], {
      userGameListType: 'play',
    });
  });

  it('given the user presses the button, an optional event is emitted that a consumer can use', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    const game = createGame({ id: 1 });

    const onToggle = vi.fn();

    render(
      <GameListItemDrawerBacklogToggleButton game={game} isInBacklog={false} onToggle={onToggle} />,
      {
        pageProps: { auth: { user: createAuthenticatedUser() } },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /add to want to play games/i }));

    // ASSERT
    expect(onToggle).toHaveBeenCalledTimes(1);
    expect(onToggle).toHaveBeenCalledWith(true);
  });

  // FIXME this test is throwing a exception vitest can't handle
  it.skip('given the back-end API call throws, emits another optional event with the current button toggle state', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post')
      .mockRejectedValueOnce({ success: false })
      .mockResolvedValue({ success: true });

    const game = createGame({ id: 1 });

    const onToggle = vi.fn();

    render(
      <GameListItemDrawerBacklogToggleButton game={game} isInBacklog={false} onToggle={onToggle} />,
      {
        pageProps: { auth: { user: createAuthenticatedUser() } },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /add to want to play games/i }));

    // ASSERT
    expect(onToggle).toHaveBeenCalledTimes(2);
    expect(onToggle).toHaveBeenNthCalledWith(1, true);
    expect(onToggle).toHaveBeenNthCalledWith(2, false);
  });

  it("given the game is currently in the user's backlog and the user presses the button, makes the call to remove the game from the user's backlog", async () => {
    // ARRANGE
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    const game = createGame({ id: 1 });

    render(<GameListItemDrawerBacklogToggleButton game={game} isInBacklog={true} />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /remove from want to play games/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledTimes(1);
    expect(deleteSpy).toHaveBeenCalledWith(['api.user-game-list.destroy', 1], {
      data: { userGameListType: 'play' },
    });
  });
});
