import { renderHook } from '@/test';
import { createGameScreenshot } from '@/test/factories';

import { usePreloadGameScreenshots } from './usePreloadGameScreenshots';

describe('Hook: usePreloadGameScreenshots', () => {
  it('given no screenshots, does not throw when called', () => {
    // ARRANGE
    const { result } = renderHook(() => usePreloadGameScreenshots(undefined));

    // ASSERT
    expect(() => result.current.preloadGameScreenshots()).not.toThrow();
  });

  it('given screenshots are provided, skips the first two and preloads the next two', () => {
    // ARRANGE
    const ImageSpy = vi.fn();
    vi.stubGlobal('Image', ImageSpy);

    const screenshots = [
      createGameScreenshot({ id: 1, width: 256, originalUrl: 'https://example.com/title.png' }),
      createGameScreenshot({ id: 2, width: 256, originalUrl: 'https://example.com/ingame.png' }),
      createGameScreenshot({ id: 3, width: 256, originalUrl: 'https://example.com/third.png' }),
      createGameScreenshot({ id: 4, width: 256, originalUrl: 'https://example.com/fourth.png' }),
      createGameScreenshot({ id: 5, width: 256, originalUrl: 'https://example.com/fifth.png' }),
    ];

    const { result } = renderHook(() => usePreloadGameScreenshots(screenshots));

    // ACT
    result.current.preloadGameScreenshots();

    // ASSERT
    expect(ImageSpy).toHaveBeenCalledTimes(2);

    vi.unstubAllGlobals();
  });

  it('given the preload function is called twice, only preloads once', () => {
    // ARRANGE
    const ImageSpy = vi.fn();
    vi.stubGlobal('Image', ImageSpy);

    const screenshots = [
      createGameScreenshot({ id: 1, width: 256 }),
      createGameScreenshot({ id: 2, width: 256 }),
      createGameScreenshot({ id: 3, width: 256 }),
      createGameScreenshot({ id: 4, width: 256 }),
    ];

    const { result } = renderHook(() => usePreloadGameScreenshots(screenshots));

    // ACT
    result.current.preloadGameScreenshots();
    result.current.preloadGameScreenshots();

    // ASSERT
    expect(ImageSpy).toHaveBeenCalledTimes(2);

    vi.unstubAllGlobals();
  });

  it('given fewer than three screenshots, does not preload anything', () => {
    // ARRANGE
    const ImageSpy = vi.fn();
    vi.stubGlobal('Image', ImageSpy);

    const screenshots = [
      createGameScreenshot({ id: 1, width: 256 }),
      createGameScreenshot({ id: 2, width: 256 }),
    ];

    const { result } = renderHook(() => usePreloadGameScreenshots(screenshots));

    // ACT
    result.current.preloadGameScreenshots();

    // ASSERT
    expect(ImageSpy).toHaveBeenCalledTimes(0);

    vi.unstubAllGlobals();
  });
});
