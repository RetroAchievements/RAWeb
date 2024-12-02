import userEvent from '@testing-library/user-event';
import type { FC } from 'react';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createGame } from '@/test/factories';

import { useGameBacklogState } from '../../useGameBacklogState';
import { GameListItemDrawerBacklogToggleButton } from './GameListItemDrawerBacklogToggleButton';

interface TestHarnessProps {
  game: App.Platform.Data.Game;
  isInitiallyInBacklog: boolean;

  onToggle?: () => void;
}

// We need to instantiate props with a hook, so a test harness is required.
const TestHarness: FC<TestHarnessProps> = ({ game, isInitiallyInBacklog, onToggle }) => {
  const backlogState = useGameBacklogState({ game, isInitiallyInBacklog });

  return (
    <GameListItemDrawerBacklogToggleButton
      backlogState={backlogState}
      onToggle={onToggle ?? vi.fn()}
    />
  );
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
    const { container } = render(<TestHarness game={createGame()} isInitiallyInBacklog={false} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it("given the game is not in the user's backlog, renders an accessible button that allows them to add it", () => {
    // ARRANGE
    render(<TestHarness game={createGame()} isInitiallyInBacklog={false} />);

    // ASSERT
    expect(screen.getByRole('button', { name: /add to want to play games/i })).toBeVisible();
    expect(screen.queryByText(/remove from/i)).not.toBeInTheDocument();
  });

  it("given the game is currently in the user's backlog, renders an accessible button that allows them to remove it", () => {
    // ARRANGE
    render(<TestHarness game={createGame()} isInitiallyInBacklog={true} />);

    // ASSERT
    expect(screen.getByRole('button', { name: /remove from want to play games/i })).toBeVisible();
    expect(screen.queryByText(/add to/i)).not.toBeInTheDocument();
  });

  it("given the game is not currently in the user's backlog and the user presses the button, invokes the toggle event", async () => {
    // ARRANGE
    const game = createGame({ id: 1 });

    const onToggle = vi.fn();

    render(<TestHarness game={game} isInitiallyInBacklog={false} onToggle={onToggle} />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /add to want to play games/i }));

    // ASSERT
    expect(onToggle).toHaveBeenCalledOnce();
  });

  it("given the game is currently in the user's backlog and the user presses the button, invokes the toggle event", async () => {
    // ARRANGE
    const game = createGame({ id: 1 });

    const onToggle = vi.fn();

    render(<TestHarness game={game} isInitiallyInBacklog={true} onToggle={onToggle} />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /remove from want to play games/i }));

    // ASSERT
    expect(onToggle).toHaveBeenCalledOnce();
  });
});
