import { renderHook } from '@/test';

import { useFormatDuration } from './useFormatDuration';

describe('Hook: useFormatDuration', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useFormatDuration());

    // ASSERT
    expect(result.current.formatDuration).toBeTruthy();
  });

  it('given only seconds, formats with just seconds', () => {
    // ARRANGE
    const { result } = renderHook(() => useFormatDuration());

    // ACT
    const formatted = result.current.formatDuration(45);

    // ASSERT
    expect(formatted).toEqual('45s');
  });

  it('given minutes and seconds, formats with minutes and seconds', () => {
    // ARRANGE
    const { result } = renderHook(() => useFormatDuration());

    // ACT
    const formatted = result.current.formatDuration(125);

    // ASSERT
    expect(formatted).toEqual('2m 5s');
  });

  it('given hours, minutes, and seconds, formats with hours, minutes, and seconds', () => {
    // ARRANGE
    const { result } = renderHook(() => useFormatDuration());

    // ACT
    const formatted = result.current.formatDuration(3665);

    // ASSERT
    expect(formatted).toEqual('1h 1m 5s');
  });
});
