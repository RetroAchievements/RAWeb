import type { FC } from 'react';
import { route } from 'ziggy-js';

import { GameTitle } from '@/common/components/GameTitle';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { WantToPlayToggle } from '../WantToPlayToggle';
import { GameMobileBannerImage } from './GameMobileBannerImage';

export const GameMobileHeader: FC = () => {
  const { backingGame, game } = usePageProps<App.Platform.Data.GameShowPageProps>();

  return (
    <div
      data-testid="mobile-header"
      className="relative -mx-4 -mt-4 h-[13.25rem] w-[calc(100vw+4px)]"
    >
      <GameMobileBannerImage />

      {/* Content */}
      <div className="flex h-full flex-col gap-3 px-4 pb-4">
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
          className="z-10 mt-3 rounded-sm bg-neutral-800/60 object-cover p-px outline outline-1 outline-white/20"
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
            <div className="flex items-center gap-2">
              {/* System name */}
              <a
                href={route('system.game.index', { system: game.system!.id })}
                className={cn(
                  'flex max-w-fit items-center gap-1 rounded-full',
                  'border border-white/30 bg-black/70 px-2.5 py-1 shadow-md backdrop-blur-sm',
                  'light:border-neutral-300 light:bg-white/80 light:backdrop-blur-md',
                )}
              >
                <img
                  src={game.system?.iconUrl}
                  alt={game.system?.nameShort}
                  width={16}
                  height={16}
                />
                <span className="text-xs font-medium">{game.system?.nameShort}</span>
              </a>

              <WantToPlayToggle variant="sm" />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
