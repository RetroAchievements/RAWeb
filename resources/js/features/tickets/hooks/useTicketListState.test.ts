import { act, renderHook } from '@/test';
import { createPaginatedData, createZiggyProps } from '@/test/factories';

import { useTicketListState } from './useTicketListState';

const serverDefaultColumnFilters = [{ id: 'status', value: ['unresolved'] }];

function renderTicketListState({
  currentPage = 1,
  persistedViewPreferences = null,
  query = {},
}: {
  currentPage?: number;
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

  it('ignores invalid persisted display preferences', () => {
    // ARRANGE
    const persistedViewPreferences = {
      columnVisibility: {
        game: false,
        id: false,
        unknown: true,
      },
      sortParam: 'garbage',
    };

    // ACT
    const { result } = renderTicketListState({ persistedViewPreferences });

    // ASSERT
    expect(result.current.columnVisibilityOverrides).toEqual({});
    expect(result.current.sortParam).toBe('-createdAt');
  });
});
