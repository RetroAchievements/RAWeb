/**
 * Low-res original images are lossless and small enough that
 * WebP compression hurts more than it helps. Larger originals
 * benefit from the optimized WebP conversions.
 */
const losslessWidthThreshold = 320;

export function getScreenshotGalleryUrl(screenshot: App.Platform.Data.GameScreenshot): string {
  return screenshot.width > losslessWidthThreshold ? screenshot.lgWebpUrl : screenshot.originalUrl;
}
