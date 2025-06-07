import { renderHook } from '@/test';

import { useScrollToTopOnSearchResults } from './useScrollToTopOnSearchResults';

describe('Hook: useScrollToTopOnSearchResults', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('returns a ref that can be attached to an element', () => {
    // ARRANGE
    const { result } = renderHook(() =>
      useScrollToTopOnSearchResults({ searchResults: null, isLoading: false }),
    );

    // ASSERT
    expect(result.current).toHaveProperty('current');
    expect(result.current.current).toBeNull();
  });

  it('given the same search results, does not scroll', () => {
    // ARRANGE
    const mockScrollTo = vi.fn();
    const mockElement = { scrollTo: mockScrollTo } as unknown as HTMLDivElement;
    const sameResults = { data: 'same results' };

    const { result, rerender } = renderHook(
      ({ searchResults, isLoading }) => useScrollToTopOnSearchResults({ searchResults, isLoading }),
      {
        initialProps: { searchResults: sameResults, isLoading: false },
      },
    );

    result.current.current = mockElement;

    // ACT
    rerender({ searchResults: sameResults, isLoading: false });

    // ASSERT
    expect(mockScrollTo).not.toHaveBeenCalled();
  });

  it('given new results but still loading, does not scroll', () => {
    // ARRANGE
    const mockScrollTo = vi.fn();
    const mockElement = { scrollTo: mockScrollTo } as unknown as HTMLDivElement;

    const { result, rerender } = renderHook(
      ({ searchResults, isLoading }) => useScrollToTopOnSearchResults({ searchResults, isLoading }),
      {
        initialProps: { searchResults: null, isLoading: true },
      },
    );

    result.current.current = mockElement;

    // ACT
    rerender({ searchResults: { data: 'new results' }, isLoading: true } as any);

    // ASSERT
    expect(mockScrollTo).not.toHaveBeenCalled();
  });

  it('given results becomes null, does not scroll', () => {
    // ARRANGE
    const mockScrollTo = vi.fn();
    const mockElement = { scrollTo: mockScrollTo } as unknown as HTMLDivElement;

    const { result, rerender } = renderHook(
      ({ searchResults, isLoading }) => useScrollToTopOnSearchResults({ searchResults, isLoading }),
      {
        initialProps: { searchResults: { data: 'initial' }, isLoading: false },
      },
    );

    result.current.current = mockElement;

    // ACT
    rerender({ searchResults: null, isLoading: false } as any);

    // ASSERT
    expect(mockScrollTo).not.toHaveBeenCalled();
  });

  it('given loading transitions from true to false with new results, scrolls to top', () => {
    // ARRANGE
    const mockScrollTo = vi.fn();
    const mockElement = { scrollTo: mockScrollTo } as unknown as HTMLDivElement;
    const newResults = { data: 'new results' };

    const { result, rerender } = renderHook(
      ({ searchResults, isLoading }) => useScrollToTopOnSearchResults({ searchResults, isLoading }),
      {
        initialProps: { searchResults: null, isLoading: true },
      },
    );

    result.current.current = mockElement;

    // ACT - Results arrive but still loading.
    rerender({ searchResults: newResults, isLoading: true } as any);

    expect(mockScrollTo).not.toHaveBeenCalled(); // no scroll yet

    rerender({ searchResults: newResults, isLoading: false } as any);

    // ASSERT
    expect(mockScrollTo).toHaveBeenCalledWith({ top: 0 });
    expect(mockScrollTo).toHaveBeenCalledTimes(1);
  });
});
