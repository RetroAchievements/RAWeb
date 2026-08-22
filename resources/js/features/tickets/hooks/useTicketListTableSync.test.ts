import type { ColumnFiltersState } from '@tanstack/react-table';

import { renderHook } from '@/test';

import { useTicketListTableSync } from './useTicketListTableSync';

const serverDefaultColumnFilters: ColumnFiltersState = [{ id: 'status', value: ['unresolved'] }];

type TicketListTableSyncProps = Parameters<typeof useTicketListTableSync>[0];

const defaultProps: TicketListTableSyncProps = {
  columnFilters: serverDefaultColumnFilters,
  serverDefaultColumnFilters,
  pageNumber: 1,
};

function renderTicketListTableSync() {
  return renderHook((props: TicketListTableSyncProps) => useTicketListTableSync(props), {
    initialProps: defaultProps,
  });
}

function setWindowLocation(search: string) {
  Object.defineProperty(window, 'location', {
    writable: true,
    value: { search, pathname: '/tickets2' },
  });
}

describe('Hook: useTicketListTableSync', () => {
  let pushStateSpy: ReturnType<typeof vi.spyOn>;
  let originalLocation: Location;

  beforeEach(() => {
    originalLocation = window.location;
    setWindowLocation('');

    pushStateSpy = vi.spyOn(window.history, 'pushState').mockImplementation(() => {});
  });

  afterEach(() => {
    Object.defineProperty(window, 'location', {
      writable: true,
      value: originalLocation,
    });
    pushStateSpy.mockRestore();
  });

  it('given it is the first render cycle, does not update URL params', () => {
    // ARRANGE
    renderTicketListTableSync();

    // ASSERT
    expect(pushStateSpy).not.toHaveBeenCalled();
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
      { inertia: true },
      '',
      `/tickets2?${encodeURIComponent('page[number]')}=3`,
    );

    // ACT
    setWindowLocation('?page[number]=3');
    rerender({
      ...defaultProps,
    });

    // ASSERT
    expect(pushStateSpy).toHaveBeenCalledWith({ inertia: true }, '', '/tickets2');
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
      { inertia: true },
      '',
      '/tickets2?filter%5Bstatus%5D=resolved',
    );

    setWindowLocation('?filter[status]=resolved');
    rerender({
      ...defaultProps,
    });

    // ASSERT
    expect(pushStateSpy).toHaveBeenCalledWith({ inertia: true }, '', '/tickets2');
  });

  it('given the URL contains unrelated params, leaves them untouched', () => {
    // ARRANGE
    setWindowLocation('?sort=state');

    const { rerender } = renderTicketListTableSync();

    // ACT
    rerender({
      ...defaultProps,
      pageNumber: 2,
    });

    // ASSERT
    expect(pushStateSpy).toHaveBeenCalledWith(
      { inertia: true },
      '',
      `/tickets2?sort=state&${encodeURIComponent('page[number]')}=2`,
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
});
