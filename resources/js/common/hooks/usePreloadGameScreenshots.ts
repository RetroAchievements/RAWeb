import { useRef } from 'react';

import type { ScreenshotGalleryOptions } from '@/common/models';
import { getScreenshotGalleryUrl } from '@/common/utils/getScreenshotGalleryUrl';

/**
 * This hook preloads gallery screenshot images on hover so
 * they're cached or in-flight by the time the user clicks to
 * open the dialog.
 */
export function usePreloadGameScreenshots(
  screenshots: App.Platform.Data.GameScreenshot[] | undefined,
  { isPixelated = false }: ScreenshotGalleryOptions = {},
) {
  const hasPreloaded = useRef(false);

  const preloadGameScreenshots = () => {
    if (hasPreloaded.current || !screenshots?.length) {
      return;
    }
    hasPreloaded.current = true;

    // The visible thumbnails use a different conversion than the gallery, so they don't
    // warm the gallery cache. Preload the first few gallery-sized URLs the user is most
    // likely to see when the dialog opens.
    for (const screenshot of screenshots.slice(0, 4)) {
      const img = new Image();
      img.src = getScreenshotGalleryUrl(screenshot, { isPixelated });
    }
  };

  return { preloadGameScreenshots };
}
