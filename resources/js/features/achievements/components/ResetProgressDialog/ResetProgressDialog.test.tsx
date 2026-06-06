import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { isResetProgressDialogOpenAtom } from '@/features/achievements/state/achievements.atoms';
import { render, screen, waitFor } from '@/test';
import { createAchievement } from '@/test/factories';

import { ResetProgressDialog } from './ResetProgressDialog';

describe('Component: ResetProgressDialog', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(router, 'reload').mockImplementation(vi.fn());
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ResetProgressDialog />, {
      pageProps: { achievement: createAchievement() },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the dialog is closed, does not show dialog content', () => {
    // ARRANGE
    render(<ResetProgressDialog />, {
      pageProps: { achievement: createAchievement() },
      jotaiAtoms: [
        [isResetProgressDialogOpenAtom, false],
        //
      ],
    });

    // ASSERT
    expect(screen.queryByRole('heading', { name: /reset progress/i })).not.toBeInTheDocument();
  });

  it('given the dialog is open, shows the confirmation dialog content', () => {
    // ARRANGE
    render(<ResetProgressDialog />, {
      pageProps: { achievement: createAchievement() },
      jotaiAtoms: [
        [isResetProgressDialogOpenAtom, true],
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
    render(<ResetProgressDialog />, {
      pageProps: { achievement: createAchievement() },
      jotaiAtoms: [
        [isResetProgressDialogOpenAtom, true],
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

  it('given the user ticks the checkbox and presses the confirm button, makes the API call and shows success toast', async () => {
    // ARRANGE
    const achievement = createAchievement({ id: 54321 });
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ data: { success: true } });

    render(<ResetProgressDialog />, {
      pageProps: { achievement },
      jotaiAtoms: [
        [isResetProgressDialogOpenAtom, true],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getByRole('checkbox'));
    await userEvent.click(screen.getByRole('button', { name: /reset progress/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith(
      route('api.user.achievement.destroy', { achievement: 54321 }),
    );

    await waitFor(() => {
      expect(screen.getByText(/progress was reset successfully/i)).toBeVisible();
    });

    expect(router.reload).toHaveBeenCalled();
  });

  it('given the user clicks confirm and the request fails, shows error toast and does not reload', async () => {
    // ARRANGE
    const achievement = createAchievement({ id: 54321 });
    const deleteSpy = vi.spyOn(axios, 'delete').mockRejectedValueOnce(new Error('Network error'));

    render(<ResetProgressDialog />, {
      pageProps: { achievement },
      jotaiAtoms: [
        [isResetProgressDialogOpenAtom, true],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getByRole('checkbox'));
    await userEvent.click(screen.getByRole('button', { name: /reset progress/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith(
      route('api.user.achievement.destroy', { achievement: 54321 }),
    );

    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });

    expect(router.reload).not.toHaveBeenCalled();
  });
});
