import { act, renderHook } from '@/test';

import { useActivePlayerSearch } from './useActivePlayerSearch';

describe('Hook: useActivePlayerSearch', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useActivePlayerSearch({}));

    // ASSERT
    expect(result.current).toBeDefined();
    expect(result.current.canShowSearchBar).toBeDefined();
    expect(result.current.handleSearch).toBeDefined();
    expect(result.current.hasSearched).toBeDefined();
    expect(result.current.searchValue).toBeDefined();
    expect(result.current.setCanShowSearchBar).toBeDefined();
  });

  it('initializes with correct values when no persisted search value is provided', () => {
    // ARRANGE
    const { result } = renderHook(() => useActivePlayerSearch({}));

    // ASSERT
    expect(result.current.canShowSearchBar).toEqual(false);
    expect(result.current.hasSearched).toEqual(false);
    expect(result.current.searchValue).toEqual('');
  });

  it('initializes with canShowSearchBar as true when a persisted search value is provided', () => {
    // ARRANGE
    const { result } = renderHook(() => useActivePlayerSearch({ persistedSearchValue: 'test' }));

    // ASSERT
    expect(result.current.canShowSearchBar).toEqual(true);
    expect(result.current.hasSearched).toEqual(false);
    expect(result.current.searchValue).toEqual('');
  });

  it('allows toggling the search bar', () => {
    // ARRANGE
    const { result } = renderHook(() => useActivePlayerSearch({}));

    // ACT
    act(() => {
      result.current.setCanShowSearchBar(true);
    });

    // ASSERT
    expect(result.current.canShowSearchBar).toEqual(true);
  });

  it('properly handles search value updates', () => {
    // ARRANGE
    const { result } = renderHook(() => useActivePlayerSearch({}));
    const searchValue = 'test search';

    // ACT
    act(() => {
      result.current.handleSearch(searchValue);
    });

    // ASSERT
    expect(result.current.searchValue).toEqual(searchValue);
    expect(result.current.hasSearched).toEqual(true);
  });
});
