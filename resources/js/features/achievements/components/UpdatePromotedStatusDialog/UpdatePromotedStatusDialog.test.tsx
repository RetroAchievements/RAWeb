import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { isUpdatePromotedStatusDialogOpenAtom } from '@/features/achievements/state/achievements.atoms';
import { render, screen, waitFor } from '@/test';
import { createAchievement } from '@/test/factories';

import { AchievementInlineActions } from '../AchievementInlineActions';
import { UpdatePromotedStatusDialog } from './UpdatePromotedStatusDialog';

describe('Component: UpdatePromotedStatusDialog', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(router, 'reload').mockImplementation(vi.fn());
  });

  it('renders without crashing', () => {
    // ARRANGE
    const achievement = createAchievement();

    const { container } = render(<UpdatePromotedStatusDialog />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an unpromoted achievement, shows a promote confirmation message', async () => {
    // ARRANGE
    const achievement = createAchievement({ isPromoted: false });

    render(
      <>
        <AchievementInlineActions />
        <UpdatePromotedStatusDialog />
      </>,
      {
        pageProps: {
          achievement,
          can: {
            develop: true,
            quickEditAchievement: true,
            updateAchievementIsPromoted: true,
            viewAchievementLogic: false,
          },
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'More actions' }));
    await userEvent.click(screen.getByRole('menuitem', { name: /quick edit/i }));
    await userEvent.click(screen.getByRole('button', { name: /promote/i }));

    // ASSERT
    expect(screen.getByText(/are you sure you want to promote this achievement/i)).toBeVisible();
  });

  it('given a promoted achievement, shows a demote confirmation message', async () => {
    // ARRANGE
    const achievement = createAchievement({ isPromoted: true });

    render(
      <>
        <AchievementInlineActions />
        <UpdatePromotedStatusDialog />
      </>,
      {
        pageProps: {
          achievement,
          can: {
            develop: true,
            quickEditAchievement: true,
            updateAchievementIsPromoted: true,
            viewAchievementLogic: false,
          },
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'More actions' }));
    await userEvent.click(screen.getByRole('menuitem', { name: /quick edit/i }));
    await userEvent.click(screen.getByRole('button', { name: /demote/i }));

    // ASSERT
    expect(screen.getByText(/are you sure you want to demote this achievement/i)).toBeVisible();
  });

  it('given an unpromoted achievement, shows a Promote confirm button', async () => {
    // ARRANGE
    const achievement = createAchievement({ isPromoted: false });

    render(
      <>
        <AchievementInlineActions />
        <UpdatePromotedStatusDialog />
      </>,
      {
        pageProps: {
          achievement,
          can: {
            develop: true,
            quickEditAchievement: true,
            updateAchievementIsPromoted: true,
            viewAchievementLogic: false,
          },
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'More actions' }));
    await userEvent.click(screen.getByRole('menuitem', { name: /quick edit/i }));
    await userEvent.click(screen.getByRole('button', { name: /promote/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: 'Promote' })).toBeVisible();
  });

  it('given a promoted achievement, shows a Demote confirm button', async () => {
    // ARRANGE
    const achievement = createAchievement({ isPromoted: true });

    render(
      <>
        <AchievementInlineActions />
        <UpdatePromotedStatusDialog />
      </>,
      {
        pageProps: {
          achievement,
          can: {
            develop: true,
            quickEditAchievement: true,
            updateAchievementIsPromoted: true,
            viewAchievementLogic: false,
          },
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'More actions' }));
    await userEvent.click(screen.getByRole('menuitem', { name: /quick edit/i }));
    await userEvent.click(screen.getByRole('button', { name: 'Demote' }));

    // ASSERT
    expect(screen.getByRole('dialog')).toBeVisible();
  });

  it('given the user confirms promoting, makes the API call and shows a success toast', async () => {
    // ARRANGE
    const achievement = createAchievement({ id: 12345, isPromoted: false });
    const patchSpy = vi.spyOn(axios, 'patch').mockResolvedValueOnce({ data: { success: true } });

    render(<UpdatePromotedStatusDialog />, {
      pageProps: { achievement },
      jotaiAtoms: [
        [isUpdatePromotedStatusDialogOpenAtom, true],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Promote' }));

    // ASSERT
    expect(patchSpy).toHaveBeenCalledWith(route('api.achievement.update', { achievement: 12345 }), {
      isPromoted: true,
    });

    await waitFor(() => {
      expect(screen.getByText(/promoted!/i)).toBeVisible();
    });

    expect(router.reload).toHaveBeenCalled();
  });

  it('given the user confirms demoting, makes the API call and shows a success toast', async () => {
    // ARRANGE
    const achievement = createAchievement({ id: 12345, isPromoted: true });
    const patchSpy = vi.spyOn(axios, 'patch').mockResolvedValueOnce({ data: { success: true } });

    render(<UpdatePromotedStatusDialog />, {
      pageProps: { achievement },
      jotaiAtoms: [
        [isUpdatePromotedStatusDialogOpenAtom, true],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Demote' }));

    // ASSERT
    expect(patchSpy).toHaveBeenCalledWith(route('api.achievement.update', { achievement: 12345 }), {
      isPromoted: false,
    });

    await waitFor(() => {
      expect(screen.getByText(/demoted!/i)).toBeVisible();
    });

    expect(router.reload).toHaveBeenCalled();
  });

  it('given the API call fails, shows an error toast and does not reload', async () => {
    // ARRANGE
    const achievement = createAchievement({ id: 12345, isPromoted: false });
    vi.spyOn(axios, 'patch').mockRejectedValueOnce(new Error('Network error'));

    render(<UpdatePromotedStatusDialog />, {
      pageProps: { achievement },
      jotaiAtoms: [
        [isUpdatePromotedStatusDialogOpenAtom, true],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Promote' }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });

    expect(router.reload).not.toHaveBeenCalled();
  });
});
