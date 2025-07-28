import userEvent from '@testing-library/user-event';
import axios from 'axios';
// eslint-disable-next-line no-restricted-imports -- this is fine in a test
import { toast } from 'sonner';

import i18n from '@/i18n-client';
import { renderHook, screen, waitFor } from '@/test';

import { useAddOrRemoveFromUserGameList } from './useAddOrRemoveFromUserGameList';

window.HTMLElement.prototype.setPointerCapture = vi.fn();

describe('Hook: useAddOrRemoveFromUserGameList', () => {
  beforeEach(() => {
    // Clear all timers before each test.
    vi.clearAllTimers();
  });

  afterEach(() => {
    // Clean up all active toasts after each test to prevent timers from running after teardown.
    toast.dismiss();
    vi.clearAllTimers();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useAddOrRemoveFromUserGameList());

    // ASSERT
    expect(result).toBeDefined();
  });

  it('exposes an add and remove function to the consumer, as well as a loading state', () => {
    // ARRANGE
    const { result } = renderHook(() => useAddOrRemoveFromUserGameList());

    // ASSERT
    expect(result.current.addToGameList).toBeDefined();
    expect(result.current.isPending).toBeDefined();
    expect(result.current.removeFromGameList).toBeDefined();
  });

  it("allows the consumer to make a call to add a game to the user's backlog", async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    const { result } = renderHook(() => useAddOrRemoveFromUserGameList());

    // ACT
    const response = await result.current.addToGameList(1, 'Sonic the Hedgehog', {
      shouldEnableToast: true,
    });

    // ASSERT
    expect(postSpy).toHaveBeenCalledTimes(1);
    expect(postSpy).toHaveBeenCalledWith(['api.user-game-list.store', 1], {
      userGameListType: 'play',
    });
    expect(response).toEqual({ success: true });
  });

  it('given a game is successfully added to the backlog, by default pops a toast on success', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    const { result } = renderHook(() => useAddOrRemoveFromUserGameList());

    // ACT
    await result.current.addToGameList(1, 'Sonic the Hedgehog');

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/added sonic the hedgehog/i)).toBeVisible();
    });
  });

  it('given a game is being added as a restore/undo, slightly tweaks the toast message', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    const { result } = renderHook(() => useAddOrRemoveFromUserGameList());

    // ACT
    await result.current.addToGameList(1, 'Sonic the Hedgehog', { isUndo: true });

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/restored sonic the hedgehog/i)).toBeVisible();
    });
  });

  it('on add, a custom toast success message can be used', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    const { result } = renderHook(() => useAddOrRemoveFromUserGameList());

    // ACT
    await result.current.addToGameList(1, 'Sonic the Hedgehog', {
      t_successMessage: i18n.t('Added {{gameTitle}}!'),
    });

    // ASSERT
    expect(await screen.findByText(/added/i)).toBeVisible();
  });

  it("allows the consumer to make a call to remove a game to the user's backlog", async () => {
    // ARRANGE
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    const { result } = renderHook(() => useAddOrRemoveFromUserGameList());

    // ACT
    const response = await result.current.removeFromGameList(1, 'Sonic the Hedgehog', {
      shouldEnableToast: true,
    });

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledTimes(1);
    expect(deleteSpy).toHaveBeenCalledWith(['api.user-game-list.destroy', 1], {
      data: {
        userGameListType: 'play',
      },
    });
    expect(response).toEqual({ success: true });
  });

  it('given a game is successfully removed from the backlog, by default pops a toast on success', async () => {
    // ARRANGE
    vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    const { result } = renderHook(() => useAddOrRemoveFromUserGameList());

    // ACT
    await result.current.removeFromGameList(1, 'Sonic the Hedgehog');

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/removed sonic the hedgehog/i)).toBeVisible();
    });

    expect(screen.getByRole('button', { name: /undo/i })).toBeVisible();
  });

  it('on remove, a custom toast success message can be used', async () => {
    // ARRANGE
    vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    const { result } = renderHook(() => useAddOrRemoveFromUserGameList());

    // ACT
    await result.current.removeFromGameList(1, 'Sonic the Hedgehog', {
      t_successMessage: i18n.t('Removed {{gameTitle}}!'),
    });

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/removed/i)).toBeVisible();
    });
  });

  it('on remove, the user can click an undo button in the popped toast to re-add the game to their backlog', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });
    vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    const { result } = renderHook(() => useAddOrRemoveFromUserGameList());

    // ACT
    await result.current.removeFromGameList(1, 'Sonic the Hedgehog');
    await userEvent.click(await screen.findByRole('button', { name: /undo/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/restored sonic the hedgehog/i)).toBeVisible();
    });

    expect(postSpy).toHaveBeenCalledTimes(1);
    expect(postSpy).toHaveBeenCalledWith(['api.user-game-list.store', 1], {
      userGameListType: 'play',
    });
  });
});
