import type { ScreenshotGalleryOptions } from '@/common/models';

/**
 * Low-res and pixel art original images are lossless enough that
 * WebP compression hurts more than it helps. Larger non-pixel-art
 * originals benefit from the optimized WebP conversions.
 */
const losslessWidthThreshold = 320;

export function getScreenshotGalleryUrl(
  screenshot: App.Platform.Data.GameScreenshot,
  { isPixelated = false }: ScreenshotGalleryOptions = {},
): string {
  if (isPixelated) {
    return screenshot.originalUrl;
  }

  return screenshot.width > losslessWidthThreshold ? screenshot.lgWebpUrl : screenshot.originalUrl;
}
