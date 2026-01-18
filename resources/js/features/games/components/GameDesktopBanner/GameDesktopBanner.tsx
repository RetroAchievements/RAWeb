import { type FC, useState } from 'react';

import { GameTitle } from '@/common/components/GameTitle';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { useCompactBannerPreference } from '../../hooks/useCompactBannerPreference';
import { ResponsiveSystemLinkChip } from '../ResponsiveSystemChip';
import { WantToPlayToggle } from '../WantToPlayToggle';
import { GameDesktopBannerImage } from './GameDesktopBannerImage';

interface GameDesktopBannerProps {
  banner: App.Platform.Data.PageBanner;
}

export const GameDesktopBanner: FC<GameDesktopBannerProps> = ({ banner }) => {
  const { backingGame, game } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const { prefersCompactBanners, toggleCompactBanners } = useCompactBannerPreference();

  const [isDividerHovered, setIsDividerHovered] = useState(false);

  const isViewingSubset = game.id !== backingGame.id;

  const leftColor = banner.leftEdgeColor ?? '#0a0a0a';
  const rightColor = banner.rightEdgeColor ?? '#0a0a0a';

  return (
    // Outer container: full viewport width with an edge color gradient for ultrawide displays.
    <div
      data-testid="desktop-banner"
      className={cn(
        'relative',
        'h-[13.25rem] md:-mt-[44px] lg:h-[344px]',
        'border-b border-neutral-700',
        'transition-[height,border-color] duration-200',
        'ml-[calc(50%-50vw)] w-screen',

        prefersCompactBanners ? 'lg:h-[212px]' : null,
        isDividerHovered ? 'border-neutral-500' : null,
      )}
      style={{
        background: `linear-gradient(to right, ${leftColor} 0%, ${leftColor} 30%, ${rightColor} 70%, ${rightColor} 100%)`,
      }}
    >
      {/* Layer 1: dark gradient overlay to mute edge colors */}
      <div
        className="absolute inset-0 z-[1] hidden md:block"
        style={{
          background: 'linear-gradient(to bottom, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.65) 100%)',
        }}
      />

      {/* Layer 2: blurred MD image that fills the full viewport width */}
      <div className="absolute inset-0 overflow-hidden">
        <picture className="absolute inset-0">
          <source srcSet={banner.desktopMdAvif ?? undefined} type="image/avif" />
          <source srcSet={banner.desktopMdWebp ?? undefined} type="image/webp" />
          <img
            src={game.imageIngameUrl}
            alt=""
            className="h-full w-full object-cover object-[50%_10%]"
            style={{
              filter: 'blur(15px)',
            }}
            aria-hidden="true"
            data-testid="blurred-backdrop"
          />
        </picture>
      </div>

      {/* Layer 3: full-res image constrained to max-width with edge shadows */}
      <div
        className="relative mx-auto h-full max-w-[1920px] overflow-hidden"
        style={{
          boxShadow: '0 0 40px 0px rgba(0, 0, 0, 0.5)',
        }}
      >
        <GameDesktopBannerImage banner={banner} />
      </div>

      {/* Layer 4: game info and associated controls */}
      <div
        className={cn(
          'absolute inset-x-0 bottom-0 z-[19] mx-auto max-w-screen-xl px-3 pb-4 transition-[padding]',
          'sm:px-4 md:px-6 md:pb-[46px] xl:px-0',
        )}
      >
        <div className="flex flex-col gap-5 sm:gap-4 md:flex-row md:items-end">
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

          <div className="flex flex-col gap-1 md:gap-2">
            {/* Game title */}
            <h1
              className={cn(
                'w-fit font-bold leading-tight text-white',
                '[text-shadow:_0_2px_8px_rgb(0_0_0_/_80%),_0_0_2px_rgb(0_0_0)]',
                'text-2xl md:text-3xl',
                game.title.length > 22 ? '!text-xl' : null,
                game.title.length > 30 ? '!text-base md:!text-2xl' : null,
                game.title.length > 50 ? 'line-clamp-2 !text-sm md:!text-xl' : null,
              )}
            >
              <GameTitle title={game.title} />
            </h1>

            {/* System chip link and action buttons */}
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
          </div>
        </div>
      </div>

      <div
        className={cn(
          'md:hidden',
          'absolute inset-0 bg-gradient-to-b from-black/40 from-0% via-black/50 via-60% to-black',
          'light:from-black/20 light:via-black/30 light:to-black/50',
        )}
      />

      {/* Layer 5: invisible hit area to toggle compact mode. only functional on LG+. */}
      <button
        onClick={toggleCompactBanners}
        onMouseEnter={() => setIsDividerHovered(true)}
        onMouseLeave={() => setIsDividerHovered(false)}
        aria-label={prefersCompactBanners ? 'Expand banner' : 'Collapse banner'}
        className="absolute inset-x-0 -bottom-2 z-10 hidden h-5 cursor-ns-resize lg:block"
        tabIndex={-1}
      />
    </div>
  );
};
