import userEvent from '@testing-library/user-event';

import { useGameBacklogState } from '@/common/hooks/useGameBacklogState';
import { render, screen } from '@/test';
import { createGame } from '@/test/factories';

import { WantToPlayToggle } from './WantToPlayToggle';

const mockToggleBacklog = vi.fn();
vi.mock('@/common/hooks/useGameBacklogState', () => ({
  useGameBacklogState: vi.fn(() => ({
    toggleBacklog: mockToggleBacklog,
    isInBacklogMaybeOptimistic: false,
  })),
}));

describe('Component: WantToPlayToggle', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<WantToPlayToggle />, {
      pageProps: {
        backingGame: createGame(),
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the want to play button text', () => {
    // ARRANGE
    render(<WantToPlayToggle />, {
      pageProps: {
        backingGame: createGame(),
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(screen.getByText(/want to play/i)).toBeVisible();
  });

  it('given the user clicks the toggle button, calls the toggle function', async () => {
    // ARRANGE
    render(<WantToPlayToggle />, {
      pageProps: {
        backingGame: createGame(),
        isOnWantToPlayList: false,
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /want to play/i }));

    // ASSERT
    expect(mockToggleBacklog).toHaveBeenCalledTimes(1);
  });

  it('given the game is not on the want to play list, sets aria-pressed to false', () => {
    // ARRANGE
    vi.mocked(useGameBacklogState).mockReturnValueOnce({
      toggleBacklog: mockToggleBacklog,
      isInBacklogMaybeOptimistic: false, // !!
    } as any);

    render(<WantToPlayToggle />, {
      pageProps: {
        backingGame: createGame(),
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const button = screen.getByRole('button', { name: /want to play/i });
    expect(button).not.toBePressed();
  });

  it('given the game is on the want to play list, sets aria-pressed to true', () => {
    // ARRANGE
    vi.mocked(useGameBacklogState).mockReturnValueOnce({
      toggleBacklog: mockToggleBacklog,
      isInBacklogMaybeOptimistic: true, // !! Already in backlog.
    } as any);

    render(<WantToPlayToggle />, {
      pageProps: {
        backingGame: createGame(),
        isOnWantToPlayList: true,
      },
    });

    // ASSERT
    const button = screen.getByRole('button', { name: /want to play/i });
    expect(button).toBePressed();
  });

  it('given the variant is base, applies base text size classes', () => {
    // ARRANGE
    render(<WantToPlayToggle variant="base" />, {
      pageProps: {
        backingGame: createGame(),
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const textElement = screen.getByText(/want to play/i);
    expect(textElement).toHaveClass('text-sm');
    expect(textElement).not.toHaveClass('text-xs');
  });

  it('given the variant is sm, applies smaller text size classes', () => {
    // ARRANGE
    render(<WantToPlayToggle variant="sm" />, {
      pageProps: {
        backingGame: createGame(),
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const textElement = screen.getByText(/want to play/i);
    expect(textElement).toHaveClass('text-xs');
    expect(textElement).not.toHaveClass('text-sm');
  });

  it('given no variant prop is provided, defaults to base variant', () => {
    // ARRANGE
    render(<WantToPlayToggle />, {
      pageProps: {
        backingGame: createGame(),
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const textElement = screen.getByText(/want to play/i);
    expect(textElement).toHaveClass('text-sm');
  });

  it('given the game is initially on the want to play list, passes correct initial state to the hook', () => {
    // ARRANGE
    const mockGame = createGame();

    render(<WantToPlayToggle />, {
      pageProps: {
        backingGame: mockGame,
        isOnWantToPlayList: true, // !!
      },
    });

    // ASSERT
    expect(useGameBacklogState).toHaveBeenCalledWith({
      game: mockGame,
      isInitiallyInBacklog: true,
      userGameListType: 'play',
    });
  });

  it('given the game is initially not on the want to play list, passes correct initial state to the hook', () => {
    // ARRANGE
    const mockGame = createGame();

    render(<WantToPlayToggle />, {
      pageProps: {
        backingGame: mockGame,
        isOnWantToPlayList: false, // !!
      },
    });

    // ASSERT
    expect(useGameBacklogState).toHaveBeenCalledWith({
      game: mockGame,
      isInitiallyInBacklog: false,
      userGameListType: 'play',
    });
  });

  it('given showSubsetIndicator is not set, does not show the subset icon', () => {
    // ARRANGE
    render(<WantToPlayToggle />, {
      pageProps: {
        backingGame: createGame(),
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(screen.queryByLabelText(/subset/i)).not.toBeInTheDocument();
  });

  it('given showSubsetIndicator is true, shows the subset icon', () => {
    // ARRANGE
    render(<WantToPlayToggle showSubsetIndicator={true} />, {
      pageProps: {
        backingGame: createGame(),
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(screen.getByLabelText(/subset/i)).toBeVisible();
  });
});
