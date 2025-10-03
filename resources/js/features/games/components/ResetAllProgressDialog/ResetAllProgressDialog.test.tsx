import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { isResetAllProgressDialogOpenAtom } from '@/features/games/state/games.atoms';
import { render, screen, waitFor } from '@/test';
import { createGame } from '@/test/factories';

import { ResetAllProgressDialog } from './ResetAllProgressDialog';

describe('Component: ResetAllProgressDialog', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(router, 'reload').mockImplementation(vi.fn());
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ResetAllProgressDialog />, {
      pageProps: { backingGame: createGame() },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the dialog is closed, does not show dialog content', () => {
    // ARRANGE
    render(<ResetAllProgressDialog />, {
      pageProps: { backingGame: createGame() },
      jotaiAtoms: [
        [isResetAllProgressDialogOpenAtom, false],
        //
      ],
    });

    // ASSERT
    expect(screen.queryByText(/are you sure/i)).not.toBeInTheDocument();
    expect(
      screen.queryByText(/this will remove all your unlocked achievements/i),
    ).not.toBeInTheDocument();
  });

  it('given the dialog is open, shows the confirmation dialog content', () => {
    // ARRANGE
    render(<ResetAllProgressDialog />, {
      pageProps: { backingGame: createGame() },
      jotaiAtoms: [
        [isResetAllProgressDialogOpenAtom, true],
        //
      ],
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /reset progress/i })).toBeVisible();
    expect(screen.getByText(/this cannot be reversed/i)).toBeVisible();
    expect(screen.getByRole('checkbox', { name: /i understand/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /cancel/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /reset progress/i })).toBeDisabled();
  });

  it('given the user clicks the cancel button, closes the dialog', async () => {
    // ARRANGE
    render(<ResetAllProgressDialog />, {
      pageProps: { backingGame: createGame() },
      jotaiAtoms: [
        [isResetAllProgressDialogOpenAtom, true],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /cancel/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
  });

  it('given the user ticks the checkbox and presses the continue button, makes the API call and shows success toast', async () => {
    // ARRANGE
    const mockGame = createGame({ id: 12345 });
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ data: { success: true } });

    render(<ResetAllProgressDialog />, {
      pageProps: { backingGame: mockGame },
      jotaiAtoms: [
        [isResetAllProgressDialogOpenAtom, true],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getByRole('checkbox'));
    await userEvent.click(screen.getByRole('button', { name: /reset progress/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith(route('api.user.game.destroy', { game: 12345 }));

    await waitFor(() => {
      expect(screen.getByText(/progress was reset successfully/i)).toBeVisible();
    });

    expect(router.reload).toHaveBeenCalled();
  });

  it('given the user clicks continue and the request fails, shows error toast and does not reload', async () => {
    // ARRANGE
    const mockGame = createGame({ id: 12345 });
    const deleteSpy = vi.spyOn(axios, 'delete').mockRejectedValueOnce(new Error('Network error'));

    render(<ResetAllProgressDialog />, {
      pageProps: { backingGame: mockGame },
      jotaiAtoms: [
        [isResetAllProgressDialogOpenAtom, true],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getByRole('checkbox'));
    await userEvent.click(screen.getByRole('button', { name: /reset progress/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith(route('api.user.game.destroy', { game: 12345 }));

    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });

    expect(router.reload).not.toHaveBeenCalled();
  });
});
