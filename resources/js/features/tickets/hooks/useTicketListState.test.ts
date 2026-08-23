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
      columnVisibility: { game: false, report: true },
      sortParam: 'state',
    };

    // ACT
    const { result } = renderTicketListState({ currentPage: 3, persistedViewPreferences });

    // ASSERT
    expect(result.current.columnVisibilityOverrides).toEqual({ game: false, report: true });
    expect(result.current.sortParam).toBe('state');
    expect(result.current.pageNumber).toBe(3);

    act(() => result.current.setSortParam('createdAt'));

    expect(result.current.sortParam).toBe('createdAt');
    expect(result.current.pageNumber).toBe(1);
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
    expect(result.current.columnVisibilityOverrides).toEqual({ game: false });
    expect(result.current.sortParam).toBe('-createdAt');
  });
});
