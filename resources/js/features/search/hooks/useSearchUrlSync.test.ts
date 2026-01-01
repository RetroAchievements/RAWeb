import { renderHook } from '@/test';

import { useSearchUrlSync } from './useSearchUrlSync';

describe('Hook: useSearchUrlSync', () => {
  let replaceStateSpy: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    replaceStateSpy = vi.spyOn(window.history, 'replaceState').mockImplementation(vi.fn()) as any;
  });

  afterEach(() => {
    replaceStateSpy.mockRestore();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useSearchUrlSync({ query: '', scope: 'all', page: 1 }));

    // ASSERT
    expect(result).toBeDefined();
  });

  it('given an empty query, scope is all, and page is 1, sets the URL to just /search', () => {
    // ARRANGE
    renderHook(() => useSearchUrlSync({ query: '', scope: 'all', page: 1 }));

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith({}, '', '/search');
  });

  it('given a query is provided, includes it in the URL params', () => {
    // ARRANGE
    renderHook(() => useSearchUrlSync({ query: 'mario', scope: 'all', page: 1 }));

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith({}, '', '/search?query=mario');
  });

  it('given the scope is not all, includes it in the URL params', () => {
    // ARRANGE
    renderHook(() => useSearchUrlSync({ query: '', scope: 'games', page: 1 }));

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith({}, '', '/search?scope=games');
  });

  it('given the page is greater than 1, includes it in the URL params', () => {
    // ARRANGE
    renderHook(() => useSearchUrlSync({ query: '', scope: 'all', page: 2 }));

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith({}, '', '/search?page=2');
  });

  it('given all params have non-default values, includes all of them in the URL', () => {
    // ARRANGE
    renderHook(() => useSearchUrlSync({ query: 'sonic', scope: 'users', page: 3 }));

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith({}, '', '/search?query=sonic&scope=users&page=3');
  });

  it('given the query and scope are set but page is 1, excludes page from the URL', () => {
    // ARRANGE
    renderHook(() => useSearchUrlSync({ query: 'zelda', scope: 'achievements', page: 1 }));

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith({}, '', '/search?query=zelda&scope=achievements');
  });

  it('given the state changes, updates the URL accordingly', () => {
    // ARRANGE
    const { rerender } = renderHook(
      (props: { query: string; scope: 'all' | 'games'; page: number }) => useSearchUrlSync(props),
      {
        initialProps: { query: 'mario', scope: 'all' as const, page: 1 },
      },
    );

    // ACT
    rerender({ query: 'mario', scope: 'games' as any, page: 2 });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenLastCalledWith(
      {},
      '',
      '/search?query=mario&scope=games&page=2',
    );
  });
});
