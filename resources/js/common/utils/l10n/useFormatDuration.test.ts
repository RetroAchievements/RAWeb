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

  it('given only seconds with truncateSeconds option, still shows seconds', () => {
    // ARRANGE
    const { result } = renderHook(() => useFormatDuration());

    // ACT
    const formatted = result.current.formatDuration(29, { shouldTruncateSeconds: true }); // !! only seconds

    // ASSERT
    expect(formatted).toEqual('29s');
  });

  it('given minutes and seconds with truncateSeconds option, only shows minutes', () => {
    // ARRANGE
    const { result } = renderHook(() => useFormatDuration());

    // ACT
    const formatted = result.current.formatDuration(125, { shouldTruncateSeconds: true }); // !! truncateSeconds enabled

    // ASSERT
    expect(formatted).toEqual('2m');
  });

  it('given hours, minutes, and seconds with truncateSeconds option, only shows hours and minutes', () => {
    // ARRANGE
    const { result } = renderHook(() => useFormatDuration());

    // ACT
    const formatted = result.current.formatDuration(8549, { shouldTruncateSeconds: true }); // !! 2h 22m 29s truncated

    // ASSERT
    expect(formatted).toEqual('2h 22m');
  });
});
