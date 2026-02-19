import { type FC, useRef, useState } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

export const GameMobileBannerImage: FC = () => {
  const { banner, game } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const [isImageLoaded, setIsImageLoaded] = useState(false);
  const imageRef = useRef<HTMLImageElement>(null);

  const handleImageRef = (element: HTMLImageElement | null) => {
    imageRef.current = element;

    if (element?.complete) {
      setIsImageLoaded(true);
    }
  };

  const isNintendoDS = game.system?.id === 18;

  return (
    <>
      <div className="absolute inset-0 overflow-hidden">
        {banner?.mobileSmWebp && !banner.isFallback ? (
          <>
            {/*
             * Blurred placeholder - loads instantly.
             * This is critical for LCP / Core Web Vitals.
             *
             * @see Game::registerMediaCollections
             */}
            {banner.mobilePlaceholder ? (
              <img
                src={banner.mobilePlaceholder}
                alt="game banner"
                className={cn(
                  'absolute inset-0 h-full w-full object-cover transition-opacity duration-300 ease-out',
                  isImageLoaded ? 'opacity-0' : 'opacity-100',
                )}
                style={{
                  filter: 'blur(6px)',
                  transform: 'scale(1.1)',
                  willChange: 'opacity', // immediately prepare the GPU for the transition
                }}
                aria-hidden="true"
              />
            ) : null}

            {/* Full-resolution image */}
            <picture
              className={cn(
                'absolute inset-0 transition-opacity duration-300 ease-out',
                isImageLoaded ? 'opacity-100' : 'opacity-0',
              )}
              style={{ willChange: 'opacity' }} // immediately prepare the GPU for the transition
            >
              <source srcSet={banner.mobileSmAvif ?? undefined} type="image/avif" />
              <source srcSet={banner.mobileSmWebp} type="image/webp" />

              {/* Legacy fallback to in-game screenshot */}
              <img
                ref={handleImageRef}
                src={game.imageIngameUrl}
                alt=""
                className="h-full w-full object-cover"
                style={{
                  objectPosition: isNintendoDS ? 'center 0%' : 'center',
                }}
                onLoad={() => setIsImageLoaded(true)}
                fetchPriority="high"
                loading="eager"
                decoding="async"
              />
            </picture>
          </>
        ) : (
          // If the game has no custom banner set, fall back to ImageIngame.
          <img
            src={game.imageIngameUrl}
            alt="game banner"
            fetchPriority="high"
            loading="eager"
            decoding="async"
            className="h-full w-full object-cover"
            style={{
              objectPosition: isNintendoDS ? 'center 0%' : 'center',
              objectFit: isNintendoDS ? 'none' : 'cover',
              scale: isNintendoDS ? '2' : undefined,
            }}
          />
        )}
      </div>

      {/* Background image gradient */}
      <div
        className={cn(
          'absolute inset-0 bg-gradient-to-b from-black/40 from-0% via-black/50 via-60% to-black',
          'light:from-black/20 light:via-black/30 light:to-black/50',
        )}
      />

      {/* Additional darkening behind the badge */}
      <div
        className="bg-radial-gradient absolute -left-10 -top-8 size-40"
        style={{
          background:
            'radial-gradient(circle at center, rgba(0,0,0,0.5) 0%, rgba(0,0,0,0.3) 30%, transparent 70%)',
        }}
      />
    </>
  );
};
