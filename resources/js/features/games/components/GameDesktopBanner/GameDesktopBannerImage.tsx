import { type FC, useRef, useState } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { buildEasedGradient } from './buildEasedGradient';

interface GameDesktopBannerImageProps {
  banner: App.Platform.Data.PageBanner;
}

export const GameDesktopBannerImage: FC<GameDesktopBannerImageProps> = ({ banner }) => {
  const { game } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const [isImageLoaded, setIsImageLoaded] = useState(false);
  const imageRef = useRef<HTMLImageElement>(null);

  const handleImageRef = (element: HTMLImageElement | null) => {
    imageRef.current = element;

    if (element?.complete) {
      setIsImageLoaded(true);
    }
  };

  return (
    <>
      {/* Sharp image layer. */}
      <div className="absolute inset-0 overflow-hidden">
        {/* Full-resolution image with responsive srcset. */}
        <picture
          className={cn('absolute inset-0', isImageLoaded ? 'opacity-100' : 'opacity-0')}
          style={{
            transition: 'opacity 1s cubic-bezier(0.77, 0, 0.175, 1)',
            willChange: 'opacity',
          }}
        >
          {/* Mobile-optimized images for small viewports. */}
          <source
            type="image/avif"
            media="(max-width: 767px)"
            srcSet={banner.mobileSmAvif ?? undefined}
          />
          <source
            type="image/webp"
            media="(max-width: 767px)"
            srcSet={banner.mobileSmWebp ?? undefined}
          />

          {/* Desktop images for larger viewports. */}
          <source
            type="image/avif"
            srcSet={[
              banner.desktopMdAvif && `${banner.desktopMdAvif} 1024w`,
              banner.desktopLgAvif && `${banner.desktopLgAvif} 1280w`,
              banner.desktopXlAvif && `${banner.desktopXlAvif} 1920w`,
            ]
              .filter(Boolean)
              .join(', ')}
            sizes="100vw"
          />
          <source
            type="image/webp"
            srcSet={[
              banner.desktopMdWebp && `${banner.desktopMdWebp} 1024w`,
              banner.desktopLgWebp && `${banner.desktopLgWebp} 1280w`,
              banner.desktopXlWebp && `${banner.desktopXlWebp} 1920w`,
            ]
              .filter(Boolean)
              .join(', ')}
            sizes="100vw"
          />

          {/* Legacy fallback to in-game screenshot. This should _never_ happen. */}
          <img
            ref={handleImageRef}
            src={game.imageIngameUrl}
            alt=""
            className="h-full w-full object-cover object-center lg:object-[50%_10%]"
            onLoad={() => setIsImageLoaded(true)}
            fetchPriority="high"
            loading="eager"
            decoding="async"
          />
        </picture>
      </div>

      {/* Top gradient for navbar blending. */}
      <div
        data-testid="top-gradient-dark"
        className="absolute inset-0 hidden light:hidden md:block"
        style={{ background: buildEasedGradient('to bottom', 'black', 0.6) }}
      />
      <div
        data-testid="top-gradient-light"
        className="absolute inset-0 hidden light:md:block"
        style={{ background: buildEasedGradient('to bottom', 'white', 0.7) }}
      />

      {/* Bottom gradient for game info text readability */}
      <div
        data-testid="bottom-gradient-dark"
        className="absolute inset-0 hidden light:hidden md:block"
        style={{ background: buildEasedGradient('to top', 'black', 0.6) }}
      />
      <div
        data-testid="bottom-gradient-light"
        className="absolute inset-0 hidden light:md:block"
        style={{ background: buildEasedGradient('to top', 'white', 0.8) }}
      />
    </>
  );
};
