import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { isResetAllProgressDialogOpenAtom } from '@/features/games/state/games.atoms';
import { render, screen, waitFor } from '@/test';
import { createGame } from '@/test/factories';

import { ResetAllProgressAlertDialog } from './ResetAllProgressAlertDialog';

describe('Component: ResetAllProgressAlertDialog', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(router, 'reload').mockImplementation(vi.fn());
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ResetAllProgressAlertDialog />, {
      pageProps: { backingGame: createGame() },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the dialog is closed, does not show dialog content', () => {
    // ARRANGE
    render(<ResetAllProgressAlertDialog />, {
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
    render(<ResetAllProgressAlertDialog />, {
      pageProps: { backingGame: createGame() },
      jotaiAtoms: [
        [isResetAllProgressDialogOpenAtom, true],
        //
      ],
    });

    // ASSERT
    expect(screen.getByText(/are you sure/i)).toBeVisible();
    expect(screen.getByText(/this will remove all your unlocked achievements/i)).toBeVisible();
    expect(screen.getByRole('button', { name: /nevermind/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /continue/i })).toBeVisible();
  });

  it('given the user clicks the cancel button, closes the dialog', async () => {
    // ARRANGE
    render(<ResetAllProgressAlertDialog />, {
      pageProps: { backingGame: createGame() },
      jotaiAtoms: [
        [isResetAllProgressDialogOpenAtom, true],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /nevermind/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.queryByText(/are you sure/i)).not.toBeInTheDocument();
    });
  });

  it('given the user clicks continue, makes the API call and shows success toast', async () => {
    // ARRANGE
    const mockGame = createGame({ id: 12345 });
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ data: { success: true } });

    render(<ResetAllProgressAlertDialog />, {
      pageProps: { backingGame: mockGame },
      jotaiAtoms: [
        [isResetAllProgressDialogOpenAtom, true],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /continue/i }));

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

    render(<ResetAllProgressAlertDialog />, {
      pageProps: { backingGame: mockGame },
      jotaiAtoms: [
        [isResetAllProgressDialogOpenAtom, true],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /continue/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith(route('api.user.game.destroy', { game: 12345 }));

    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });

    expect(router.reload).not.toHaveBeenCalled();
  });
});
