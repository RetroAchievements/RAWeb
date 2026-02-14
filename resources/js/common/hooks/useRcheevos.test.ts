import { renderHook, waitFor } from '@/test';

import { useRcheevos } from './useRcheevos';

const mockRcheevosInstance = vi.hoisted(() => ({ initialized: true }) as any);

vi.mock('rcheevos', () => {
  const initialize = vi.fn(() => Promise.resolve(mockRcheevosInstance));

  return {
    RCheevos: {
      initialize,
    },
  };
});

describe('Hook: useRcheevos', () => {
  it('returns a ref initialized to null', () => {
    // ARRANGE
    const { result } = renderHook(() => useRcheevos());

    // ASSERT
    expect(result.current.current).toBeNull();
  });

  it('populates the ref with the initialized RCheevos instance', async () => {
    // ARRANGE
    const { result } = renderHook(() => useRcheevos());

    // ASSERT
    await waitFor(() => {
      expect(result.current.current).toBe(mockRcheevosInstance);
    });
  });
});
