import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { cn } from '@/common/utils/cn';

import { ZoomableImage } from '../ZoomableImage';

/**
 * TODO replace legacy PNG sources with <picture> sourcesets
 * using the sm/md WebP/AVIF conversions from the screenshots
 * MediaLibrary collection.
 */

interface PlayableMainMediaProps {
  imageIngameUrl: string;
  imageTitleUrl: string;

  /** Set width/height on img tags to reserve space and prevent layout shift. */
  expectedHeight?: number | null;
  expectedWidth?: number | null;
  hasAnalogTvOutput?: boolean;
  isPixelated?: boolean;
}

export const PlayableMainMedia: FC<PlayableMainMediaProps> = ({
  expectedHeight,
  expectedWidth,
  hasAnalogTvOutput,
  imageIngameUrl,
  imageTitleUrl,
  isPixelated,
}) => {
  const { t } = useTranslation();

  // If both images are the "No Screenshot Found" default, display nothing.
  if (imageTitleUrl.includes('000002') && imageIngameUrl.includes('000002')) {
    return null;
  }

  const dimensionProps =
    expectedWidth && expectedHeight ? { width: expectedWidth, height: expectedHeight } : {};

  // CRT systems displayed non-square pixels stretched to 4:3. The aspect
  // ratio is always 4:3 regardless of the native pixel resolution.
  const aspectRatio = hasAnalogTvOutput ? 4 / 3 : undefined;
  const aspectRatioStyle = aspectRatio ? { aspectRatio } : undefined;

  return (
    <div
      className={cn(
        'flex w-full items-center justify-around',
        'border border-embed-highlight bg-zinc-900/50 light:bg-neutral-50',
        'gap-x-5 gap-y-1',
        'xl:mx-0 xl:min-h-[180px] xl:w-full xl:rounded-lg xl:px-4 xl:py-2',
      )}
    >
      <ZoomableImage
        src={imageTitleUrl}
        alt={t('title screenshot')}
        aspectRatio={aspectRatio}
        isPixelated={isPixelated}
      >
        <div className="flex items-center justify-center overflow-hidden">
          <img
            className="w-full rounded-sm"
            src={imageTitleUrl}
            alt={t('title screenshot')}
            style={aspectRatioStyle}
            {...dimensionProps}
          />
        </div>
      </ZoomableImage>

      <ZoomableImage
        src={imageIngameUrl}
        alt={t('ingame screenshot')}
        aspectRatio={aspectRatio}
        isPixelated={isPixelated}
      >
        <div className="flex items-center justify-center overflow-hidden">
          <img
            className="w-full rounded-sm"
            src={imageIngameUrl}
            alt={t('ingame screenshot')}
            style={aspectRatioStyle}
            {...dimensionProps}
          />
        </div>
      </ZoomableImage>
    </div>
  );
};
