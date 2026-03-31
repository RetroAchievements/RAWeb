import { useRef } from 'react';

import { getScreenshotGalleryUrl } from '@/common/utils/getScreenshotGalleryUrl';

/**
 * This hook preloads gallery screenshot images on hover so
 * they're cached or in-flight by the time the user clicks to
 * open the dialog.
 */
export function usePreloadGameScreenshots(
  screenshots: App.Platform.Data.GameScreenshot[] | undefined,
) {
  const hasPreloaded = useRef(false);

  const preloadGameScreenshots = () => {
    if (hasPreloaded.current || !screenshots?.length) {
      return;
    }
    hasPreloaded.current = true;

    // The first two screenshots (title + ingame) are already visible
    // as thumbnails, so skip them and preload the next two.
    for (const screenshot of screenshots.slice(2, 4)) {
      const img = new Image();
      img.src = getScreenshotGalleryUrl(screenshot);
    }
  };

  return { preloadGameScreenshots };
}
