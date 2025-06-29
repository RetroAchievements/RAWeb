import { useCookie } from 'react-use';

import { act, renderHook } from '@/test';

import { usePersistedGameIdsCookie } from './usePersistedGameIdsCookie';

vi.mock('react-use', async (importOriginal) => {
  const original = (await importOriginal()) as any;

  return {
    ...original,
    __esModule: true,

    useCookie: vi.fn(),
  };
});

describe('Hook: usePersistedGameIdsCookie', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('is defined', () => {
    // ASSERT
    expect(useCookie).toBeDefined();
  });

  it('given no cookie value exists, returns empty state', () => {
    // ARRANGE
    const mockSetCookieValue = vi.fn();
    vi.mocked(useCookie as any).mockReturnValue([undefined, mockSetCookieValue]);

    // ACT
    const { result } = renderHook(() => usePersistedGameIdsCookie('test-cookie', 123));

    // ASSERT
    expect(result.current.isGameIdInCookie()).toEqual(false);
  });

  it('given a cookie with game IDs exists, correctly parses the IDs', () => {
    // ARRANGE
    const mockSetCookieValue = vi.fn();
    vi.mocked(useCookie as any).mockReturnValue(['1,2,3,4', mockSetCookieValue]);

    // ACT
    const { result } = renderHook(() => usePersistedGameIdsCookie('test-cookie', 3));

    // ASSERT
    expect(result.current.isGameIdInCookie()).toEqual(true);
  });

  it('given the game ID is not in the cookie, isGameIdInCookie returns false', () => {
    // ARRANGE
    const mockSetCookieValue = vi.fn();
    vi.mocked(useCookie as any).mockReturnValue(['1,2,3,4', mockSetCookieValue]);

    // ACT
    const { result } = renderHook(() => usePersistedGameIdsCookie('test-cookie', 999));

    // ASSERT
    expect(result.current.isGameIdInCookie()).toEqual(false);
  });

  it('given toggleGameId is called with true and the ID is not present, adds the game ID', () => {
    // ARRANGE
    const mockSetCookieValue = vi.fn();
    vi.mocked(useCookie as any).mockReturnValue(['1,2,3', mockSetCookieValue]);

    const { result } = renderHook(() => usePersistedGameIdsCookie('test-cookie', 4));

    // ACT
    act(() => {
      result.current.toggleGameId(true);
    });

    // ASSERT
    expect(mockSetCookieValue).toHaveBeenCalledWith('1,2,3,4');
  });

  it('given toggleGameId is called with true and the ID already exists, does not duplicate it', () => {
    // ARRANGE
    const mockSetCookieValue = vi.fn();
    vi.mocked(useCookie as any).mockReturnValue(['1,2,3', mockSetCookieValue]);

    const { result } = renderHook(() => usePersistedGameIdsCookie('test-cookie', 2));

    // ACT
    act(() => {
      result.current.toggleGameId(true);
    });

    // ASSERT
    expect(mockSetCookieValue).toHaveBeenCalledWith('1,2,3');
  });

  it('given toggleGameId is called with false, removes the game ID', () => {
    // ARRANGE
    const mockSetCookieValue = vi.fn();
    vi.mocked(useCookie as any).mockReturnValue(['1,2,3,4', mockSetCookieValue]);

    const { result } = renderHook(() => usePersistedGameIdsCookie('test-cookie', 3));

    // ACT
    act(() => {
      result.current.toggleGameId(false);
    });

    // ASSERT
    expect(mockSetCookieValue).toHaveBeenCalledWith('1,2,4');
  });

  it('given adding a game ID would exceed the maximum limit, removes the oldest IDs', () => {
    // ARRANGE
    const mockSetCookieValue = vi.fn();

    // ... the existing cookie has exactly 10 game IDs ...
    vi.mocked(useCookie as any).mockReturnValue(['1,2,3,4,5,6,7,8,9,10', mockSetCookieValue]);

    const { result } = renderHook(() => usePersistedGameIdsCookie('test-cookie', 11));

    // ACT
    act(() => {
      result.current.toggleGameId(true);
    });

    // ASSERT
    // ... FIFO, so remove the first ID (1) and add the new one (11) ...
    expect(mockSetCookieValue).toHaveBeenCalledWith('2,3,4,5,6,7,8,9,10,11');
  });

  it('given the cookie contains invalid values for some reason, filters them out', () => {
    // ARRANGE
    const mockSetCookieValue = vi.fn();
    vi.mocked(useCookie as any).mockReturnValue(['1,abc,3,NaN,5', mockSetCookieValue]);

    const { result } = renderHook(() => usePersistedGameIdsCookie('test-cookie', 3));

    // ACT
    const isInCookie = result.current.isGameIdInCookie();

    // ASSERT
    // ... should correctly identify that 3 is in the cookie despite the bizarre invalid values ...
    expect(isInCookie).toEqual(true);
  });

  it('given the cookie is an empty string, handles this situation gracefully', () => {
    // ARRANGE
    const mockSetCookieValue = vi.fn();
    vi.mocked(useCookie as any).mockReturnValue(['', mockSetCookieValue]);

    const { result } = renderHook(() => usePersistedGameIdsCookie('test-cookie', 123));

    // ACT
    act(() => {
      result.current.toggleGameId(true);
    });

    // ASSERT
    expect(mockSetCookieValue).toHaveBeenCalledWith('123');
  });

  it('given toggleGameId is called with false on an empty cookie, handles this situation gracefully', () => {
    // ARRANGE
    const mockSetCookieValue = vi.fn();
    vi.mocked(useCookie as any).mockReturnValue([undefined, mockSetCookieValue]);

    const { result } = renderHook(() => usePersistedGameIdsCookie('test-cookie', 123));

    // ACT
    act(() => {
      result.current.toggleGameId(false);
    });

    // ASSERT
    // ... should set an empty string since there are no IDs ...
    expect(mockSetCookieValue).toHaveBeenCalledWith('');
  });
});
