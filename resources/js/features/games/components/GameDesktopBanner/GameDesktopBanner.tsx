import type { FC } from 'react';
import { route } from 'ziggy-js';

import { GameTitle } from '@/common/components/GameTitle';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { WantToPlayToggle } from '../WantToPlayToggle';
import { GameDesktopBannerImage } from './GameDesktopBannerImage';

interface GameDesktopBannerProps {
  banner: App.Platform.Data.PageBanner;
}

export const GameDesktopBanner: FC<GameDesktopBannerProps> = ({ banner }) => {
  const { backingGame, game } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const isViewingSubset = game.id !== backingGame.id;

  const leftColor = banner.leftEdgeColor ?? '#0a0a0a';
  const rightColor = banner.rightEdgeColor ?? '#0a0a0a';

  return (
    // Outer container: full viewport width with an edge color gradient for ultrawide displays.
    <div
      data-testid="desktop-banner"
      className={cn(
        'relative z-0',
        '-mt-[44px] h-[344px]',
        'border-b border-neutral-700',
        'ml-[calc(50%-50vw)] w-screen',
      )}
      style={{
        background: `linear-gradient(to right, ${leftColor} 0%, ${leftColor} 30%, ${rightColor} 70%, ${rightColor} 100%)`,
      }}
    >
      {/* Layer 1: dark gradient overlay to mute edge colors */}
      <div
        className="absolute inset-0 z-[1]"
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
      <div className="absolute inset-x-0 bottom-0 z-10 mx-auto max-w-screen-xl px-4 pb-[46px] transition-[padding] sm:px-5 md:px-6 xl:px-0">
        <div className="flex items-end gap-4">
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
              'size-24 rounded bg-neutral-950/50 object-cover',
              'ring-1 ring-white/20 ring-offset-2 ring-offset-black/50',
              'shadow-xl shadow-black/50',
              'light:bg-white/50 light:shadow-black/20 light:ring-black/20 light:ring-offset-white/50',
            )}
          />

          <div className="flex flex-col gap-2">
            {/* Game title */}
            <h1
              className={cn(
                'w-fit font-bold leading-tight text-white',
                '[text-shadow:_0_2px_8px_rgb(0_0_0_/_80%),_0_0_2px_rgb(0_0_0)]',
                'text-3xl',
                game.title.length > 30 ? '!text-2xl' : null,
                game.title.length > 50 ? '!text-xl' : null,
              )}
            >
              <GameTitle title={game.title} />
            </h1>

            {/* System chip link and action buttons */}
            <div className="flex items-center gap-2">
              {/* System chip (breadcrumb) */}
              <a
                href={route('system.game.index', { system: game.system!.id })}
                className={cn(
                  'flex items-center gap-1.5 rounded-full',
                  'border border-white/20 bg-black/70 px-3 py-1.5',
                  'shadow-md backdrop-blur-sm',
                  'hover:border-link-hover hover:bg-black/80',
                  'light:border-neutral-300 light:bg-white/80 light:backdrop-blur-md',
                  'light:hover:bg-white/90',
                )}
              >
                <img
                  src={game.system?.iconUrl}
                  alt={game.system?.nameShort}
                  width={18}
                  height={18}
                />

                <span className="text-sm font-medium">{game.system?.name}</span>
              </a>

              <WantToPlayToggle
                className="h-[35px] border-white/20 lg:transition-transform lg:duration-100 lg:active:translate-y-[1px] lg:active:scale-[0.98]"
                showSubsetIndicator={isViewingSubset}
                variant="base"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
