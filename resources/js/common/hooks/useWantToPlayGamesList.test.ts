import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { renderHook, screen } from '@/test';

import { useWantToPlayGamesList } from './useWantToPlayGamesList';

window.HTMLElement.prototype.setPointerCapture = vi.fn();

describe('Hook: useWantToPlayGamesList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useWantToPlayGamesList());

    // ASSERT
    expect(result).toBeDefined();
  });

  it('exposes an add and remove function to the consumer, as well as a loading state', () => {
    // ARRANGE
    const { result } = renderHook(() => useWantToPlayGamesList());

    const { addToWantToPlayGamesList, isPending, removeFromWantToPlayGamesList } =
      result.current as ReturnType<typeof useWantToPlayGamesList>;

    // ASSERT
    expect(addToWantToPlayGamesList).toBeDefined();
    expect(isPending).toBeDefined();
    expect(removeFromWantToPlayGamesList).toBeDefined();
  });

  it("allows the consumer to make a call to add a game to the user's backlog", async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    const { result } = renderHook(() => useWantToPlayGamesList());

    const { addToWantToPlayGamesList } = result.current as ReturnType<
      typeof useWantToPlayGamesList
    >;

    // ACT
    const response = await addToWantToPlayGamesList(1, 'Sonic the Hedgehog', {
      shouldDisableToast: true,
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

    const { result } = renderHook(() => useWantToPlayGamesList());

    const { addToWantToPlayGamesList } = result.current as ReturnType<
      typeof useWantToPlayGamesList
    >;

    // ACT
    await addToWantToPlayGamesList(1, 'Sonic the Hedgehog');

    // ASSERT
    expect(await screen.findByText(/added sonic the hedgehog/i)).toBeVisible();
  });

  it('given a game is being added as a restore/undo, slightly tweaks the toast message', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    const { result } = renderHook(() => useWantToPlayGamesList());

    const { addToWantToPlayGamesList } = result.current as ReturnType<
      typeof useWantToPlayGamesList
    >;

    // ACT
    await addToWantToPlayGamesList(1, 'Sonic the Hedgehog', { isUndo: true });

    // ASSERT
    expect(await screen.findByText(/restored sonic the hedgehog/i)).toBeVisible();
  });

  it('on add, a custom toast success message can be used', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    const { result } = renderHook(() => useWantToPlayGamesList());

    const { addToWantToPlayGamesList } = result.current as ReturnType<
      typeof useWantToPlayGamesList
    >;

    // ACT
    await addToWantToPlayGamesList(1, 'Sonic the Hedgehog', { t_successMessage: 'Added!' });

    // ASSERT
    expect(await screen.findByText(/added!/i)).toBeVisible();
  });

  it("allows the consumer to make a call to remove a game to the user's backlog", async () => {
    // ARRANGE
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    const { result } = renderHook(() => useWantToPlayGamesList());

    const { removeFromWantToPlayGamesList } = result.current as ReturnType<
      typeof useWantToPlayGamesList
    >;

    // ACT
    const response = await removeFromWantToPlayGamesList(1, 'Sonic the Hedgehog', {
      shouldDisableToast: true,
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

    const { result } = renderHook(() => useWantToPlayGamesList());

    const { removeFromWantToPlayGamesList } = result.current as ReturnType<
      typeof useWantToPlayGamesList
    >;

    // ACT
    await removeFromWantToPlayGamesList(1, 'Sonic the Hedgehog');

    // ASSERT
    expect(await screen.findByText(/removed sonic the hedgehog/i)).toBeVisible();
    expect(screen.getByRole('button', { name: /undo/i })).toBeVisible();
  });

  it('on remove, a custom toast success message can be used', async () => {
    // ARRANGE
    vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    const { result } = renderHook(() => useWantToPlayGamesList());

    const { removeFromWantToPlayGamesList } = result.current as ReturnType<
      typeof useWantToPlayGamesList
    >;

    // ACT
    await removeFromWantToPlayGamesList(1, 'Sonic the Hedgehog', { t_successMessage: 'Removed!' });

    // ASSERT
    expect(await screen.findByText(/removed!/i)).toBeVisible();
  });

  it('on remove, the user can click an undo button in the popped toast to re-add the game to their backlog', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });
    vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    const { result } = renderHook(() => useWantToPlayGamesList());

    const { removeFromWantToPlayGamesList } = result.current as ReturnType<
      typeof useWantToPlayGamesList
    >;

    // ACT
    await removeFromWantToPlayGamesList(1, 'Sonic the Hedgehog');
    await userEvent.click(await screen.findByRole('button', { name: /undo/i }));

    // ASSERT
    expect(await screen.findByText(/restored sonic the hedgehog/i)).toBeVisible();
    expect(postSpy).toHaveBeenCalledTimes(1);
    expect(postSpy).toHaveBeenCalledWith(['api.user-game-list.store', 1], {
      userGameListType: 'play',
    });
  });
});
