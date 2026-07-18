import type { FC } from 'react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuEye } from 'react-icons/lu';

import { cn } from '@/common/utils/cn';
import { getScreenshotGalleryUrl } from '@/common/utils/getScreenshotGalleryUrl';
import { getScreenshotImageRendering } from '@/common/utils/getScreenshotImageRendering';

// matches max-w-5xl on the gallery container
const MAX_CONTAINER_WIDTH = 1024;

// matches the placeholder's 500ms opacity transition + small buffer
const PLACEHOLDER_HIDE_DELAY_MS = 550;

interface ScreenshotGalleryImageProps {
  hasBeatenGame: boolean;
  registerScrollTarget: (id: number, el: HTMLElement | null) => void;
  screenshot: App.Platform.Data.GameScreenshot;

  hasAnalogTvOutput?: boolean;
  isPixelated?: boolean;
}

export const ScreenshotGalleryImage: FC<ScreenshotGalleryImageProps> = ({
  hasAnalogTvOutput,
  hasBeatenGame,
  isPixelated,
  registerScrollTarget,
  screenshot,
}) => {
  const { t } = useTranslation();

  const [isLoaded, setIsLoaded] = useState(false);
  const [isPlaceholderHidden, setIsPlaceholderHidden] = useState(false);
  const [hasUserRevealed, setHasUserRevealed] = useState(false);

  const hideTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const isCompletion = screenshot.type === 'completion';

  // Players who have already beaten the game have seen the ending.
  // Spoiler protection just adds friction for them, so reveal the image.
  const isRevealed = hasBeatenGame || hasUserRevealed;

  // For pixel art systems, constrain to an integer multiple of
  // the source width so nearest-neighbor produces uniform pixels.
  // Cap at 4x so very low-res sources (eg: Game Boy) don't blow up
  // to an absurd size.
  let integerScaledMaxWidth: number | undefined;
  if (isPixelated && screenshot.width > 0) {
    const maxScale = 4;
    const scale = Math.min(maxScale, Math.floor(MAX_CONTAINER_WIDTH / screenshot.width));

    if (scale >= 1) {
      integerScaledMaxWidth = scale * screenshot.width;
    }
  }

  const aspectRatio = hasAnalogTvOutput ? '4 / 3' : undefined;

  const cancelHideTimer = () => {
    if (hideTimerRef.current !== null) {
      clearTimeout(hideTimerRef.current);
      hideTimerRef.current = null;
    }
  };

  const markLoaded = () => {
    cancelHideTimer();

    hideTimerRef.current = setTimeout(() => {
      hideTimerRef.current = null;
      setIsPlaceholderHidden(true);
    }, PLACEHOLDER_HIDE_DELAY_MS);

    setIsLoaded(true);
  };

  const imageRendering = getScreenshotImageRendering(screenshot.width, isPixelated);

  // Hover preloads can finish before React attaches onLoad, so check `complete` on mount too.
  const handleImageRef = (element: HTMLImageElement | null) => {
    if (element?.complete && element.naturalWidth > 0) {
      markLoaded();
    }
  };

  const handleScrollTargetRef = (el: HTMLDivElement | null) => {
    registerScrollTarget(screenshot.id, el);
  };

  // Cancel any in-flight hide timer if we unmount.
  useEffect(() => cancelHideTimer, []);

  return (
    <div
      ref={handleScrollTargetRef}
      className={cn(
        'pointer-events-auto relative scroll-mt-20 overflow-hidden rounded ring-1 ring-neutral-800',
        integerScaledMaxWidth && 'mx-auto w-full',
      )}
      style={integerScaledMaxWidth ? { maxWidth: integerScaledMaxWidth } : undefined}
    >
      {/* Pixel art originals are tiny enough that the blur layer adds noise without much benefit. */}
      {!isPixelated && !isPlaceholderHidden ? (
        <img
          src={screenshot.placeholderUrl}
          alt=""
          aria-hidden="true"
          className={cn(
            'absolute inset-0 h-full w-full object-cover transition-opacity duration-500 ease-in-out',
            isLoaded ? 'opacity-0' : 'opacity-100',
          )}
          style={{
            filter: 'blur(16px)',
            transform: 'scale(1.1)',
          }}
        />
      ) : null}

      <img
        ref={handleImageRef}
        src={getScreenshotGalleryUrl(screenshot, { isPixelated })}
        alt={isCompletion ? t('Completion screenshot') : ''}
        width={screenshot.width}
        height={screenshot.height}
        loading="lazy"
        decoding="async"
        onLoad={markLoaded}
        className={cn(
          'relative w-full rounded transition-[filter,opacity] duration-500 ease-in-out',
          !isPixelated && !isLoaded && 'opacity-0',
          isCompletion && !isRevealed && 'blur-3xl',
        )}
        style={{
          imageRendering,
          ...(!isPixelated && aspectRatio ? { aspectRatio } : {}),
        }}
      />

      {isCompletion && !isRevealed ? (
        <button
          type="button"
          aria-label={t('Reveal completion screenshot')}
          className={cn(
            'absolute inset-0 flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded',
            'bg-black/60 transition-colors hover:bg-black/70',
          )}
          onClick={() => setHasUserRevealed(true)}
        >
          <span className="text-xs tracking-widest text-neutral-300 uppercase">
            {t('Completion screenshot')}
          </span>

          <span className="flex items-center gap-1.5 text-sm font-semibold text-white">
            <LuEye className="size-4" />
            <span className="sm:hidden">{t('Tap to reveal')}</span>
            <span className="hidden sm:inline">{t('Click to reveal')}</span>
          </span>
        </button>
      ) : null}
    </div>
  );
};
