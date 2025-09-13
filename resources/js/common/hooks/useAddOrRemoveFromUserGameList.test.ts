import axios from 'axios';

import i18n from '@/i18n-client';
import { renderHook } from '@/test';

import { useAddOrRemoveFromUserGameList } from './useAddOrRemoveFromUserGameList';

// Mock sonner to prevent timer issues (causes flakiness in vitest).
vi.mock('sonner', () => ({
  toast: {
    dismiss: vi.fn(),
  },
  Toaster: () => null,
}));

// Mock BaseToaster to prevent real toasts with timers (causes flakiness in vitest).
vi.mock('@/common/components/+vendor/BaseToaster', () => ({
  BaseToaster: () => null,
  toastMessage: {
    promise: vi.fn((promise) => promise),
  },
}));

window.HTMLElement.prototype.setPointerCapture = vi.fn();

describe('Hook: useAddOrRemoveFromUserGameList', () => {
  afterEach(() => {
    vi.clearAllMocks();
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

  it('given a game is successfully added to the backlog, by default calls toast', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });
    const { toastMessage } = await import('@/common/components/+vendor/BaseToaster');

    const { result } = renderHook(() => useAddOrRemoveFromUserGameList());

    // ACT
    await result.current.addToGameList(1, 'Sonic the Hedgehog');

    // ASSERT
    expect(toastMessage.promise).toHaveBeenCalled();
  });

  it('given a game is being added as a restore/undo, calls toast with restore message', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });
    const { toastMessage } = await import('@/common/components/+vendor/BaseToaster');

    const { result } = renderHook(() => useAddOrRemoveFromUserGameList());

    // ACT
    await result.current.addToGameList(1, 'Sonic the Hedgehog', { isUndo: true });

    // ASSERT
    expect(toastMessage.promise).toHaveBeenCalled();
  });

  it('on add, a custom toast success message can be used', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });
    const { toastMessage } = await import('@/common/components/+vendor/BaseToaster');

    const { result } = renderHook(() => useAddOrRemoveFromUserGameList());

    // ACT
    await result.current.addToGameList(1, 'Sonic the Hedgehog', {
      t_successMessage: i18n.t('Added {{gameTitle}}!'),
    });

    // ASSERT
    expect(toastMessage.promise).toHaveBeenCalled();
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

  it('given a game is successfully removed from the backlog, by default calls toast with undo action', async () => {
    // ARRANGE
    vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });
    const { toastMessage } = await import('@/common/components/+vendor/BaseToaster');

    const { result } = renderHook(() => useAddOrRemoveFromUserGameList());

    // ACT
    await result.current.removeFromGameList(1, 'Sonic the Hedgehog');

    // ASSERT
    expect(toastMessage.promise).toHaveBeenCalled();
    const lastCall = vi.mocked(toastMessage.promise).mock.calls[0];
    expect(lastCall[1]).toHaveProperty('action');
    expect(lastCall[1]!.action).toHaveProperty('label', 'Undo');
  });

  it('on remove, a custom toast success message can be used', async () => {
    // ARRANGE
    vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });
    const { toastMessage } = await import('@/common/components/+vendor/BaseToaster');

    const { result } = renderHook(() => useAddOrRemoveFromUserGameList());

    // ACT
    await result.current.removeFromGameList(1, 'Sonic the Hedgehog', {
      t_successMessage: i18n.t('Removed {{gameTitle}}!'),
    });

    // ASSERT
    expect(toastMessage.promise).toHaveBeenCalled();
  });

  it('on remove, the undo action callback re-adds the game to their backlog', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });
    vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });
    const { toastMessage } = await import('@/common/components/+vendor/BaseToaster');

    const { result } = renderHook(() => useAddOrRemoveFromUserGameList());

    // ACT
    await result.current.removeFromGameList(1, 'Sonic the Hedgehog');

    const lastCall = vi.mocked(toastMessage.promise).mock.calls[0];
    const undoAction = (lastCall[1]!.action! as any).onClick!;
    await undoAction();

    // ASSERT
    expect(postSpy).toHaveBeenCalledTimes(1);
    expect(postSpy).toHaveBeenCalledWith(['api.user-game-list.store', 1], {
      userGameListType: 'play',
    });
  });
});
