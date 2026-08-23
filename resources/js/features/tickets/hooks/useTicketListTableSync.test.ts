import type { ColumnFiltersState } from '@tanstack/react-table';

import { renderHook } from '@/test';

import { useTicketListTableSync } from './useTicketListTableSync';

const serverDefaultColumnFilters: ColumnFiltersState = [{ id: 'status', value: ['unresolved'] }];

type TicketListTableSyncProps = Parameters<typeof useTicketListTableSync>[0];

const defaultProps: TicketListTableSyncProps = {
  columnFilters: serverDefaultColumnFilters,
  columnVisibilityOverrides: {},
  serverDefaultColumnFilters,
  pageNumber: 1,
  restoreState: vi.fn(),
  sortParam: '-createdAt',
};

function renderTicketListTableSync() {
  return renderHook((props: TicketListTableSyncProps) => useTicketListTableSync(props), {
    initialProps: defaultProps,
    pageProps: {
      persistenceCookieName: 'datatable_view_preference_tickets_all',
    },
  });
}

function readCookieWrite(cookieWrite: string, cookieName: string): unknown {
  const cookieMatch = cookieWrite.match(new RegExp(`${cookieName}=(.+?);`));

  return JSON.parse(decodeURIComponent(cookieMatch![1]));
}

function setWindowLocation(search: string) {
  Object.defineProperty(window, 'location', {
    writable: true,
    value: { search, pathname: '/tickets2' },
  });
}

describe('Hook: useTicketListTableSync', () => {
  let pushStateSpy: ReturnType<typeof vi.spyOn>;
  let replaceStateSpy: ReturnType<typeof vi.spyOn>;
  let originalLocation: Location;

  beforeEach(() => {
    originalLocation = window.location;
    setWindowLocation('');

    pushStateSpy = vi.spyOn(window.history, 'pushState').mockImplementation(() => {});
    replaceStateSpy = vi.spyOn(window.history, 'replaceState').mockImplementation(() => {});
  });

  afterEach(() => {
    Object.defineProperty(window, 'location', {
      writable: true,
      value: originalLocation,
    });
    pushStateSpy.mockRestore();
    replaceStateSpy.mockRestore();
  });

  it('given it is the first render cycle, does not update URL params', () => {
    // ARRANGE
    const setCookieSpy = vi.spyOn(document, 'cookie', 'set');
    renderTicketListTableSync();

    // ASSERT
    expect(pushStateSpy).not.toHaveBeenCalled();
    expect(setCookieSpy).not.toHaveBeenCalled();
  });

  it('persists only the sort and column visibility overrides', () => {
    // ARRANGE
    const cookieName = 'datatable_view_preference_tickets_all';
    const setCookieSpy = vi.spyOn(document, 'cookie', 'set');
    const { rerender } = renderTicketListTableSync();

    // ACT
    rerender({
      ...defaultProps,
      columnVisibilityOverrides: { game: false },
      pageNumber: 3,
      sortParam: 'state',
    });

    // ASSERT
    const lastCookieWrite = setCookieSpy.mock.calls.at(-1)![0];
    expect(readCookieWrite(lastCookieWrite, cookieName)).toEqual({
      columnVisibility: { game: false },
      sortParam: 'state',
    });
  });

  it('persists the defaults when the display is reset', () => {
    // ARRANGE
    const cookieName = 'datatable_view_preference_tickets_all';
    const setCookieSpy = vi.spyOn(document, 'cookie', 'set');
    const { rerender } = renderTicketListTableSync();

    rerender({
      ...defaultProps,
      columnVisibilityOverrides: { game: false },
      sortParam: 'state',
    });

    // ACT
    rerender(defaultProps);

    // ASSERT
    const lastCookieWrite = setCookieSpy.mock.calls.at(-1)![0];
    expect(readCookieWrite(lastCookieWrite, cookieName)).toEqual({
      columnVisibility: {},
      sortParam: '-createdAt',
    });
  });

  it('updates URL params when the page changes', () => {
    // ARRANGE
    const { rerender } = renderTicketListTableSync();

    // ACT
    rerender({
      ...defaultProps,
      pageNumber: 3,
    });

    // ASSERT
    expect(pushStateSpy).toHaveBeenCalledWith(
      { inertia: true, ticketListSortParam: '-createdAt' },
      '',
      `/tickets2?${encodeURIComponent('page[number]')}=3`,
    );

    // ACT
    setWindowLocation('?page[number]=3');
    rerender({
      ...defaultProps,
    });

    // ASSERT
    expect(pushStateSpy).toHaveBeenCalledWith(
      { inertia: true, ticketListSortParam: '-createdAt' },
      '',
      '/tickets2',
    );
  });

  it('given a filter value changes from the default, the URL updates accordingly', () => {
    // ARRANGE
    const { rerender } = renderTicketListTableSync();

    // ACT
    rerender({
      ...defaultProps,
      columnFilters: [{ id: 'status', value: ['resolved'] }] as ColumnFiltersState,
    });

    expect(pushStateSpy).toHaveBeenCalledWith(
      { inertia: true, ticketListSortParam: '-createdAt' },
      '',
      '/tickets2?filter%5Bstatus%5D=resolved',
    );

    setWindowLocation('?filter[status]=resolved');
    rerender({
      ...defaultProps,
    });

    // ASSERT
    expect(pushStateSpy).toHaveBeenCalledWith(
      { inertia: true, ticketListSortParam: '-createdAt' },
      '',
      '/tickets2',
    );
  });

  it('given the URL contains unrelated params, leaves them untouched', () => {
    // ARRANGE
    setWindowLocation('?highlight=17');

    const { rerender } = renderTicketListTableSync();

    // ACT
    rerender({
      ...defaultProps,
      pageNumber: 2,
    });

    // ASSERT
    expect(pushStateSpy).toHaveBeenCalledWith(
      { inertia: true, ticketListSortParam: '-createdAt' },
      '',
      `/tickets2?highlight=17&${encodeURIComponent('page[number]')}=2`,
    );
  });

  it('given the sort changes, the URL updates accordingly', () => {
    // ARRANGE
    const { rerender } = renderTicketListTableSync();

    // ACT
    rerender({
      ...defaultProps,
      sortParam: 'state',
    });

    // ASSERT
    expect(pushStateSpy).toHaveBeenCalledWith(
      { inertia: true, ticketListSortParam: 'state' },
      '',
      '/tickets2?sort=state',
    );
  });

  it('given the current URL params already match the serialized state, does not push a new history entry', () => {
    // ARRANGE
    setWindowLocation('?page%5Bnumber%5D=3');

    const { rerender } = renderTicketListTableSync();

    // ACT
    rerender({
      ...defaultProps,
      pageNumber: 3,
    });

    // ASSERT
    expect(pushStateSpy).not.toHaveBeenCalled();
  });

  it('given the user navigates history, syncs state correctly', () => {
    // ARRANGE
    const restoreState = vi.fn();

    renderHook((props: TicketListTableSyncProps) => useTicketListTableSync(props), {
      initialProps: { ...defaultProps, restoreState },
    });

    // ACT
    setWindowLocation('?filter[status]=resolved&page[number]=3&sort=state');
    window.dispatchEvent(new PopStateEvent('popstate'));

    // ASSERT
    expect(restoreState).toHaveBeenCalledWith({
      columnFilters: [{ id: 'status', value: ['resolved'] }],
      pageNumber: 3,
      sortParam: 'state',
    });
  });

  it('given the initial sort comes from persistence, restores it when navigating back', () => {
    // ARRANGE
    const restoreState = vi.fn();
    const { rerender } = renderHook(
      (props: TicketListTableSyncProps) => useTicketListTableSync(props),
      {
        initialProps: { ...defaultProps, restoreState, sortParam: 'state' },
      },
    );

    expect(replaceStateSpy).toHaveBeenCalledWith(
      expect.objectContaining({ ticketListSortParam: 'state' }),
      '',
    );

    rerender({
      ...defaultProps,
      pageNumber: 2,
      restoreState,
      sortParam: 'state',
    });

    // ACT
    setWindowLocation('');
    window.dispatchEvent(
      new PopStateEvent('popstate', { state: { ticketListSortParam: 'state' } }),
    );

    // ASSERT
    expect(restoreState).toHaveBeenCalledWith({
      columnFilters: serverDefaultColumnFilters,
      pageNumber: 1,
      sortParam: 'state',
    });
  });

  it('given history restores a URL with different state, does not push a new history entry', () => {
    // ARRANGE
    const restoreState = vi.fn();

    const { rerender } = renderHook(
      (props: TicketListTableSyncProps) => useTicketListTableSync(props),
      {
        initialProps: { ...defaultProps, restoreState },
      },
    );

    // ACT
    setWindowLocation('?filter[status]=unresolved');
    window.dispatchEvent(new PopStateEvent('popstate'));

    const [restoredState] = restoreState.mock.calls[0];
    rerender({
      ...defaultProps,
      ...restoredState,
      restoreState,
    });

    // ASSERT
    expect(pushStateSpy).not.toHaveBeenCalled();

    rerender({
      ...defaultProps,
      ...restoredState,
      restoreState,
      pageNumber: 2,
    });

    expect(pushStateSpy).toHaveBeenCalledWith(
      { inertia: true, ticketListSortParam: '-createdAt' },
      '',
      `/tickets2?${encodeURIComponent('page[number]')}=2`,
    );
  });
});
