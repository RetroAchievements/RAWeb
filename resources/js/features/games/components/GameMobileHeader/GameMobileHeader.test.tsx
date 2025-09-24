/* eslint-disable testing-library/no-node-access */
/* eslint-disable testing-library/no-container */

import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';

import { useGameBacklogState } from '@/features/game-list/components/GameListItems/useGameBacklogState';
import { render, screen } from '@/test';
import { createGame, createSystem } from '@/test/factories';

import { GameMobileHeader } from './GameMobileHeader';

const mockToggleBacklog = vi.fn();
vi.mock('@/features/game-list/components/GameListItems/useGameBacklogState', () => ({
  useGameBacklogState: vi.fn(() => ({
    toggleBacklog: mockToggleBacklog,
    isInBacklogMaybeOptimistic: false,
  })),
}));

vi.mock('@/common/components/BetaFeedbackDialog', () => ({
  BetaFeedbackDialog: ({ children }: { children: ReactNode }) => <div>{children}</div>,
}));

describe('Component: GameMobileHeader', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameMobileHeader />, {
      pageProps: {
        backingGame: createGame(),
        game: createGame(),
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the game title', () => {
    // ARRANGE
    const game = createGame({ title: 'Super Mario World' });

    render(<GameMobileHeader />, {
      pageProps: {
        game,
        backingGame: game,
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(screen.getByText('Super Mario World')).toBeVisible();
  });

  it('displays the system icon and name', () => {
    // ARRANGE
    const system = createSystem({
      nameShort: 'SNES',
      iconUrl: 'https://example.com/snes.png',
    });
    const game = createGame({ system });

    render(<GameMobileHeader />, {
      pageProps: {
        game,
        backingGame: game,
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(screen.getByText('SNES')).toBeVisible();
    expect(screen.getByRole('img', { name: 'SNES' })).toBeVisible();
  });

  it('given the user clicks the want to play toggle, calls the toggle function', async () => {
    // ARRANGE
    const game = createGame();

    render(<GameMobileHeader />, {
      pageProps: {
        game,
        backingGame: game,
        isOnWantToPlayList: false,
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /want to play/i }));

    // ASSERT
    expect(mockToggleBacklog).toHaveBeenCalledTimes(1);
  });

  it('given the game is already on the want to play list, shows the button as pressed', () => {
    // ARRANGE
    vi.mocked(useGameBacklogState).mockReturnValueOnce({
      toggleBacklog: mockToggleBacklog,
      isInBacklogMaybeOptimistic: true, // !!
    } as any);

    const game = createGame();

    render(<GameMobileHeader />, {
      pageProps: {
        game,
        backingGame: game,
        isOnWantToPlayList: true,
      },
    });

    // ASSERT
    const button = screen.getByRole('button', { name: /want to play/i });
    expect(button).toHaveAttribute('aria-pressed', 'true');
  });

  it('given the game is not on the want to play list, shows the button as not pressed', () => {
    // ARRANGE
    const game = createGame();

    render(<GameMobileHeader />, {
      pageProps: {
        game,
        backingGame: game,
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const button = screen.getByRole('button', { name: /want to play/i });
    expect(button).toHaveAttribute('aria-pressed', 'false');
  });

  it('given the user can submit beta feedback, shows the feedback button', () => {
    // ARRANGE
    const game = createGame();

    render(<GameMobileHeader />, {
      pageProps: {
        game,
        backingGame: game,
        canSubmitBetaFeedback: true, // !!
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /give feedback/i })).toBeVisible();
  });

  it('given the user cannot submit beta feedback, does not show the feedback button', () => {
    // ARRANGE
    const game = createGame();

    render(<GameMobileHeader />, {
      pageProps: {
        game,
        backingGame: game,
        canSubmitBetaFeedback: false, // !!
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /give feedback/i })).not.toBeInTheDocument();
  });

  it('given the game is for the Nintendo DS, applies special background image styling', () => {
    // ARRANGE
    const system = createSystem({ id: 18, nameShort: 'NDS' });
    const game = createGame({
      system,
      imageIngameUrl: 'https://example.com/game.jpg',
    });

    const { container } = render(<GameMobileHeader />, {
      pageProps: {
        game,
        backingGame: game,
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const backgroundDiv = container.querySelector('[style*="background-image"]');
    expect(backgroundDiv).toHaveStyle({
      backgroundSize: '100% auto',
      backgroundPosition: 'center 0%',
    });
  });

  it('given the game is not for the Nintendo DS, applies standard background styling', () => {
    // ARRANGE
    const system = createSystem({ id: 1, nameShort: 'SNES' });
    const game = createGame({
      system,
      imageIngameUrl: 'https://example.com/game.jpg',
    });

    const { container } = render(<GameMobileHeader />, {
      pageProps: {
        game,
        backingGame: game,
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const backgroundDiv = container.querySelector('[style*="background-image"]');
    expect(backgroundDiv).toHaveStyle({
      backgroundSize: 'cover',
      backgroundPosition: 'center',
    });
  });

  it('given the game title is longer than 22 characters, uses XL font size', () => {
    // ARRANGE
    const game = createGame({
      title: '12345678901234567890123',
    });

    render(<GameMobileHeader />, {
      pageProps: {
        game,
        backingGame: game,
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const titleElement = screen.getByRole('heading', { level: 1 });
    expect(titleElement).toHaveClass('!text-xl');
  });

  it('given the game title is longer than 40 characters, uses base font size', () => {
    // ARRANGE
    const game = createGame({
      title: '12345678901234567890123456789012345678901',
    });

    render(<GameMobileHeader />, {
      pageProps: {
        game,
        backingGame: game,
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const titleElement = screen.getByRole('heading', { level: 1 });
    expect(titleElement).toHaveClass('!text-base');
  });

  it('given the game title is longer than 60 characters, uses SM font size', () => {
    // ARRANGE
    const game = createGame({
      title: '1234567890123456789012345678901234567890123456789012345678901234567890',
    });

    render(<GameMobileHeader />, {
      pageProps: {
        game,
        backingGame: game,
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const titleElement = screen.getByRole('heading', { level: 1 });
    expect(titleElement).toHaveClass('!text-sm');
  });

  it('given the game title is 22 characters or less, uses larger font size', () => {
    // ARRANGE
    const game = createGame({
      title: 'Short Title',
    });

    render(<GameMobileHeader />, {
      pageProps: {
        game,
        backingGame: game,
        isOnWantToPlayList: false,
      },
    });

    // ASSERT
    const titleElement = screen.getByRole('heading', { level: 1 });
    expect(titleElement).toHaveClass('text-2xl');
    expect(titleElement).not.toHaveClass('text-xl');
  });
});
