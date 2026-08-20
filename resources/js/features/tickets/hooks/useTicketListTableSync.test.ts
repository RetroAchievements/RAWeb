import { renderHook } from '@/test';

import { useTicketListTableSync } from './useTicketListTableSync';

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

  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useTicketListTableSync(1));

    // ASSERT
    expect(result).toBeDefined();
  });

  it('given it is the first render cycle, does not update URL params', () => {
    // ARRANGE
    renderHook(() => useTicketListTableSync(1));

    // ASSERT
    expect(pushStateSpy).not.toHaveBeenCalled();
  });

  it('given the user advances from page 1 to page 2, updates URL params accordingly', () => {
    // ARRANGE
    const { rerender } = renderHook((pageNumber: number) => useTicketListTableSync(pageNumber), {
      initialProps: 1,
    });

    // ACT
    rerender(3);

    // ASSERT
    expect(pushStateSpy).toHaveBeenCalledWith(
      { inertia: true },
      '',
      `/tickets2?${encodeURIComponent('page[number]')}=3`,
    );
  });

  it('given the user goes from page 2 to page 1, updates URL params accordingly', () => {
    // ARRANGE
    setWindowLocation('?page[number]=3');

    const { rerender } = renderHook((pageNumber: number) => useTicketListTableSync(pageNumber), {
      initialProps: 3,
    });

    // ACT
    rerender(1);

    // ASSERT
    expect(pushStateSpy).toHaveBeenCalledWith({ inertia: true }, '', '/tickets2');
  });

  it('given the URL contains unrelated params, leaves them untouched', () => {
    // ARRANGE
    setWindowLocation('?filter[status]=resolved&sort=state');

    const { rerender } = renderHook((pageNumber: number) => useTicketListTableSync(pageNumber), {
      initialProps: 1,
    });

    // ACT
    rerender(2);

    // ASSERT
    expect(pushStateSpy).toHaveBeenCalledWith(
      { inertia: true },
      '',
      '/tickets2?filter%5Bstatus%5D=resolved&sort=state&page%5Bnumber%5D=2',
    );
  });

  it('given the current URL params already match the serialized state, does not push a new history entry', () => {
    // ARRANGE
    setWindowLocation('?page%5Bnumber%5D=3');

    const { rerender } = renderHook((pageNumber: number) => useTicketListTableSync(pageNumber), {
      initialProps: 1,
    });

    // ACT
    rerender(3);

    // ASSERT
    expect(pushStateSpy).not.toHaveBeenCalled();
  });
});
