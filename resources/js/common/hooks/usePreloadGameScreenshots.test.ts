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

  it('given screenshots are provided, preloads the first four gallery URLs', () => {
    // ARRANGE
    const preloadedImages: Array<{ src?: string }> = [];
    const ImageSpy = vi.fn(function (this: Record<string, unknown>) {
      preloadedImages.push(this);
    });
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
    expect(ImageSpy).toHaveBeenCalledTimes(4);
    expect(preloadedImages[0].src).toEqual('https://example.com/title.png');
    expect(preloadedImages[1].src).toEqual('https://example.com/ingame.png');
    expect(preloadedImages[2].src).toEqual('https://example.com/third.png');
    expect(preloadedImages[3].src).toEqual('https://example.com/fourth.png');

    vi.unstubAllGlobals();
  });

  it('given pixelated screenshots are provided, preloads original lossless URLs', () => {
    // ARRANGE
    const preloadedImages: Array<{ src?: string }> = [];
    const ImageSpy = vi.fn(function (this: Record<string, unknown>) {
      preloadedImages.push(this);
    });
    vi.stubGlobal('Image', ImageSpy);

    const screenshots = [
      createGameScreenshot({
        id: 1,
        width: 560,
        originalUrl: 'https://example.com/first.png',
        lgWebpUrl: 'https://example.com/first.webp',
      }),
      createGameScreenshot({
        id: 2,
        width: 560,
        originalUrl: 'https://example.com/second.png',
        lgWebpUrl: 'https://example.com/second.webp',
      }),
      createGameScreenshot({
        id: 3,
        width: 560,
        originalUrl: 'https://example.com/third.png',
        lgWebpUrl: 'https://example.com/third.webp',
      }),
      createGameScreenshot({
        id: 4,
        width: 560,
        originalUrl: 'https://example.com/fourth.png',
        lgWebpUrl: 'https://example.com/fourth.webp',
      }),
    ];

    const { result } = renderHook(() =>
      usePreloadGameScreenshots(screenshots, { isPixelated: true }),
    );

    // ACT
    result.current.preloadGameScreenshots();

    // ASSERT
    expect(ImageSpy).toHaveBeenCalledTimes(4);
    expect(preloadedImages[0].src).toEqual('https://example.com/first.png');
    expect(preloadedImages[1].src).toEqual('https://example.com/second.png');
    expect(preloadedImages[2].src).toEqual('https://example.com/third.png');
    expect(preloadedImages[3].src).toEqual('https://example.com/fourth.png');

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
    expect(ImageSpy).toHaveBeenCalledTimes(4);

    vi.unstubAllGlobals();
  });

  it('given fewer than four screenshots, preloads everything that is available', () => {
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
    expect(ImageSpy).toHaveBeenCalledTimes(2);

    vi.unstubAllGlobals();
  });
});
