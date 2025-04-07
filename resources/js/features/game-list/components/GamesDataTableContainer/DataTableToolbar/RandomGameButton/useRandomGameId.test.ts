import axios from 'axios';

import { renderHook, waitFor } from '@/test';
import { createZiggyProps } from '@/test/factories';

import { useRandomGameId } from './useRandomGameId';

// Vitest is going to complain about not wrapping stuff in act(...).
// It doesn't really matter.
console.error = vi.fn();

describe('Hook: useRandomGameId', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(
      () => useRandomGameId({ apiRouteName: 'api.game.random', columnFilters: [] }),
      { pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) } },
    );

    // ASSERT
    expect(result.current.getRandomGameId).toBeDefined();
    expect(result.current.prefetchRandomGameId).toBeDefined();
  });

  it('given the game ID is already prefetched, does not try to prefetch again', async () => {
    // ARRANGE
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValue({ data: { gameId: 12345 } });

    const { result } = renderHook(
      () => useRandomGameId({ apiRouteName: 'api.game.random', columnFilters: [] }),
      { pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) } },
    );

    // ACT & ASSERT
    // First prefetch should work.
    result.current.prefetchRandomGameId();
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledTimes(1);
    });

    // Second prefetch should be skipped.
    result.current.prefetchRandomGameId();
    expect(getSpy).toHaveBeenCalledTimes(1);

    // Force prefetch should work even with existing prefetched ID.
    result.current.prefetchRandomGameId({ shouldForce: true });
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledTimes(2);
    });
  });

  it('given the mutation is already pending, skips prefetch unless forced', async () => {
    // ARRANGE
    let resolveFirstRequest: (value: { data: { gameId: number } }) => void;
    const pendingPromise = new Promise<{ data: { gameId: number } }>((resolve) => {
      resolveFirstRequest = resolve;
    });

    const getSpy = vi
      .spyOn(axios, 'get')
      .mockImplementationOnce(() => pendingPromise)
      .mockResolvedValueOnce({ data: { gameId: 67890 } });

    const { result } = renderHook(
      () => useRandomGameId({ apiRouteName: 'api.game.random', columnFilters: [] }),
      { pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) } },
    );

    // ACT & ASSERT
    // Start first request and wait for it to be in-flight.
    const firstPrefetchPromise = result.current.prefetchRandomGameId();
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledTimes(1);
    });

    // Try to prefetch while request is pending.
    result.current.prefetchRandomGameId();
    expect(getSpy).toHaveBeenCalledTimes(1); // Should still be 1

    // Force prefetch while request is pending should work.
    result.current.prefetchRandomGameId({ shouldForce: true });
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledTimes(2);
    });

    // Cleanup - resolve pending promises.
    resolveFirstRequest!({ data: { gameId: 12345 } });
    await firstPrefetchPromise;
  });

  it('given a request is already in-flight, getRandomGameId uses that in-flight request', async () => {
    // ARRANGE
    let resolveFirstRequest: (value: { data: { gameId: number } }) => void;
    const pendingPromise = new Promise<{ data: { gameId: number } }>((resolve) => {
      resolveFirstRequest = resolve;
    });

    const getSpy = vi
      .spyOn(axios, 'get')
      .mockImplementationOnce(() => pendingPromise)
      .mockResolvedValueOnce({ data: { gameId: 67890 } }); // !! this shouldn't be called

    const { result } = renderHook(
      () => useRandomGameId({ apiRouteName: 'api.game.random', columnFilters: [] }),
      { pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) } },
    );

    // ACT & ASSERT
    // Start prefetch but don't wait for it.
    result.current.prefetchRandomGameId();
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledTimes(1);
    });

    // Call getRandomGameId while the prefetch is still pending.
    const getGameIdPromise = result.current.getRandomGameId();

    // Now resolve the pending request.
    resolveFirstRequest!({ data: { gameId: 12345 } });

    // Wait for getRandomGameId to complete.
    const gameId = await getGameIdPromise;

    // ASSERT
    expect(gameId).toBe(12345);
    expect(getSpy).toHaveBeenCalledTimes(1); // !! should not have made a new request
  });
});
