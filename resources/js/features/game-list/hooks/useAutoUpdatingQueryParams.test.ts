import type { ColumnFiltersState, PaginationState, SortingState } from '@tanstack/react-table';

import { renderHook } from '@/test';

import { allGamesDefaultFilters } from '../utils/allGamesDefaultFilters';
import { useAutoUpdatingQueryParams } from './useAutoUpdatingQueryParams';

describe('Hook: useAutoUpdatingQueryParams', () => {
  let replaceStateSpy: ReturnType<typeof vi.spyOn>;
  let originalLocation: Location;

  beforeEach(() => {
    // Save the original location object.
    originalLocation = window.location;

    // Mock the location search params.
    Object.defineProperty(window, 'location', {
      writable: true,
      value: { search: '', pathname: '/games' },
    });

    // Mock the history.replaceState function.
    replaceStateSpy = vi.spyOn(window.history, 'replaceState').mockImplementation(vi.fn()) as any;
  });

  afterEach(() => {
    // Restore the location and history values.
    window.location = originalLocation;
    replaceStateSpy.mockRestore();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: true }];

    const { result } = renderHook(() =>
      useAutoUpdatingQueryParams({
        columnFilters,
        pagination,
        sorting,
        defaultFilters: allGamesDefaultFilters,
      }),
    );

    // ASSERT
    expect(result).toBeDefined();
  });

  it('given it is the first render cycle, does not update URL params', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: true }];

    renderHook(() =>
      useAutoUpdatingQueryParams({
        columnFilters,
        pagination,
        sorting,
        defaultFilters: allGamesDefaultFilters,
      }),
    );

    // ASSERT
    expect(replaceStateSpy).not.toHaveBeenCalled();
  });

  it('given the user advances from page 1 to page 2, updates URL params correctly', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: { columnFilters, pagination, sorting, defaultFilters: allGamesDefaultFilters },
    });

    // ACT
    const updatedPagination: PaginationState = { pageIndex: 1, pageSize: 25 };
    rerender({
      columnFilters,
      sorting,
      pagination: updatedPagination,
      defaultFilters: allGamesDefaultFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games?page[number]=2'));
  });

  it('given the user goes from page 2 to page 1, updates URL params correctly', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    const pagination: PaginationState = { pageIndex: 1, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: { columnFilters, pagination, sorting, defaultFilters: allGamesDefaultFilters },
    });

    // ACT
    const updatedPagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    rerender({
      columnFilters,
      sorting,
      pagination: updatedPagination,
      defaultFilters: allGamesDefaultFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games')); // don't send a param on page 1
  });

  it('given the user changes the sort order, updates URL params correctly', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: { columnFilters, pagination, sorting, defaultFilters: allGamesDefaultFilters },
    });

    // ACT
    const updatedSorting: SortingState = [{ id: 'system', desc: true }];
    rerender({
      columnFilters,
      pagination,
      sorting: updatedSorting,
      defaultFilters: allGamesDefaultFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games?sort=-system'));
  });

  it('given the user sorts by title ascending, updates URL params correctly by removing the sort order', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting = [{ id: 'system', desc: true }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: { columnFilters, pagination, sorting, defaultFilters: allGamesDefaultFilters },
    });

    // ACT
    const updatedSorting: SortingState = [{ id: 'title', desc: false }];
    rerender({
      columnFilters,
      pagination,
      sorting: updatedSorting,
      defaultFilters: allGamesDefaultFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games')); // don't send a param on the default sort order
  });

  it('given the user filters by a system, updates URL params correctly', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: { columnFilters, pagination, sorting, defaultFilters: allGamesDefaultFilters },
    });

    // ACT
    const updatedFilters: ColumnFiltersState = [
      { id: 'achievementsPublished', value: ['has'] },
      { id: 'system', value: '1' },
    ];
    rerender({
      pagination,
      sorting,
      columnFilters: updatedFilters,
      defaultFilters: allGamesDefaultFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games?filter[system]=1'));
  });

  it('given the user filters by multiple systems, updates URL params correctly', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: { columnFilters, pagination, sorting, defaultFilters: allGamesDefaultFilters },
    });

    // ACT
    const updatedFilters: ColumnFiltersState = [
      { id: 'achievementsPublished', value: ['has'] },
      { id: 'system', value: ['1', '5'] },
    ];
    rerender({
      pagination,
      sorting,
      columnFilters: updatedFilters,
      defaultFilters: allGamesDefaultFilters,
    });

    // ASSERT
    const filterValue = '1%2C5';
    expect(replaceStateSpy).toHaveBeenCalledWith(
      null,
      '',
      encodeURI('/games?filter[system]=') + filterValue,
    );
  });

  it('given the user clears their filters, removes them from query params', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [
      { id: 'achievementsPublished', value: ['has'] },
      { id: 'system', value: ['1', '5'] },
    ];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: { columnFilters, pagination, sorting, defaultFilters: allGamesDefaultFilters },
    });

    // ACT
    const updatedFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    rerender({
      pagination,
      sorting,
      columnFilters: updatedFilters,
      defaultFilters: allGamesDefaultFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games'));
  });

  it('given the user sets the achievements published filter to "none", updates URL params correctly', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: { columnFilters, pagination, sorting },
    });

    // ACT
    const updatedFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['none'] }];
    rerender({
      pagination,
      sorting,
      columnFilters: updatedFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(
      null,
      '',
      encodeURI('/games?filter[achievementsPublished]=none'),
    );
  });

  it('given the user sets the achievements published filter to "has", removes it from the query params', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['none'] }];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: { columnFilters, pagination, sorting, defaultFilters: allGamesDefaultFilters },
    });

    // ACT
    const updatedFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    rerender({
      pagination,
      sorting,
      columnFilters: updatedFilters,
      defaultFilters: allGamesDefaultFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games'));
  });

  it('given a non-array filter value is set to empty, removes it from query params', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [
      { id: 'achievementsPublished', value: ['has'] },
      { id: 'title', value: 'mario' }, // !!
    ];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: { columnFilters, pagination, sorting, defaultFilters: allGamesDefaultFilters },
    });

    // ACT
    const updatedFilters: ColumnFiltersState = [
      { id: 'achievementsPublished', value: ['has'] },
      { id: 'title', value: '' }, // set to an empty string
    ];
    rerender({
      pagination,
      sorting,
      columnFilters: updatedFilters,
      defaultFilters: allGamesDefaultFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games'));
  });

  it('given a non-array filter value matches the default, removes it from query params', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [
      { id: 'achievementsPublished', value: ['has'] },
      { id: 'title', value: 'default-value' },
    ];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];
    const defaultFilters: ColumnFiltersState = [{ id: 'title', value: 'default-value' }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: {
        columnFilters,
        pagination,
        sorting,
        defaultFilters,
      },
    });

    // ACT
    const updatedColumnFilters = [...columnFilters]; // trigger the effect
    rerender({
      columnFilters: updatedColumnFilters,
      pagination,
      sorting,
      defaultFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(
      null,
      '',
      encodeURI('/games?filter[achievementsPublished]=has'),
    );
  });

  it('given a non-array filter value differs from default, includes it in query params', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [
      { id: 'achievementsPublished', value: ['has'] },
      { id: 'title', value: 'default-value' },
    ];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];
    const defaultFilters: ColumnFiltersState = [{ id: 'title', value: 'default-value' }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: {
        columnFilters,
        pagination,
        sorting,
        defaultFilters,
      },
    });

    // ACT
    const updatedColumnFilters: ColumnFiltersState = [
      { id: 'achievementsPublished', value: ['has'] },
      { id: 'title', value: 'new-value' },
    ];
    rerender({
      columnFilters: updatedColumnFilters,
      pagination,
      sorting,
      defaultFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(
      null,
      '',
      encodeURI('/games?filter[achievementsPublished]=has&filter[title]=new-value'),
    );
  });

  it('given arrays of different lengths, treats them as non-matching filters', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'platform', value: ['1'] }];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];
    const defaultFilters: ColumnFiltersState = [{ id: 'platform', value: ['1'] }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: {
        columnFilters,
        pagination,
        sorting,
        defaultFilters,
      },
    });

    // ACT
    const updatedColumnFilters: ColumnFiltersState = [
      { id: 'platform', value: ['1', '2'] }, // ... changed from ['1'] to ['1', '2'].
    ];
    rerender({
      columnFilters: updatedColumnFilters,
      pagination,
      sorting,
      defaultFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', '/games?filter%5Bplatform%5D=1%2C2');
  });
});
