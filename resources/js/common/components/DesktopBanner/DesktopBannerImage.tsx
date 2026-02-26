import { type FC, useRef, useState } from 'react';

import { cn } from '@/common/utils/cn';

import { buildEasedGradient } from './buildEasedGradient';

interface DesktopBannerImageProps {
  banner: App.Platform.Data.PageBanner;
}

export const DesktopBannerImage: FC<DesktopBannerImageProps> = ({ banner }) => {
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
      {/* The sharp image fades in once loaded, replacing the blurred backdrop */}
      <div className="absolute inset-0 overflow-hidden">
        <picture
          className={cn('absolute inset-0', isImageLoaded ? 'opacity-100' : 'opacity-0')}
          style={{
            transition: 'opacity 1s cubic-bezier(0.77, 0, 0.175, 1)',
            willChange: 'opacity',
          }}
        >
          {/* Smaller viewports get a smaller crop to save bandwidth */}
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

          {/* Desktop viewports use responsive srcSet so the browser picks the best resolution */}
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

          <img
            ref={handleImageRef}
            src={banner.desktopMdWebp ?? undefined}
            alt=""
            className="h-full w-full object-cover object-center lg:object-[50%_10%]"
            onLoad={() => setIsImageLoaded(true)}
            fetchPriority="high"
            loading="eager"
            decoding="async"
          />
        </picture>
      </div>

      {/* These gradients ensure the navbar and content text remain readable over any image */}
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

      {/* Bottom gradients protect the game title and action chips from low-contrast images */}
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
