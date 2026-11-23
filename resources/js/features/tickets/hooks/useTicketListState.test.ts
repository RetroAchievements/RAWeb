import { act, renderHook } from '@/test';
import { createPaginatedData, createZiggyProps } from '@/test/factories';

import { useTicketListState } from './useTicketListState';

const serverDefaultColumnFilters = [{ id: 'status', value: ['unresolved'] }];

function renderTicketListState({
  currentPage = 1,
  groupFields = ['game'],
  persistedViewPreferences = null,
  query = {},
}: {
  currentPage?: number;
  groupFields?: App.Platform.Enums.TicketListGroupField[];
  persistedViewPreferences?: unknown;
  query?: Record<string, string | Record<string, string>>;
} = {}) {
  return renderHook(
    () =>
      useTicketListState(
        createPaginatedData<App.Platform.Data.TicketListEntry>([], { currentPage }),
        serverDefaultColumnFilters,
        '-createdAt',
      ),
    {
      pageProps: {
        groupFields,
        persistedViewPreferences,
        ziggy: createZiggyProps({ query }),
      },
    },
  );
}

describe('Hook: useTicketListState', () => {
  it('uses persisted display preferences and resets the page when the sort changes', () => {
    // ARRANGE
    const persistedViewPreferences = {
      columnVisibility: { game: false, type: true },
      sortParam: 'state',
    };

    // ACT
    const { result } = renderTicketListState({ currentPage: 3, persistedViewPreferences });

    // ASSERT
    expect(result.current.columnVisibilityOverrides).toEqual({ type: true });
    expect(result.current.sortParam).toBe('state');
    expect(result.current.pageNumber).toBe(3);

    act(() => result.current.setSortParam('createdAt'));

    expect(result.current.sortParam).toBe('createdAt');
    expect(result.current.pageNumber).toBe(1);
  });

  it('given the status filter has no resolved dates, switches it to resolved when the resolved sort is picked', () => {
    // ARRANGE
    const { result } = renderTicketListState();

    // ACT
    act(() => result.current.setSortParam('resolvedAt'));

    // ASSERT
    expect(result.current.sortParam).toEqual('resolvedAt');
    expect(result.current.columnFilters).toEqual([{ id: 'status', value: ['resolved'] }]);
  });

  it('given the status filter already has resolved dates, keeps it when the resolved sort is picked', () => {
    // ARRANGE
    const { result } = renderTicketListState({ query: { filter: { status: 'closed' } } });

    // ACT
    act(() => result.current.setSortParam('-resolvedAt'));

    // ASSERT
    expect(result.current.columnFilters).toEqual([{ id: 'status', value: ['closed'] }]);
  });

  it('given a sort other than resolved, keeps the status filter', () => {
    // ARRANGE
    const { result } = renderTicketListState();

    // ACT
    act(() => result.current.setSortParam('state'));

    // ASSERT
    expect(result.current.columnFilters).toEqual([{ id: 'status', value: ['unresolved'] }]);
  });

  it('prefers a URL sort over the persisted sort', () => {
    // ARRANGE
    const persistedViewPreferences = {
      columnVisibility: {},
      sortParam: 'state',
    };

    // ACT
    const { result } = renderTicketListState({
      persistedViewPreferences,
      query: { sort: '-resolvedAt' },
    });

    // ASSERT
    expect(result.current.sortParam).toBe('-resolvedAt');
  });

  it('uses a persisted grouping and resets the page when the grouping changes', () => {
    // ARRANGE
    const persistedViewPreferences = {
      columnVisibility: {},
      groupField: 'game',
      sortParam: 'state',
    };

    // ACT
    const { result } = renderTicketListState({ currentPage: 3, persistedViewPreferences });

    // ASSERT
    expect(result.current.groupField).toEqual('game');
    expect(result.current.pageNumber).toEqual(3);

    act(() => result.current.setGroupField('none'));

    expect(result.current.groupField).toEqual('none');
    expect(result.current.pageNumber).toEqual(1);
    expect(result.current.sortParam).toEqual('state');
  });

  it('given the URL turns grouping off, ignores the persisted grouping', () => {
    // ARRANGE
    const persistedViewPreferences = {
      columnVisibility: {},
      groupField: 'game',
    };

    // ACT
    const { result } = renderTicketListState({
      persistedViewPreferences,
      query: { group: 'none' },
    });

    // ASSERT
    expect(result.current.groupField).toEqual('none');
  });

  it('given the URL has some grouping the scope does not support, does not group', () => {
    // ACT
    const { result } = renderTicketListState({
      groupFields: ['game'],
      query: { group: 'ticketable' },
    });

    // ASSERT
    expect(result.current.groupField).toEqual('none');
  });

  it('ignores invalid persisted display preferences', () => {
    // ARRANGE
    const persistedViewPreferences = {
      columnVisibility: {
        game: false,
        id: false,
        unknown: true,
      },
      groupField: 'garbage',
      sortParam: 'garbage',
    };

    // ACT
    const { result } = renderTicketListState({ persistedViewPreferences });

    // ASSERT
    expect(result.current.columnVisibilityOverrides).toEqual({});
    expect(result.current.sortParam).toBe('-createdAt');
    expect(result.current.groupField).toEqual('none');
  });
});
