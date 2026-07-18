import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';
import { createGame } from '@/test/factories';

import { ResponsiveManageChip } from './ResponsiveManageChip';

describe('Component: ResponsiveManageChip', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ResponsiveManageChip />, {
      pageProps: {
        can: {},
        backingGame: createGame(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('has an accessible label', () => {
    // ARRANGE
    const backingGame = createGame();

    render(<ResponsiveManageChip />, {
      pageProps: {
        can: {},
        backingGame,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /manage/i })).toBeVisible();
  });

  it('opens in a new tab', () => {
    // ARRANGE
    const backingGame = createGame();

    render(<ResponsiveManageChip />, {
      pageProps: {
        can: {},
        backingGame,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /manage/i })).toHaveAttribute('target', '_blank');
  });

  it('given the user can update the backingGame, links to the edit page', () => {
    // ARRANGE
    const backingGame = createGame({ id: 1 });

    render(<ResponsiveManageChip />, {
      pageProps: {
        can: { updateGame: true },
        backingGame,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /manage/i })).toHaveAttribute(
      'href',
      '/manage/games/1/edit',
    );
  });

  it('given the user cannot update the backingGame, links to the details page', () => {
    // ARRANGE
    const backingGame = createGame({ id: 1 });

    render(<ResponsiveManageChip />, {
      pageProps: {
        can: { updateGame: false },
        backingGame,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /manage/i })).toHaveAttribute(
      'href',
      '/manage/games/1',
    );
  });

  it('given the user hovers over the chip, shows the manage label text', async () => {
    // ARRANGE
    const backingGame = createGame();

    render(<ResponsiveManageChip />, {
      pageProps: {
        can: {},
        backingGame,
      },
    });

    // ACT
    await userEvent.hover(screen.getByRole('link', { name: /manage/i }));

    // ASSERT
    expect(screen.getByText(/manage/i)).toBeVisible();
  });

  it('given the user stops hovering over the chip, hides the manage label text', async () => {
    // ARRANGE
    const backingGame = createGame();

    render(<ResponsiveManageChip />, {
      pageProps: {
        can: {},
        backingGame,
      },
    });

    // ACT
    await userEvent.hover(screen.getByRole('link', { name: /manage/i }));
    await userEvent.unhover(screen.getByRole('link', { name: /manage/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.queryByText(/manage/i)).not.toBeInTheDocument();
    });
  });
});
