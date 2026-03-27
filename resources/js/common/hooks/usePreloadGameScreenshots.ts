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

    // Preload only the first few images.
    for (const screenshot of screenshots.slice(0, 3)) {
      const img = new Image();
      img.src = getScreenshotGalleryUrl(screenshot);
    }
  };

  return { preloadGameScreenshots };
}
