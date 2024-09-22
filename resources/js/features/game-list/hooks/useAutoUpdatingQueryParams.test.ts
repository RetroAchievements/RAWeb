import type { ColumnFiltersState, PaginationState, SortingState } from '@tanstack/react-table';

import { renderHook } from '@/test';

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
    const columnFilters: ColumnFiltersState = [];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: true }];

    const { result } = renderHook(() =>
      useAutoUpdatingQueryParams({ columnFilters, pagination, sorting }),
    );

    // ASSERT
    expect(result).toBeDefined();
  });

  it('given it is the first render cycle, does not update URL params', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: true }];

    renderHook(() => useAutoUpdatingQueryParams({ columnFilters, pagination, sorting }));

    // ASSERT
    expect(replaceStateSpy).not.toHaveBeenCalled();
  });

  it('given the user advances from page 1 to page 2, updates URL params correctly', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: { columnFilters, pagination, sorting },
    });

    // ACT
    const updatedPagination: PaginationState = { pageIndex: 1, pageSize: 25 };
    rerender({
      columnFilters,
      sorting,
      pagination: updatedPagination,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games?page[number]=2'));
  });

  it('given the user goes from page 2 to page 1, updates URL params correctly', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [];
    const pagination: PaginationState = { pageIndex: 1, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: { columnFilters, pagination, sorting },
    });

    // ACT
    const updatedPagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    rerender({
      columnFilters,
      sorting,
      pagination: updatedPagination,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games')); // don't send a param on page 1
  });

  it('given the user changes the sort order, updates URL params correctly', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: { columnFilters, pagination, sorting },
    });

    // ACT
    const updatedSorting: SortingState = [{ id: 'system', desc: true }];
    rerender({
      columnFilters,
      pagination,
      sorting: updatedSorting,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games?sort=-system'));
  });

  it('given the user sorts by title ascending, updates URL params correctly by removing the sort order', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting = [{ id: 'system', desc: true }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: { columnFilters, pagination, sorting },
    });

    // ACT
    const updatedSorting: SortingState = [{ id: 'title', desc: false }];
    rerender({
      columnFilters,
      pagination,
      sorting: updatedSorting,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games')); // don't send a param on the default sort order
  });

  it('given the user filters by a system, updates URL params correctly', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: { columnFilters, pagination, sorting },
    });

    // ACT
    const updatedFilters: ColumnFiltersState = [{ id: 'system', value: '1' }];
    rerender({
      pagination,
      sorting,
      columnFilters: updatedFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games?filter[system]=1'));
  });

  it('given the user filters by multiple systems, updates URL params correctly', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: { columnFilters, pagination, sorting },
    });

    // ACT
    const updatedFilters: ColumnFiltersState = [{ id: 'system', value: ['1', '5'] }];
    rerender({
      pagination,
      sorting,
      columnFilters: updatedFilters,
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
    const columnFilters: ColumnFiltersState = [{ id: 'system', value: ['1', '5'] }];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useAutoUpdatingQueryParams(props), {
      initialProps: { columnFilters, pagination, sorting },
    });

    // ACT
    const updatedFilters: ColumnFiltersState = [];
    rerender({
      pagination,
      sorting,
      columnFilters: updatedFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games'));
  });
});
