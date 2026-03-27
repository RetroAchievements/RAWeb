import type { FC } from 'react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCamera } from 'react-icons/lu';

import { usePreloadGameScreenshots } from '@/common/hooks/usePreloadGameScreenshots';
import { cn } from '@/common/utils/cn';

import { ScreenshotGalleryDialog } from '../ScreenshotGalleryDialog';
import { ZoomableImage } from '../ZoomableImage';

interface PlayableMainMediaProps {
  imageIngameUrl: string;
  imageTitleUrl: string;

  /** Set width/height on img tags to reserve space and prevent layout shift. */
  expectedHeight?: number | null;
  expectedWidth?: number | null;
  hasAnalogTvOutput?: boolean;
  hasBeatenGame?: boolean;
  isPixelated?: boolean;
  numScreenshots?: number;
  screenshots?: App.Platform.Data.GameScreenshot[];
}

export const PlayableMainMedia: FC<PlayableMainMediaProps> = ({
  expectedHeight,
  expectedWidth,
  hasAnalogTvOutput,
  hasBeatenGame,
  imageIngameUrl,
  imageTitleUrl,
  isPixelated,
  screenshots,
  numScreenshots = 0,
}) => {
  const { t } = useTranslation();

  // null = closed, number = open at that screenshot index.
  const [openAtIndex, setOpenAtIndex] = useState<number | null>(null);

  const { preloadGameScreenshots } = usePreloadGameScreenshots(screenshots);

  // If both images are the "No Screenshot Found" default, display nothing.
  if (imageTitleUrl.includes('000002') && imageIngameUrl.includes('000002')) {
    return null;
  }

  // Show the gallery UI as soon as the eager count arrives, even before
  // the deferred screenshots array has resolved. The dialog itself is
  // only opened once the actual data is present.
  const hasGallery = numScreenshots > 0;
  const canOpenGallery = hasGallery && !!screenshots?.length;

  const dimensionProps =
    expectedWidth && expectedHeight ? { width: expectedWidth, height: expectedHeight } : {};

  // CRT systems displayed non-square pixels stretched to 4:3. The aspect
  // ratio is always 4:3 regardless of the native pixel resolution.
  const aspectRatio = hasAnalogTvOutput ? 4 / 3 : undefined;
  const aspectRatioStyle = aspectRatio ? { aspectRatio } : undefined;

  const handleOpenGallery = (type: 'title' | 'ingame') => {
    // Find the index of the first screenshot matching the clicked type.
    const targetIndex = screenshots!.findIndex((s) => s.type === type);

    setOpenAtIndex(targetIndex >= 0 ? targetIndex : 0);
  };

  const imgProps = { className: 'w-full rounded-sm', style: aspectRatioStyle, ...dimensionProps };

  return (
    <div
      className={cn(
        'flex w-full items-center justify-around',
        'border border-embed-highlight bg-zinc-900/50 light:bg-neutral-50',
        'gap-x-5 gap-y-1',
        'xl:mx-0 xl:min-h-[180px] xl:w-full xl:rounded-lg xl:px-4 xl:py-2',
      )}
    >
      {hasGallery ? (
        <>
          <button
            type="button"
            disabled={!canOpenGallery}
            className="cursor-pointer"
            onMouseEnter={preloadGameScreenshots}
            onClick={() => handleOpenGallery('title')}
          >
            <div className="flex items-center justify-center overflow-hidden">
              <img src={imageTitleUrl} alt={t('title screenshot')} {...imgProps} />
            </div>
          </button>

          <button
            type="button"
            disabled={!canOpenGallery}
            className="relative cursor-pointer"
            onMouseEnter={preloadGameScreenshots}
            onClick={() => handleOpenGallery('ingame')}
          >
            <div className="flex items-center justify-center overflow-hidden">
              <img src={imageIngameUrl} alt={t('ingame screenshot')} {...imgProps} />
            </div>

            {numScreenshots > 1 ? (
              <span
                className={cn(
                  'absolute bottom-1.5 right-1.5 flex items-center gap-1',
                  'rounded bg-black/80 px-1.5 py-0.5 text-2xs text-white/90',
                )}
              >
                <LuCamera className="size-3" />
                {numScreenshots}
              </span>
            ) : null}
          </button>

          {canOpenGallery ? (
            <ScreenshotGalleryDialog
              screenshots={screenshots!}
              initialIndex={openAtIndex ?? 0}
              isOpen={openAtIndex !== null}
              onOpenChange={() => setOpenAtIndex(null)}
              hasAnalogTvOutput={hasAnalogTvOutput}
              hasBeatenGame={hasBeatenGame}
              isPixelated={isPixelated}
            />
          ) : null}
        </>
      ) : (
        <>
          <ZoomableImage
            src={imageTitleUrl}
            alt={t('title screenshot')}
            aspectRatio={aspectRatio}
            isPixelated={isPixelated}
          >
            <div className="flex items-center justify-center overflow-hidden">
              <img src={imageTitleUrl} alt={t('title screenshot')} {...imgProps} />
            </div>
          </ZoomableImage>

          <ZoomableImage
            src={imageIngameUrl}
            alt={t('ingame screenshot')}
            aspectRatio={aspectRatio}
            isPixelated={isPixelated}
          >
            <div className="flex items-center justify-center overflow-hidden">
              <img src={imageIngameUrl} alt={t('ingame screenshot')} {...imgProps} />
            </div>
          </ZoomableImage>
        </>
      )}
    </div>
  );
};
