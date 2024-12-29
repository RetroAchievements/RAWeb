import type {
  ColumnFiltersState,
  PaginationState,
  SortingState,
  VisibilityState,
} from '@tanstack/react-table';

import { renderHook } from '@/test';

import { useTableSync } from './useTableSync';

const defaultColumnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];

describe('Hook: useTableSync', () => {
  let replaceStateSpy: ReturnType<typeof vi.spyOn>;
  let cookieSpy: ReturnType<typeof vi.spyOn>;
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

    // Mock document.cookie for persistence tests.
    cookieSpy = vi.spyOn(document, 'cookie', 'set');
  });

  afterEach(() => {
    window.location = originalLocation;
    replaceStateSpy.mockRestore();
    cookieSpy.mockRestore();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    const columnVisibility: VisibilityState = {};
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: true }];

    const { result } = renderHook(() =>
      useTableSync({
        columnFilters,
        columnVisibility,
        pagination,
        sorting,
        defaultColumnFilters,
      }),
    );

    // ASSERT
    expect(result).toBeDefined();
  });

  it('given it is the first render cycle, does not update URL params', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    const columnVisibility: VisibilityState = {};
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: true }];

    renderHook(() =>
      useTableSync({
        columnFilters,
        columnVisibility,
        pagination,
        sorting,
        defaultColumnFilters,
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

    const { rerender } = renderHook((props: any) => useTableSync(props), {
      initialProps: { columnFilters, pagination, sorting, defaultColumnFilters },
    });

    // ACT
    const updatedPagination: PaginationState = { pageIndex: 1, pageSize: 25 };
    rerender({
      columnFilters,
      sorting,
      pagination: updatedPagination,
      defaultColumnFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games?page[number]=2'));
  });

  it('given the user goes from page 2 to page 1, updates URL params correctly', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    const pagination: PaginationState = { pageIndex: 1, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useTableSync(props), {
      initialProps: { columnFilters, pagination, sorting, defaultColumnFilters },
    });

    // ACT
    const updatedPagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    rerender({
      columnFilters,
      sorting,
      pagination: updatedPagination,
      defaultColumnFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games')); // don't send a param on page 1
  });

  it('given the user changes the sort order, updates URL params correctly', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useTableSync(props), {
      initialProps: { columnFilters, pagination, sorting, defaultColumnFilters },
    });

    // ACT
    const updatedSorting: SortingState = [{ id: 'system', desc: true }];
    rerender({
      columnFilters,
      pagination,
      sorting: updatedSorting,
      defaultColumnFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games?sort=-system'));
  });

  it('given the user sorts by title ascending, updates URL params correctly by removing the sort order', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting = [{ id: 'system', desc: true }];

    const { rerender } = renderHook((props: any) => useTableSync(props), {
      initialProps: { columnFilters, pagination, sorting, defaultColumnFilters },
    });

    // ACT
    const updatedSorting: SortingState = [{ id: 'title', desc: false }];
    rerender({
      columnFilters,
      pagination,
      sorting: updatedSorting,
      defaultColumnFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games')); // don't send a param on the default sort order
  });

  it('given the user filters by a system, updates URL params correctly', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useTableSync(props), {
      initialProps: { columnFilters, pagination, sorting, defaultColumnFilters },
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
      defaultColumnFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games?filter[system]=1'));
  });

  it('given the user filters by multiple systems, updates URL params correctly', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useTableSync(props), {
      initialProps: { columnFilters, pagination, sorting, defaultColumnFilters },
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
      defaultColumnFilters,
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

    const { rerender } = renderHook((props: any) => useTableSync(props), {
      initialProps: { columnFilters, pagination, sorting, defaultColumnFilters },
    });

    // ACT
    const updatedFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    rerender({
      pagination,
      sorting,
      columnFilters: updatedFilters,
      defaultColumnFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games'));
  });

  it('given the user sets the achievements published filter to "none", updates URL params correctly', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useTableSync(props), {
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

    const { rerender } = renderHook((props: any) => useTableSync(props), {
      initialProps: { columnFilters, pagination, sorting, defaultColumnFilters },
    });

    // ACT
    const updatedFilters: ColumnFiltersState = [{ id: 'achievementsPublished', value: ['has'] }];
    rerender({
      pagination,
      sorting,
      columnFilters: updatedFilters,
      defaultColumnFilters,
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

    const { rerender } = renderHook((props: any) => useTableSync(props), {
      initialProps: { columnFilters, pagination, sorting, defaultColumnFilters },
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
      defaultColumnFilters,
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
    const defaultColumnFilters: ColumnFiltersState = [{ id: 'title', value: 'default-value' }];

    const { rerender } = renderHook((props: any) => useTableSync(props), {
      initialProps: {
        columnFilters,
        pagination,
        sorting,
        defaultColumnFilters,
      },
    });

    // ACT
    const updatedColumnFilters = [...columnFilters]; // trigger the effect
    rerender({
      columnFilters: updatedColumnFilters,
      pagination,
      sorting,
      defaultColumnFilters,
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

    const { rerender } = renderHook((props: any) => useTableSync(props), {
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

    const { rerender } = renderHook((props: any) => useTableSync(props), {
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

  it('given user persistence is enabled, saves table state to the cookie', () => {
    // ARRANGE
    const cookieName = 'test_cookie_name';
    const setCookieSpy = vi.spyOn(document, 'cookie', 'set');

    const columnFilters: ColumnFiltersState = [{ id: 'system', value: '1' }];
    const columnVisibility: VisibilityState = { system: false };
    const pagination: PaginationState = { pageIndex: 2, pageSize: 50 };
    const sorting: SortingState = [{ id: 'title', desc: true }];

    const { rerender } = renderHook((props: any) => useTableSync(props), {
      initialProps: {
        columnFilters,
        columnVisibility,
        pagination,
        sorting,
        isUserPersistenceEnabled: true,
      },
      pageProps: {
        persistenceCookieName: cookieName,
      },
    });

    // ACT
    const updatedFilters = [...columnFilters];
    rerender({
      columnFilters: updatedFilters,
      columnVisibility,
      pagination,
      sorting,
      isUserPersistenceEnabled: true,
    });

    // ASSERT
    const cookieValue = setCookieSpy.mock.calls[0][0];
    const cookieMatch = cookieValue.match(new RegExp(`${cookieName}=(.+?);`));
    const parsedCookie = JSON.parse(decodeURIComponent(cookieMatch![1]));

    expect(parsedCookie).toEqual({
      columnFilters,
      columnVisibility,
      sorting,
      pagination: { ...pagination, pageIndex: 0 },
    });
  });

  it('given user persistence is enabled and table state changes, saves new state to the cookie', () => {
    // ARRANGE
    const cookieName = 'test_cookie_name';
    const setCookieSpy = vi.spyOn(document, 'cookie', 'set');

    const initialVisibility: VisibilityState = { system: false };
    const updatedVisibility: VisibilityState = { system: true };

    const columnFilters: ColumnFiltersState = [{ id: 'system', value: '1' }];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useTableSync(props), {
      initialProps: {
        columnFilters,
        columnVisibility: initialVisibility,
        pagination,
        sorting,
        isUserPersistenceEnabled: true,
      },
      pageProps: {
        persistenceCookieName: cookieName,
      },
    });

    // ACT
    rerender({
      columnFilters,
      columnVisibility: updatedVisibility,
      pagination,
      sorting,
      isUserPersistenceEnabled: true,
    });

    // ASSERT
    const lastCall = setCookieSpy.mock.calls[setCookieSpy.mock.calls.length - 1][0];
    const cookieMatch = lastCall.match(new RegExp(`${cookieName}=(.+?);`));
    const parsedCookie = JSON.parse(decodeURIComponent(cookieMatch![1]));

    expect(parsedCookie.columnVisibility).toEqual(updatedVisibility);
  });

  it('given persistence is enabled but then disabled, cleans up the cookie', () => {
    // ARRANGE
    const cookieName = 'test_cookie_name';
    const setCookieSpy = vi.spyOn(document, 'cookie', 'set');

    const columnFilters: ColumnFiltersState = [{ id: 'system', value: '1' }];
    const columnVisibility: VisibilityState = { system: false };
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useTableSync(props), {
      initialProps: {
        columnFilters,
        columnVisibility,
        pagination,
        sorting,
        isUserPersistenceEnabled: true,
      },
      pageProps: {
        persistenceCookieName: cookieName,
      },
    });

    // ACT
    rerender({
      columnFilters,
      columnVisibility,
      pagination,
      sorting,
      isUserPersistenceEnabled: false,
    });

    // ASSERT
    const lastCall = setCookieSpy.mock.calls[setCookieSpy.mock.calls.length - 1][0];
    expect(lastCall).toContain(`${cookieName}=;`);
  });

  it('given user persistence is enabled and there is a title filter, excludes the title filter from cookie persistence', () => {
    // ARRANGE
    const cookieName = 'test_cookie_name';
    const setCookieSpy = vi.spyOn(document, 'cookie', 'set');

    const columnFilters: ColumnFiltersState = [
      { id: 'system', value: '1' },
      { id: 'title', value: 'mario' }, // !! this should be excluded
    ];
    const columnVisibility: VisibilityState = {};
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useTableSync(props), {
      initialProps: {
        columnFilters,
        columnVisibility,
        pagination,
        sorting,
        isUserPersistenceEnabled: true,
      },
      pageProps: {
        persistenceCookieName: cookieName,
      },
    });

    // ACT
    const updatedFilters = [...columnFilters];
    rerender({
      columnFilters: updatedFilters,
      columnVisibility,
      pagination,
      sorting,
      isUserPersistenceEnabled: true,
    });

    // ASSERT
    const cookieValue = setCookieSpy.mock.calls[0][0];
    const cookieMatch = cookieValue.match(new RegExp(`${cookieName}=(.+?);`));
    const parsedCookie = JSON.parse(decodeURIComponent(cookieMatch![1]));

    // ... only the system filter should be persisted in the cookie ...
    expect(parsedCookie.columnFilters).toEqual([{ id: 'system', value: '1' }]);
  });

  it('given the user changes the page size, updates URL params correctly', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    const { rerender } = renderHook((props: any) => useTableSync(props), {
      initialProps: { columnFilters, pagination, sorting },
    });

    // ACT
    const updatedPagination = { pageIndex: 0, pageSize: 50 };
    rerender({
      columnFilters,
      sorting,
      pagination: updatedPagination,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games?page[size]=50'));
  });

  it('given the user changes the sort direction to ascending, updates URL params correctly', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'system', desc: true }];

    const { rerender } = renderHook((props: any) => useTableSync(props), {
      initialProps: { columnFilters, pagination, sorting },
    });

    // ACT
    const updatedSorting: SortingState = [{ id: 'system', desc: false }];
    rerender({
      columnFilters,
      pagination,
      sorting: updatedSorting,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(
      null,
      '',
      encodeURI('/games?sort=system'), // !! no minus prefix on "system" when using ascending sort
    );
  });

  it('given inactive filters are present in the URL, removes them when updating params', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];

    // ... mock a URL with an inactive filter param ...
    Object.defineProperty(window, 'location', {
      writable: true,
      value: { search: '?filter[oldParam]=value', pathname: '/games' },
    });

    const { rerender } = renderHook((props: any) => useTableSync(props), {
      initialProps: { columnFilters, pagination, sorting },
    });

    // ACT
    const updatedFilters: ColumnFiltersState = [{ id: 'system', value: '1' }];
    rerender({
      columnFilters: updatedFilters,
      pagination,
      sorting,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(
      null,
      '',
      encodeURI('/games?filter[system]=1'), // !! oldParam is removed
    );
  });

  it('given a filter value has a different length than the default filter value, treats them as different values', () => {
    // ARRANGE
    const columnFilters: ColumnFiltersState = [];
    const pagination: PaginationState = { pageIndex: 0, pageSize: 25 };
    const sorting: SortingState = [{ id: 'title', desc: false }];
    const defaultFilters: ColumnFiltersState = [{ id: 'system', value: ['1', '2'] }];

    const { rerender } = renderHook((props: any) => useTableSync(props), {
      initialProps: {
        columnFilters,
        pagination,
        sorting,
        defaultColumnFilters: defaultFilters,
      },
    });

    // ACT
    const updatedFilters: ColumnFiltersState = [{ id: 'system', value: ['1'] }];
    rerender({
      columnFilters: updatedFilters,
      pagination,
      sorting,
      defaultColumnFilters: defaultFilters,
    });

    // ASSERT
    expect(replaceStateSpy).toHaveBeenCalledWith(null, '', encodeURI('/games?filter[system]=1'));
  });
});
