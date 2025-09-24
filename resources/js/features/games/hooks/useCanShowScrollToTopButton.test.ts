import { act, renderHook, waitFor } from '@/test';

import { useCanShowScrollToTopButton } from './useCanShowScrollToTopButton';

describe('Hook: useCanShowScrollToTopButton', () => {
  beforeEach(() => {
    Object.defineProperty(document.documentElement, 'scrollHeight', {
      value: 3000,
      writable: true,
      configurable: true,
    });
    Object.defineProperty(window, 'innerHeight', {
      value: 1000,
      writable: true,
      configurable: true,
    });
    Object.defineProperty(window, 'scrollY', {
      value: 0,
      writable: true,
      configurable: true,
    });

    vi.spyOn(window, 'requestAnimationFrame').mockImplementation((cb) => {
      setTimeout(() => cb(0), 0);

      return 0;
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('on mount, returns false', () => {
    // ACT
    const { result } = renderHook(() => useCanShowScrollToTopButton());

    // ASSERT
    expect(result.current).toEqual(false);
  });

  it('given the user has not scrolled past 800px, returns false', () => {
    // ARRANGE
    const { result } = renderHook(() => useCanShowScrollToTopButton());

    // ACT
    act(() => {
      Object.defineProperty(window, 'scrollY', { value: 500, configurable: true });
      window.dispatchEvent(new Event('scroll'));
    });

    // ASSERT
    expect(result.current).toEqual(false);
  });

  it('given the user scrolls down past 800px then scrolls up, returns true', async () => {
    // ARRANGE
    const { result } = renderHook(() => useCanShowScrollToTopButton());

    // ... scroll down past 800px ...
    act(() => {
      Object.defineProperty(window, 'scrollY', { value: 900, configurable: true });
      window.dispatchEvent(new Event('scroll'));
    });

    await waitFor(() => {
      // ... wait for the first scroll to process ...
      expect(result.current).toEqual(false);
    });

    // ACT
    act(() => {
      Object.defineProperty(window, 'scrollY', { value: 850, configurable: true });
      window.dispatchEvent(new Event('scroll'));
    });

    // ASSERT
    await waitFor(() => {
      expect(result.current).toEqual(true);
    });
  });

  it('given the user is scrolling up then scrolls down, returns false', async () => {
    // ARRANGE
    const { result } = renderHook(() => useCanShowScrollToTopButton());

    // ... scroll down, then up to get isScrollingUp = true ...
    act(() => {
      Object.defineProperty(window, 'scrollY', { value: 900, configurable: true });
      window.dispatchEvent(new Event('scroll'));
    });

    await waitFor(() => {
      expect(result.current).toEqual(false);
    });

    act(() => {
      Object.defineProperty(window, 'scrollY', { value: 850, configurable: true });
      window.dispatchEvent(new Event('scroll'));
    });

    await waitFor(() => {
      expect(result.current).toEqual(true);
    });

    // ACT
    // ... scroll down ...
    act(() => {
      Object.defineProperty(window, 'scrollY', { value: 870, configurable: true });
      window.dispatchEvent(new Event('scroll'));
    });

    // ASSERT
    await waitFor(() => {
      expect(result.current).toEqual(false);
    });
  });

  it('given the user is within 200px of the bottom of the page, returns false', () => {
    // ARRANGE
    const { result } = renderHook(() => useCanShowScrollToTopButton());

    // ACT
    // ... scroll to near the bottom (remaining scroll = 2000 - 1850 = 150px) ...
    act(() => {
      Object.defineProperty(window, 'scrollY', { value: 1850, configurable: true });
      window.dispatchEvent(new Event('scroll'));
    });

    // ASSERT
    expect(result.current).toEqual(false);
  });

  it('given the component unmounts, cleans up the event listener', () => {
    // ARRANGE
    const removeEventListenerSpy = vi.spyOn(window, 'removeEventListener');
    const { unmount } = renderHook(() => useCanShowScrollToTopButton());

    // ACT
    unmount();

    // ASSERT
    expect(removeEventListenerSpy).toHaveBeenCalledWith('scroll', expect.any(Function));
  });
});
