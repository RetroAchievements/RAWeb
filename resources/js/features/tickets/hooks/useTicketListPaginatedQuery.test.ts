import { renderHook } from '@/test';
import {
  createPaginatedData,
  createTicketListEntry,
  createTicketListStateCounts,
} from '@/test/factories';

import { useTicketListPaginatedQuery } from './useTicketListPaginatedQuery';

const { useQueryMock } = vi.hoisted(() => ({ useQueryMock: vi.fn() }));

vi.mock('@tanstack/react-query', async () => {
  const actual =
    await vi.importActual<typeof import('@tanstack/react-query')>('@tanstack/react-query');

  return { ...actual, useQuery: useQueryMock };
});

function buildDefaultProps() {
  return {
    columnFilters: [{ id: 'status', value: ['unresolved'] }],
    initialData: {
      paginatedTickets: createPaginatedData([createTicketListEntry({ id: 1001 })], {
        currentPage: 1,
        lastPage: 1,
        perPage: 50,
        total: 1,
      }),
      stateCounts: createTicketListStateCounts(),
      facetCounts: {},
    },
    pageNumber: 1,
    scope: 'all',
    sortParam: '-createdAt',
    targetParams: {},
  };
}

describe('Hook: useTicketListPaginatedQuery', () => {
  it('is defined', () => {
    // ASSERT
    expect(useTicketListPaginatedQuery).toBeDefined();
  });

  it('given the query has data, reports the query data', () => {
    // ARRANGE
    const queryData = {
      paginatedTickets: createPaginatedData([createTicketListEntry({ id: 2002 })], {
        currentPage: 1,
        lastPage: 1,
        perPage: 50,
        total: 1,
      }),
      stateCounts: createTicketListStateCounts(),
      facetCounts: {},
    };
    useQueryMock.mockReturnValue({ data: queryData, isFetching: false });

    // ACT
    const { result } = renderHook(() => useTicketListPaginatedQuery(buildDefaultProps() as any));

    // ASSERT
    expect(result.current.data.paginatedTickets.items[0].id).toEqual(2002);
  });

  it('given the query errored and has no data, falls back to the server-sent value', () => {
    // ARRANGE
    useQueryMock.mockReturnValue({ data: undefined, isFetching: false });

    // ACT
    const { result } = renderHook(() => useTicketListPaginatedQuery(buildDefaultProps() as any));

    // ASSERT
    expect(result.current.data.stateCounts).toBeDefined();
    expect(result.current.data.paginatedTickets.items[0].id).toEqual(1001);
  });
});
