import { renderHook } from '@/test';
import { createGameScreenshot } from '@/test/factories';

import { usePreloadGameScreenshots } from './usePreloadGameScreenshots';

describe('Hook: usePreloadGameScreenshots', () => {
  it('given screenshots are provided, preloads the first few images on call', () => {
    // ARRANGE
    const screenshots = [
      createGameScreenshot({ id: 1, width: 256, originalUrl: 'https://example.com/1.png' }),
      createGameScreenshot({ id: 2, width: 256, originalUrl: 'https://example.com/2.png' }),
    ];

    const { result } = renderHook(() => usePreloadGameScreenshots(screenshots));

    // ACT
    result.current.preloadGameScreenshots();

    // ASSERT
    expect(result.current.preloadGameScreenshots).toBeInstanceOf(Function);
  });

  it('given no screenshots, does not throw when called', () => {
    // ARRANGE
    const { result } = renderHook(() => usePreloadGameScreenshots(undefined));

    // ACT & ASSERT
    expect(() => result.current.preloadGameScreenshots()).not.toThrow();
  });

  it('given the preload function is called twice, only preloads once', () => {
    // ARRANGE
    const ImageSpy = vi.fn();
    vi.stubGlobal('Image', ImageSpy);

    const screenshots = [
      createGameScreenshot({ id: 1, width: 256, originalUrl: 'https://example.com/1.png' }),
      createGameScreenshot({ id: 2, width: 256, originalUrl: 'https://example.com/2.png' }),
      createGameScreenshot({ id: 3, width: 256, originalUrl: 'https://example.com/3.png' }),
    ];

    const { result } = renderHook(() => usePreloadGameScreenshots(screenshots));

    // ACT
    result.current.preloadGameScreenshots();
    result.current.preloadGameScreenshots();

    // ASSERT
    expect(ImageSpy).toHaveBeenCalledTimes(3); // !! not 6

    vi.unstubAllGlobals();
  });
});
