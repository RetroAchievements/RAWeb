import userEvent from '@testing-library/user-event';
import axios from 'axios';
import type { FC } from 'react';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createGame } from '@/test/factories';

import { useGameBacklogState } from '../../useGameBacklogState';
import { GameListItemDrawerBacklogToggleButton } from './GameListItemDrawerBacklogToggleButton';

interface TestHarnessProps {
  game: App.Platform.Data.Game;
  isInitiallyInBacklog: boolean;
}

const TestHarness: FC<TestHarnessProps> = ({ game, isInitiallyInBacklog }) => {
  const backlogState = useGameBacklogState({ game, isInitiallyInBacklog });

  return <GameListItemDrawerBacklogToggleButton game={game} backlogState={backlogState} />;
};

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
    const { container } = render(<TestHarness game={createGame()} isInitiallyInBacklog={false} />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it("given the game is not in the user's backlog, renders an accessible button that allows them to add it", () => {
    // ARRANGE
    render(<TestHarness game={createGame()} isInitiallyInBacklog={false} />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /add to want to play games/i })).toBeVisible();
    expect(screen.queryByText(/remove from/i)).not.toBeInTheDocument();
  });

  it("given the game is currently in the user's backlog, renders an accessible button that allows them to remove it", () => {
    // ARRANGE
    render(<TestHarness game={createGame()} isInitiallyInBacklog={true} />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /remove from want to play games/i })).toBeVisible();
    expect(screen.queryByText(/add to/i)).not.toBeInTheDocument();
  });

  it('given the user is not authenticated and presses the button, redirects them to login', async () => {
    // ARRANGE
    render(<TestHarness game={createGame()} isInitiallyInBacklog={false} />, {
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

    render(<TestHarness game={game} isInitiallyInBacklog={false} />, {
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

  it("given the game is currently in the user's backlog and the user presses the button, makes the call to remove the game from the user's backlog", async () => {
    // ARRANGE
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    const game = createGame({ id: 1 });

    render(<TestHarness game={game} isInitiallyInBacklog={true} />, {
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
