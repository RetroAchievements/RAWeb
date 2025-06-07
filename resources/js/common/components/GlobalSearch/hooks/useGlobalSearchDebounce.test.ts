import { act, renderHook } from '@/test';

import { useGlobalSearchDebounce } from './useGlobalSearchDebounce';

describe('Hook: useGlobalSearchDebounce', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.runOnlyPendingTimers();
    vi.useRealTimers();
  });

  it('given the first render, does not call setSearchTerm', () => {
    // ARRANGE
    const mockSetSearchTerm = vi.fn();

    renderHook(() =>
      useGlobalSearchDebounce({ rawQuery: 'test query', setSearchTerm: mockSetSearchTerm }),
    );

    act(() => {
      vi.advanceTimersByTime(1000);
    });

    // ASSERT
    expect(mockSetSearchTerm).not.toHaveBeenCalled();
  });

  it('given a query with less than 3 characters after first render, does not call setSearchTerm', () => {
    // ARRANGE
    const mockSetSearchTerm = vi.fn();
    const { rerender } = renderHook(
      ({ rawQuery }) => useGlobalSearchDebounce({ rawQuery, setSearchTerm: mockSetSearchTerm }),
      { initialProps: { rawQuery: '' } },
    );

    // ACT
    rerender({ rawQuery: 'ab' });
    act(() => {
      vi.advanceTimersByTime(2000); // !! longer than any debounce duration
    });

    // ASSERT
    expect(mockSetSearchTerm).not.toHaveBeenCalled();
  });

  it('given a query with 3 or more characters, calls setSearchTerm after 500ms', () => {
    // ARRANGE
    const mockSetSearchTerm = vi.fn();
    const { rerender } = renderHook(
      ({ rawQuery }) => useGlobalSearchDebounce({ rawQuery, setSearchTerm: mockSetSearchTerm }),
      { initialProps: { rawQuery: '' } },
    );

    // ... advance past first render ...
    act(() => {
      vi.advanceTimersByTime(0);
    });

    // ACT
    rerender({ rawQuery: 'mario' });

    act(() => {
      vi.advanceTimersByTime(499);
    });

    expect(mockSetSearchTerm).not.toHaveBeenCalled();
    act(() => {
      vi.advanceTimersByTime(1);
    });

    // ASSERT
    expect(mockSetSearchTerm).toHaveBeenCalledWith('mario');
    expect(mockSetSearchTerm).toHaveBeenCalledTimes(1);
  });

  it('given the query is cleared to empty string, calls setSearchTerm immediately', () => {
    // ARRANGE
    const mockSetSearchTerm = vi.fn();
    const { rerender } = renderHook(
      ({ rawQuery }) => useGlobalSearchDebounce({ rawQuery, setSearchTerm: mockSetSearchTerm }),
      { initialProps: { rawQuery: '' } },
    );

    // ... advance past first render ...
    act(() => {
      vi.advanceTimersByTime(0);
    });

    // ... set a valid query ...
    rerender({ rawQuery: 'test' });
    act(() => {
      vi.advanceTimersByTime(500);
    });

    mockSetSearchTerm.mockClear();

    // ACT
    rerender({ rawQuery: '' });

    // ... since empty string has 0 debounce, it should be called immediately ...
    act(() => {
      vi.advanceTimersByTime(0);
    });

    // ASSERT
    expect(mockSetSearchTerm).toHaveBeenCalledWith('');
    expect(mockSetSearchTerm).toHaveBeenCalledTimes(1);
  });

  it('given rapid query changes, only calls setSearchTerm with the latest value', () => {
    // ARRANGE
    const mockSetSearchTerm = vi.fn();
    const { rerender } = renderHook(
      ({ rawQuery }) => useGlobalSearchDebounce({ rawQuery, setSearchTerm: mockSetSearchTerm }),
      { initialProps: { rawQuery: '' } },
    );

    // ... advance past first render ...
    act(() => {
      vi.advanceTimersByTime(0);
    });

    // ACT
    // ... simulate rapid typing ...
    rerender({ rawQuery: 'sup' });
    act(() => {
      vi.advanceTimersByTime(100);
    });

    rerender({ rawQuery: 'supe' });
    act(() => {
      vi.advanceTimersByTime(100);
    });

    rerender({ rawQuery: 'super' });
    act(() => {
      vi.advanceTimersByTime(100);
    });

    rerender({ rawQuery: 'super mario' });

    // ASSERT
    expect(mockSetSearchTerm).not.toHaveBeenCalled();

    act(() => {
      vi.advanceTimersByTime(500);
    });

    expect(mockSetSearchTerm).toHaveBeenCalledWith('super mario');
    expect(mockSetSearchTerm).toHaveBeenCalledTimes(1);
  });

  it('given a short query (1-2 chars), uses a 1000ms debounce duration', () => {
    // ARRANGE
    const mockSetSearchTerm = vi.fn();
    const { rerender } = renderHook(
      ({ rawQuery }) => useGlobalSearchDebounce({ rawQuery, setSearchTerm: mockSetSearchTerm }),
      { initialProps: { rawQuery: '' } },
    );

    // ACT
    rerender({ rawQuery: 'a' });
    act(() => {
      vi.advanceTimersByTime(999);
    });

    // ASSERT
    expect(mockSetSearchTerm).not.toHaveBeenCalled();

    act(() => {
      vi.advanceTimersByTime(1);
    });

    expect(mockSetSearchTerm).not.toHaveBeenCalled();
  });
});
