import { renderHook } from '@/test';

import { useFormatSessionsInfo } from './useFormatSessionInfo';

describe('Hook: useFormatSessionInfo', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useFormatSessionsInfo());

    // ASSERT
    expect(result.current.formatSessionsInfo).toBeTruthy();
  });

  it('given there are no sessions, returns "no sessions"', () => {
    // ARRANGE
    const { result } = renderHook(() => useFormatSessionsInfo());

    // ACT
    const formatted = result.current.formatSessionsInfo({
      sessionCount: 0,
      totalUnlockTime: 0,
    });

    // ASSERT
    expect(formatted).toEqual('No sessions');
  });

  it('given there is one session, returns "1 session"', () => {
    // ARRANGE
    const { result } = renderHook(() => useFormatSessionsInfo());

    // ACT
    const formatted = result.current.formatSessionsInfo({
      sessionCount: 1,
      totalUnlockTime: 3600,
    });

    // ASSERT
    expect(formatted).toEqual('1 session');
  });

  it('given there are multiple sessions over one day, returns sessions with a singular day string', () => {
    // ARRANGE
    const { result } = renderHook(() => useFormatSessionsInfo());

    // ACT
    const formatted = result.current.formatSessionsInfo({
      sessionCount: 5,
      totalUnlockTime: 86_400, // !! 24 hours in seconds
    });

    // ASSERT
    expect(formatted).toEqual('5 sessions over 1 day');
  });

  it('given there are multiple sessions over multiple days, returns sessions with a plural days string', () => {
    // ARRANGE
    const { result } = renderHook(() => useFormatSessionsInfo());

    // ACT
    const formatted = result.current.formatSessionsInfo({
      sessionCount: 10,
      totalUnlockTime: 172_800, // !! 48 hours in seconds
    });

    // ASSERT
    expect(formatted).toEqual('10 sessions over 2 days');
  });

  it('given there are multiple sessions over one hour, returns sessions with a singular hour string', () => {
    // ARRANGE
    const { result } = renderHook(() => useFormatSessionsInfo());

    // ACT
    const formatted = result.current.formatSessionsInfo({
      sessionCount: 3,
      totalUnlockTime: 3600, // !! 1 hour in seconds
    });

    // ASSERT
    expect(formatted).toEqual('3 sessions over 1 hour');
  });

  it('given there are multiple sessions over multiple hours, returns the correct string', () => {
    // ARRANGE
    const { result } = renderHook(() => useFormatSessionsInfo());

    // ACT
    const formatted = result.current.formatSessionsInfo({
      sessionCount: 3,
      totalUnlockTime: 7800, // !!
    });

    // ASSERT
    expect(formatted).toEqual('3 sessions over 3 hours');
  });
});
