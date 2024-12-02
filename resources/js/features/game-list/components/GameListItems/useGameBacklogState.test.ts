import type { Mock } from 'vitest';

import { usePageProps } from '@/common/hooks/usePageProps';
import { useWantToPlayGamesList } from '@/common/hooks/useWantToPlayGamesList';
import { createAuthenticatedUser } from '@/common/models';
import { act, renderHook, waitFor } from '@/test';
import { createGame } from '@/test/factories';

import { useGameBacklogState } from './useGameBacklogState';

vi.mock('@/common/hooks/useWantToPlayGamesList', () => ({
  useWantToPlayGamesList: vi.fn(),
}));

vi.mock('@/common/hooks/usePageProps', () => ({
  usePageProps: vi.fn(),
}));

describe('Hook: useGameBacklogState', () => {
  beforeEach(() => {
    (usePageProps as Mock).mockReturnValue({
      auth: { user: createAuthenticatedUser({ id: 1 }) },
    });

    (useWantToPlayGamesList as Mock).mockReturnValue({
      addToWantToPlayGamesList: vi.fn(),
      removeFromWantToPlayGamesList: vi.fn(),
      isPending: false,
    });
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  it('given an API call fails while optimistic updates are enabled, reverts the optimistic update', async () => {
    // ARRANGE
    const mockError = new Error('API Error');
    const addToWantToPlayGamesList = vi.fn().mockRejectedValue(mockError);

    (useWantToPlayGamesList as Mock).mockReturnValue({
      addToWantToPlayGamesList,
      removeFromWantToPlayGamesList: vi.fn(),
      isPending: false,
    });

    const { result } = renderHook(() =>
      useGameBacklogState({
        game: createGame({ id: 1, title: 'Test Game' }),
        isInitiallyInBacklog: false, // !!
        shouldUpdateOptimistically: true,
      }),
    );

    // ACT
    // ... attempt to add to backlog, which will fail ...
    await act(async () => {
      await result.current.toggleBacklog();
    });

    // ASSERT
    // ... verify the state was initially updated optimistically ...
    expect(addToWantToPlayGamesList).toHaveBeenCalled();

    // ... verify the state was reverted after the error ...
    await waitFor(() => {
      expect(result.current.isInBacklogMaybeOptimistic).toBe(false);
    });
  });

  it('given an API call fails when optimistic updates are not enabled, does not revert state', async () => {
    // ARRANGE
    const mockError = new Error('API Error');
    const addToWantToPlayGamesList = vi.fn().mockRejectedValue(mockError);

    (useWantToPlayGamesList as Mock).mockReturnValue({
      addToWantToPlayGamesList,
      removeFromWantToPlayGamesList: vi.fn(),
      isPending: false,
    });

    const { result } = renderHook(() =>
      useGameBacklogState({
        game: createGame({ id: 1, title: 'Test Game' }),
        isInitiallyInBacklog: false, // !!
        shouldUpdateOptimistically: false,
      }),
    );

    // ACT
    // ... attempt to add to backlog, which will fail ...
    await act(async () => {
      await result.current.toggleBacklog();
    });

    // ASSERT
    // ... verify the API was called ...
    expect(addToWantToPlayGamesList).toHaveBeenCalled();

    // ... verify the state remained unchanged since we're not using optimistic updates ...
    expect(result.current.isInBacklogMaybeOptimistic).toBe(false);
  });
});
