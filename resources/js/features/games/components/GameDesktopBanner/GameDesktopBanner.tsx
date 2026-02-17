import { type FC, useState } from 'react';

import { GameTitle } from '@/common/components/GameTitle';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { useBannerPreference } from '../../hooks/useBannerPreference';
import { ResponsiveManageChip } from '../ResponsiveManageChip';
import { ResponsiveSystemLinkChip } from '../ResponsiveSystemChip';
import { WantToPlayToggle } from '../WantToPlayToggle';
import { GameDesktopBannerImage } from './GameDesktopBannerImage';

interface GameDesktopBannerProps {
  banner?: App.Platform.Data.PageBanner | null;
}

export const GameDesktopBanner: FC<GameDesktopBannerProps> = ({ banner }) => {
  const { backingGame, can, game } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const { bannerPreference, cycleBannerPreference } = useBannerPreference();

  const [isDividerHovered, setIsDividerHovered] = useState(false);

  const isViewingSubset = game.id !== backingGame.id;

  const hasCustomBanner = !!banner?.desktopMdWebp;

  const leftColor = banner?.leftEdgeColor ?? '#0a0a0a';
  const rightColor = banner?.rightEdgeColor ?? '#0a0a0a';

  return (
    // Outer container: full viewport width with an edge color gradient for ultrawide displays.
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
        background: hasCustomBanner
          ? `linear-gradient(to right, ${leftColor} 0%, ${leftColor} 30%, ${rightColor} 70%, ${rightColor} 100%)`
          : '#0a0a0a',
      }}
    >
      {/*
        Layer 1: For custom banners, use a dark gradient overlay to mute edge colors.
                 For the fallback, use a color-extracted blurred screenshot.
      */}
      {hasCustomBanner ? (
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
      ) : (
        <>
          <div className="absolute inset-0">
            <img
              src={game.imageIngameUrl}
              alt=""
              aria-hidden="true"
              className="h-full w-full object-cover"
              style={{
                filter: 'blur(80px) saturate(1.8)',
                transform: 'scale(4)',
              }}
              data-testid="fallback-color-source"
            />
          </div>

          <div className="absolute inset-0 bg-black/35 light:bg-black/25" />
          <div className="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent" />
        </>
      )}

      {/* Layer 1b: noise texture to reduce gradient banding */}
      <div
        className="pointer-events-none absolute inset-0 z-[2] hidden md:block"
        style={{
          backgroundImage: `url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E")`,
          opacity: 0.15,
          mixBlendMode: 'overlay',
        }}
      />

      {/* Layer 2: blurred image backdrop (only for custom banners) */}
      {hasCustomBanner ? (
        <div className="absolute inset-0 overflow-hidden">
          <div className="relative mx-auto h-full max-w-[1920px]">
            <img
              src={banner.desktopMdWebp!}
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

      {/* Layer 3: full-res image constrained to max-width with edge shadows (only for custom banners) */}
      {hasCustomBanner ? (
        <div
          className="relative mx-auto h-full max-w-[1920px] overflow-hidden"
          style={{
            boxShadow: '0 0 40px 0px rgba(0, 0, 0, 0.5)',
          }}
        >
          <GameDesktopBannerImage banner={banner} />
        </div>
      ) : null}

      {/* Layer 4: game info and associated controls */}
      <div
        className={cn(
          'absolute inset-x-0 bottom-0 z-[19] mx-auto max-w-screen-xl px-3 pb-4 transition-[padding]',
          'sm:px-4 md:px-6 md:pb-[46px] xl:px-0',
        )}
      >
        <div className="flex w-full flex-col gap-5 sm:gap-4 md:flex-row md:items-end">
          {/* Game badge. */}
          <img
            loading="eager"
            decoding="sync"
            fetchPriority="high"
            width="96"
            height="96"
            src={backingGame.badgeUrl}
            alt={game.title}
            className={cn(
              'size-20 rounded bg-neutral-950/50 object-cover md:size-24',
              'ring-1 ring-white/20 ring-offset-2 ring-offset-black/50',
              'shadow-md shadow-black/50 md:shadow-xl',
              'light:bg-white/50 light:shadow-black/20 light:ring-black/20 light:ring-offset-white/50',
            )}
          />

          <div className="flex w-full flex-col gap-1 md:gap-2">
            {/* Game title */}
            <h1
              className={cn(
                'w-fit font-bold leading-tight text-white',
                '[text-shadow:_0_1px_2px_rgb(0_0_0),_0_2px_6px_rgb(0_0_0_/_80%),_0_0_14px_rgb(0_0_0_/_60%)]',
                'text-2xl md:text-3xl',
                game.title.length > 26 ? '!text-xl' : null,
                game.title.length > 30 ? '!text-base md:!text-2xl' : null,
                game.title.length > 50 ? 'line-clamp-2 !text-sm md:!text-xl' : null,
              )}
            >
              <GameTitle title={game.title} />
            </h1>

            {/* System chip link and action buttons */}
            <div className="flex w-full justify-between gap-2">
              <div className="flex items-center gap-2">
                <ResponsiveSystemLinkChip />

                {/* XS */}
                <WantToPlayToggle className="sm:hidden" variant="sm" />

                {/* SM+ */}
                <WantToPlayToggle
                  className="hidden border-white/20 sm:flex sm:h-[35px] lg:transition-transform lg:duration-100 lg:active:translate-y-[1px] lg:active:scale-[0.98]"
                  showSubsetIndicator={isViewingSubset}
                  variant="base"
                />
              </div>

              {can.manageGames ? <ResponsiveManageChip /> : null}
            </div>
          </div>
        </div>
      </div>

      {/* Layer 5: mobile-only gradient overlay for text readability */}
      <div
        className={cn(
          'absolute inset-0 md:hidden',
          'bg-gradient-to-b from-black/40 from-0% via-black/50 via-60% to-black',
          'light:from-black/20 light:via-black/30 light:to-black/50',
        )}
      />

      {/* Layer 6: invisible hit area to toggle compact mode. only functional on LG+ with custom banners. */}
      {hasCustomBanner ? (
        <button
          onClick={cycleBannerPreference}
          onMouseEnter={() => setIsDividerHovered(true)}
          onMouseLeave={() => setIsDividerHovered(false)}
          aria-label={bannerPreference === 'expanded' ? 'Collapse banner' : 'Expand banner'}
          className="absolute inset-x-0 -bottom-2 z-10 hidden h-5 cursor-ns-resize lg:block"
          tabIndex={-1}
        />
      ) : null}
    </div>
  );
};
