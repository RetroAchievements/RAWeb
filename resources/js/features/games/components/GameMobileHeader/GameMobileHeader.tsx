import type { FC } from 'react';

import { GameTitle } from '@/common/components/GameTitle';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { ResponsiveManageChip } from '../ResponsiveManageChip';
import { ResponsiveSystemLinkChip } from '../ResponsiveSystemChip/ResponsiveSystemLinkChip';
import { WantToPlayToggle } from '../WantToPlayToggle';
import { GameMobileBannerImage } from './GameMobileBannerImage';

export const GameMobileHeader: FC = () => {
  const { backingGame, can, game } = usePageProps<App.Platform.Data.GameShowPageProps>();

  return (
    <div
      data-testid="mobile-header"
      className="relative -mx-4 -mt-4 h-[13.25rem] w-[calc(100vw+4px)]"
    >
      <GameMobileBannerImage />

      {/* Content */}
      <div className="flex h-full flex-col gap-3 pb-4 pl-4 pr-3">
        {/* Badge */}
        <img
          loading="eager"
          decoding="sync"
          fetchPriority="high"
          width="80"
          height="80"
          src={backingGame.badgeUrl}
          alt={game.title}
          style={{
            aspectRatio: '1/1',
          }}
          className={cn(
            'z-10 mt-3 rounded-sm bg-neutral-800/60 object-cover',
            'ring-1 ring-white/20 ring-offset-2 ring-offset-black/50',
            'shadow-md shadow-black/50',
            'light:bg-white/50 light:shadow-black/20 light:ring-black/20 light:ring-offset-white/50',
          )}
        />

        <div className="relative flex h-full items-end">
          <div className="flex w-full flex-col gap-1">
            {/* Game title */}
            <h1
              className={cn(
                'font-bold leading-tight text-white [text-shadow:_0_1px_0_rgb(0_0_0),_0_0_12px_rgb(0_0_0)]',
                'light:border-b-0',

                'text-2xl',
                game.title.length > 22 ? '!text-xl' : null,
                game.title.length > 40 ? '!text-base' : null,
                game.title.length > 60 ? 'line-clamp-2 !text-sm' : null,
              )}
            >
              <GameTitle title={game.title} />
            </h1>

            {/* Chip buttons */}
            <div className="flex w-full items-center justify-between gap-2">
              <div className="flex items-center gap-2">
                <ResponsiveSystemLinkChip />
                <WantToPlayToggle variant="sm" />
              </div>

              {can.manageGames ? <ResponsiveManageChip className="h-[28px]" /> : null}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
