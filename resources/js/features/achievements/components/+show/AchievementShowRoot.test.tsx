import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { act, render, screen, waitFor } from '@/test';
import { createAchievement, createComment, createGame, createSystem } from '@/test/factories';

import { AchievementShowRoot } from './AchievementShowRoot';

describe('Component: AchievementShowRoot', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    vi.spyOn(router, 'reload').mockImplementation((options?: Record<string, unknown>) => {
      if (options && typeof options.onFinish === 'function') {
        (options.onFinish as () => void)();
      }
    });

    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    window.HTMLElement.prototype.setPointerCapture = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    const { container } = render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays breadcrumbs with the achievement game when there is no backing game', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ title: 'Sonic the Hedgehog', system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    const breadcrumbNav = screen.getByRole('navigation', { name: /breadcrumb/i });
    expect(breadcrumbNav).toHaveTextContent(/sonic the hedgehog/i);
  });

  it('displays breadcrumbs with the backing game when the achievement belongs to a subset', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ title: 'Sonic the Hedgehog [Subset - Bonus]', system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    const backingGame = createGame({ title: 'Sonic the Hedgehog', system: createSystem() });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(screen.getAllByText(/sonic the hedgehog/i)[0]).toBeVisible();
  });

  it('defaults to showing the comments tab', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 2,
        recentVisibleComments: [createComment({ payload: 'Great achievement!' })],
      },
    });

    // ASSERT
    expect(screen.getByText(/great achievement!/i)).toBeVisible();
  });

  it('allows switching to the unlocks tab', async () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ACT
    await userEvent.click(screen.getAllByRole('tab', { name: /unlocks/i })[0]);

    // ASSERT
    expect(screen.getByRole('table')).toBeVisible();
  });

  it('given the user hovers over an inactive tab, applies the hover text style', async () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ACT
    const unlocksTabs = screen.getAllByRole('tab', { name: /unlocks/i });
    await userEvent.hover(unlocksTabs[0]);
    await userEvent.unhover(unlocksTabs[0]);

    // ASSERT
    expect(unlocksTabs[0]).toBeVisible();
  });

  it('given the user hovers between tabs sequentially, applies the slide transition', async () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ACT
    await userEvent.hover(screen.getAllByRole('tab', { name: /unlocks/i })[0]);
    await userEvent.hover(screen.getAllByRole('tab', { name: /changelog/i })[0]);

    // ASSERT
    expect(screen.getAllByRole('tab', { name: /changelog/i })[0]).toBeVisible();
  });

  it('given the animation becomes ready, applies cubic-bezier timing to the active indicator', () => {
    // ARRANGE
    vi.useFakeTimers();

    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ACT
    act(() => {
      vi.advanceTimersByTime(50);
    });

    // ASSERT
    const indicator = screen.getByTestId('tab-indicator');
    expect(indicator.style.transitionTimingFunction).toEqual('cubic-bezier(0.65, 0, 0.35, 1)');

    vi.useRealTimers();
  });

  it('given the achievement has an embed URL, shows the Media tab', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
      embedUrl: 'https://youtube.com/watch?v=dQw4w9WgXcQ',
    });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(screen.getAllByRole('tab', { name: /media/i }).length).toBeGreaterThan(0);
  });

  it('given the embed URL is an image, renders an img tag instead of a video embed', async () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
      embedUrl: 'https://i.imgur.com/7ma23Se.png',
    });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ACT
    await userEvent.click(screen.getAllByRole('tab', { name: /media/i })[0]);

    // ASSERT
    const imgEl = screen.getByRole('img', { name: 'Media' });
    expect(imgEl).toHaveAttribute('src', 'https://i.imgur.com/7ma23Se.png');
    expect(screen.queryByTestId('video-embed')).not.toBeInTheDocument();
  });

  it('given the achievement has no embed URL, does not show the Media tab', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(screen.queryAllByRole('tab', { name: /media/i })).toHaveLength(0);
  });

  it('given the user has unlocked the achievement, renders the reset progress link', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlockedAt: '2024-03-15T12:00:00Z',
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /reset progress/i })).toBeVisible();
  });

  it('given the user has not unlocked the achievement, does not render the reset progress link', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /reset progress/i })).not.toBeInTheDocument();
  });

  it('given the user can update promoted status, shows the promote/demote dialog when the button is clicked', async () => {
    // ARRANGE
    const achievement = createAchievement({
      isPromoted: false,
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: {
          createAchievementComments: false,
          develop: true,
          updateAchievementIsPromoted: true,
          viewAchievementLogic: false,
        },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ACT
    await userEvent.click(screen.getAllByRole('button', { name: /quick edit/i })[0]);
    await userEvent.click(screen.getByRole('button', { name: /promote/i }));

    // ASSERT
    expect(screen.getByText(/are you sure you want to promote this achievement/i)).toBeVisible();
  });

  it('allows switching to the changelog tab', async () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: { createAchievementComments: false },
        changelog: [],
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ACT
    await userEvent.click(screen.getAllByRole('tab', { name: /changelog/i })[0]);

    // ASSERT
    expect(screen.getByText(/no changelog entries found/i)).toBeVisible();
  });

  it('given the user edits the title and clicks Save, sends the updated title to the API', async () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 99,
      title: 'Old Title',
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    const patchSpy = vi.spyOn(axios, 'patch').mockResolvedValueOnce({ data: { success: true } });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: {
          createAchievementComments: false,
          develop: true,
          updateAchievementTitle: true,
          viewAchievementLogic: false,
        },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ACT
    await userEvent.click(screen.getAllByRole('button', { name: /quick edit/i })[0]);

    const titleInput = screen.getByRole('textbox', { name: 'Achievement title' });
    await userEvent.clear(titleInput);
    await userEvent.type(titleInput, 'New Title');

    await userEvent.click(screen.getByRole('button', { name: /save/i }));

    // ASSERT
    await waitFor(() => {
      expect(patchSpy).toHaveBeenCalledWith(route('api.achievement.update', { achievement: 99 }), {
        title: 'New Title',
      });
    });
  });

  it('given the user saves without making changes, does not call the API', async () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 99,
      title: 'Same Title',
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    const patchSpy = vi.spyOn(axios, 'patch');

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: {
          createAchievementComments: false,
          develop: true,
          updateAchievementTitle: true,
          viewAchievementLogic: false,
        },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ACT
    await userEvent.click(screen.getAllByRole('button', { name: /quick edit/i })[0]);
    await userEvent.click(screen.getByRole('button', { name: /save/i }));

    // ASSERT
    expect(patchSpy).not.toHaveBeenCalled();
  });

  it('given the user saves with changes and the API succeeds, shows a success toast', async () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 99,
      title: 'Old Title',
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    vi.spyOn(axios, 'patch').mockResolvedValueOnce({ data: { success: true } });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: {
          createAchievementComments: false,
          develop: true,
          updateAchievementTitle: true,
          viewAchievementLogic: false,
        },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ACT
    await userEvent.click(screen.getAllByRole('button', { name: /quick edit/i })[0]);

    const titleInput = screen.getByRole('textbox', { name: 'Achievement title' });
    await userEvent.clear(titleInput);
    await userEvent.type(titleInput, 'New Title');

    await userEvent.click(screen.getByRole('button', { name: /save/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/saved!/i)).toBeVisible();
    });

    expect(router.reload).toHaveBeenCalled();
  });

  it('given a successful save, exits edit mode after the page reloads', async () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 99,
      title: 'Old Title',
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    vi.spyOn(axios, 'patch').mockResolvedValueOnce({ data: { success: true } });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: {
          createAchievementComments: false,
          develop: true,
          updateAchievementTitle: true,
          viewAchievementLogic: false,
        },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ACT
    await userEvent.click(screen.getAllByRole('button', { name: /quick edit/i })[0]);

    const titleInput = screen.getByRole('textbox', { name: 'Achievement title' });
    await userEvent.clear(titleInput);
    await userEvent.type(titleInput, 'New Title');

    await userEvent.click(screen.getByRole('button', { name: /save/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/saved!/i)).toBeVisible();
    });

    expect(screen.queryByRole('button', { name: /save/i })).not.toBeInTheDocument();
  });

  it('given the user changes the points, sends the updated points to the API', async () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 77,
      points: 25,
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    const patchSpy = vi.spyOn(axios, 'patch').mockResolvedValueOnce({ data: { success: true } });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: {
          createAchievementComments: false,
          develop: true,
          updateAchievementPoints: true,
          viewAchievementLogic: false,
        },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ACT
    await userEvent.click(screen.getAllByRole('button', { name: /quick edit/i })[0]);
    await userEvent.click(screen.getAllByRole('combobox', { name: 'Achievement points' })[0]);
    await userEvent.click(screen.getByRole('option', { name: '10' }));
    await userEvent.click(screen.getByRole('button', { name: /save/i }));

    // ASSERT
    await waitFor(() => {
      expect(patchSpy).toHaveBeenCalledWith(route('api.achievement.update', { achievement: 77 }), {
        points: 10,
      });
    });
  });

  it('given the user changes the type to "none", sends null to the API', async () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 99,
      type: 'missable',
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    const patchSpy = vi.spyOn(axios, 'patch').mockResolvedValueOnce({ data: { success: true } });

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: {
          createAchievementComments: false,
          develop: true,
          updateAchievementType: true,
          viewAchievementLogic: false,
        },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ACT
    await userEvent.click(screen.getAllByRole('button', { name: /quick edit/i })[0]);
    await userEvent.click(screen.getAllByRole('combobox', { name: 'Achievement type' })[0]);
    await userEvent.click(screen.getByRole('option', { name: 'None' }));
    await userEvent.click(screen.getByRole('button', { name: /save/i }));

    // ASSERT
    await waitFor(() => {
      expect(patchSpy).toHaveBeenCalledWith(route('api.achievement.update', { achievement: 99 }), {
        type: null,
      });
    });
  });

  it('given the API returns an error when saving, shows an error toast', async () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 99,
      title: 'Old Title',
      game: createGame({ playersTotal: 1000, system: createSystem() }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    vi.spyOn(axios, 'patch').mockRejectedValueOnce(new Error('Server error'));

    render(<AchievementShowRoot />, {
      pageProps: {
        achievement,
        backingGame: null,
        gameAchievementSet: null,
        can: {
          createAchievementComments: false,
          develop: true,
          updateAchievementTitle: true,
          viewAchievementLogic: false,
        },
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ACT
    await userEvent.click(screen.getAllByRole('button', { name: /quick edit/i })[0]);

    const titleInput = screen.getByRole('textbox', { name: 'Achievement title' });
    await userEvent.clear(titleInput);
    await userEvent.type(titleInput, 'New Title');

    await userEvent.click(screen.getByRole('button', { name: /save/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });

    expect(router.reload).not.toHaveBeenCalled();
  });
});
