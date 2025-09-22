import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck, LuMegaphone, LuPlus } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { BetaFeedbackDialog } from '@/common/components/BetaFeedbackDialog';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { useGameBacklogState } from '@/features/game-list/components/GameListItems/useGameBacklogState';

export const GameMobileHeader: FC = () => {
  const {
    backingGame,
    canSubmitBetaFeedback,
    game,
    isOnWantToPlayList: isInitiallyOnWantToPlayList,
  } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const { toggleBacklog: toggleWantToPlay, isInBacklogMaybeOptimistic: isOnWantToPlayList } =
    useGameBacklogState({
      game: backingGame,
      isInitiallyInBacklog: isInitiallyOnWantToPlayList,
      userGameListType: 'play',
    });

  const isNintendoDS = game.system?.id === 18;

  return (
    <div
      data-testid="mobile-header"
      style={{
        backgroundImage: `url(${game.imageIngameUrl})`,

        // For Nintendo DS, zoom in 2x and show only the top portion.
        backgroundSize: isNintendoDS ? '100% auto' : 'cover',
        backgroundPosition: isNintendoDS ? 'center 0%' : 'center',
      }}
      className="relative -mx-4 -mt-4 h-[13.25rem] w-[calc(100vw+4px)]"
    >
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

      {/* Content */}
      <div className="flex h-full flex-col gap-3 px-4 pb-4">
        {/* Badge */}
        <img
          loading="eager"
          decoding="sync"
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
              {game.title}
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

              {/* Want to Play toggle */}
              <button
                onClick={() => toggleWantToPlay()}
                className={cn(
                  'flex items-center gap-1 whitespace-nowrap rounded-full',
                  'border border-white/30 bg-black/70 px-2.5 py-1 shadow-md backdrop-blur-sm transition-all hover:bg-black/80',
                  'light:border-neutral-300 light:bg-white/80 light:backdrop-blur-md light:hover:bg-white/90',
                )}
                aria-pressed={isOnWantToPlayList}
              >
                <div className="relative size-3.5">
                  <LuPlus
                    className={cn(
                      'absolute inset-0 size-3.5 text-link transition-all duration-200',
                      'light:text-neutral-700',
                      isOnWantToPlayList
                        ? 'rotate-45 scale-75 opacity-0'
                        : 'rotate-0 scale-100 opacity-100',
                    )}
                  />
                  <LuCheck
                    className={cn(
                      'absolute inset-0 size-3.5 text-green-400 transition-all duration-200',
                      'light:text-green-700',
                      isOnWantToPlayList
                        ? 'rotate-0 scale-100 opacity-100'
                        : '-rotate-45 scale-75 opacity-0',
                    )}
                  />
                </div>

                <span className="text-xs font-medium text-link light:text-neutral-700">
                  {t('game_wantToPlayToggle')}
                </span>
              </button>

              {/* Give beta feedback */}
              {canSubmitBetaFeedback ? (
                <BetaFeedbackDialog betaName="react-game-page">
                  <button
                    className={cn(
                      'whitespace-nowrap text-link light:text-neutral-700',
                      'flex items-center gap-1 rounded-full',
                      'border border-white/30 bg-black/70 px-2.5 py-1 shadow-md backdrop-blur-sm transition-all hover:bg-black/80',
                      'light:border-neutral-300 light:bg-white/80 light:backdrop-blur-md light:hover:bg-white/90',
                    )}
                  >
                    <LuMegaphone className="size-3.5" />
                    {t('Give Feedback')}
                  </button>
                </BetaFeedbackDialog>
              ) : null}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
