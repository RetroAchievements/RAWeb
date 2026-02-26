import { type FC, useState } from 'react';

import { DesktopBanner } from '@/common/components/DesktopBanner';
import { GameTitle } from '@/common/components/GameTitle';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { useBannerPreference } from '../../hooks/useBannerPreference';
import { ResponsiveManageChip } from '../ResponsiveManageChip';
import { ResponsiveSystemLinkChip } from '../ResponsiveSystemChip';
import { WantToPlayToggle } from '../WantToPlayToggle';

interface GameDesktopBannerProps {
  banner?: App.Platform.Data.PageBanner | null;
}

export const GameDesktopBanner: FC<GameDesktopBannerProps> = ({ banner }) => {
  const { backingGame, can, game } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const { bannerPreference, cycleBannerPreference } = useBannerPreference();

  const [isDividerHovered, setIsDividerHovered] = useState(false);

  const isViewingSubset = game.id !== backingGame.id;

  // Fallback banners shouldn't expand/collapse since they're generic.
  const hasCustomBanner = !!banner?.desktopMdWebp && !banner?.isFallback;

  return (
    <DesktopBanner
      banner={banner}
      hasCustomBanner={hasCustomBanner}
      bannerPreference={bannerPreference}
      isDividerHovered={isDividerHovered}
    >
      {/* Positioned at the bottom of the banner so it layers over the image. */}
      <div
        className={cn(
          'absolute inset-x-0 bottom-0 z-[19] mx-auto max-w-screen-xl px-3 pb-4 transition-[padding]',
          'sm:px-4 md:px-6 md:pb-[46px] xl:px-0',
        )}
      >
        <div className="flex w-full flex-col gap-5 sm:gap-4 md:flex-row md:items-end">
          {/* Game badge */}
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

      {/* Invisible hit area to toggle banner height on LG+ with custom banners */}
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
    </DesktopBanner>
  );
};
