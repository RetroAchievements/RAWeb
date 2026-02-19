import type { FC, ReactNode } from 'react';

import { cn } from '@/common/utils/cn';

import { DesktopBannerImage } from './DesktopBannerImage';

interface DesktopBannerProps {
  children: ReactNode;

  banner?: App.Platform.Data.PageBanner | null;

  /**
   * Only per-game custom banners get the taller height and expand/collapse.
   * The fallback banner and event banners always use the compact height.
   */
  hasCustomBanner?: boolean;

  bannerPreference?: string;
  isDividerHovered?: boolean;
}

export const DesktopBanner: FC<DesktopBannerProps> = ({
  banner,
  bannerPreference,
  children,
  hasCustomBanner = false,
  isDividerHovered = false,
}) => {
  const leftColor = banner?.leftEdgeColor ?? '#0a0a0a';
  const rightColor = banner?.rightEdgeColor ?? '#0a0a0a';

  return (
    <div
      data-testid="desktop-banner"
      className={cn(
        'relative overflow-hidden',
        'h-[13.25rem] md:-mt-[44px]',
        'border-b border-neutral-700',
        'transition-[height,border-color] duration-200',
        'ml-[calc(50%-50vw)] w-screen',

        // Only allow height variations when there's a custom banner.
        hasCustomBanner ? 'lg:h-[344px]' : 'lg:h-[212px]',
        hasCustomBanner && bannerPreference === 'compact' ? 'lg:!h-[212px]' : null,
        hasCustomBanner && bannerPreference === 'expanded' ? 'lg:!h-[474px]' : null,
        hasCustomBanner && isDividerHovered ? 'border-neutral-500' : null,
      )}
      style={{
        background: `linear-gradient(to right, ${leftColor} 0%, ${leftColor} 30%, ${rightColor} 70%, ${rightColor} 100%)`,
      }}
    >
      {/* Dark gradient overlay mutes the edge colors so text remains readable */}
      <div
        className="absolute inset-0 z-[1] hidden md:block"
        style={{
          background: `linear-gradient(to bottom,
            transparent 0%,
            rgba(0,0,0,0.01) 10%,
            rgba(0,0,0,0.03) 20%,
            rgba(0,0,0,0.06) 30%,
            rgba(0,0,0,0.1) 40%,
            rgba(0,0,0,0.16) 50%,
            rgba(0,0,0,0.24) 60%,
            rgba(0,0,0,0.35) 70%,
            rgba(0,0,0,0.46) 80%,
            rgba(0,0,0,0.56) 90%,
            rgba(0,0,0,0.65) 100%
          )`,
        }}
      />

      {/* Noise texture breaks up smooth gradients that would otherwise show visible banding */}
      <div
        className="pointer-events-none absolute inset-0 z-[2] hidden md:block"
        style={{
          backgroundImage: `url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E")`,
          opacity: 0.15,
          mixBlendMode: 'overlay',
        }}
      />

      {/* Blurred image backdrop fills the viewport while the sharp image loads */}
      {banner?.desktopMdWebp ? (
        <div className="absolute inset-0 overflow-hidden">
          <div className="relative mx-auto h-full max-w-[1920px]">
            <img
              src={banner.desktopMdWebp}
              alt=""
              className="absolute inset-0 h-full w-full object-cover object-center lg:object-[50%_10%]"
              style={{
                filter: 'blur(15px)',
              }}
              aria-hidden="true"
              data-testid="blurred-backdrop"
            />
          </div>
        </div>
      ) : null}

      {/* Full-res banner image constrained to max-width with edge shadows for depth */}
      {banner?.desktopMdWebp ? (
        <div
          className="relative mx-auto h-full max-w-[1920px] overflow-hidden"
          style={{
            boxShadow: '0 0 40px 0px rgba(0, 0, 0, 0.5)',
          }}
        >
          <DesktopBannerImage banner={banner} />
        </div>
      ) : null}

      {/* Content overlay sits above all visual layers (game info, etc) */}
      {children}

      <div
        className={cn(
          'absolute inset-0 md:hidden',
          'bg-gradient-to-b from-black/40 from-0% via-black/50 via-60% to-black',
          'light:from-black/20 light:via-black/30 light:to-black/50',
        )}
      />
    </div>
  );
};
