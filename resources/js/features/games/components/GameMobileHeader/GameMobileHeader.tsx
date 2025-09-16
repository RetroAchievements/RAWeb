import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck, LuMegaphone, LuPlus } from 'react-icons/lu';

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
        backgroundSize: isNintendoDS ? '93% auto' : 'cover',
        backgroundPosition: isNintendoDS ? 'center 0%' : 'center',
      }}
      className="relative -mx-4 -mt-4 h-48 w-[calc(100vw+4px)]"
    >
      <div className="absolute inset-0 bg-gradient-to-b from-black/40 from-0% via-black/50 via-60% to-black" />
      <div className="relative flex h-full items-end px-4 pb-4">
        <div className="flex w-full flex-col gap-2">
          <h1
            className={cn(
              'font-bold leading-tight text-white [text-shadow:_0_1px_0_rgb(0_0_0),_0_0_12px_rgb(0_0_0)]',
              game.title.length > 22 ? 'text-xl' : 'text-2xl',
            )}
          >
            {game.title}
          </h1>

          <div className="flex items-center gap-2">
            <div
              className={cn(
                'flex max-w-fit items-center gap-1 rounded-full',
                'border border-white/30 bg-black/70 px-2.5 py-1 shadow-md backdrop-blur-sm',
              )}
            >
              <img src={game.system?.iconUrl} alt={game.system?.nameShort} width={16} height={16} />
              <span className="text-xs font-medium text-white">{game.system?.nameShort}</span>
            </div>

            <button
              onClick={() => toggleWantToPlay()}
              className={cn(
                'flex items-center gap-1 rounded-full',
                'border border-white/30 bg-black/70 px-2.5 py-1 shadow-md backdrop-blur-sm transition-all hover:bg-black/80',
              )}
              aria-pressed={isOnWantToPlayList}
            >
              <div className="relative size-3.5">
                <LuPlus
                  className={cn(
                    'absolute inset-0 size-3.5 text-link transition-all duration-200',
                    isOnWantToPlayList
                      ? 'rotate-45 scale-75 opacity-0'
                      : 'rotate-0 scale-100 opacity-100',
                  )}
                />
                <LuCheck
                  className={cn(
                    'absolute inset-0 size-3.5 text-green-400 transition-all duration-200',
                    isOnWantToPlayList
                      ? 'rotate-0 scale-100 opacity-100'
                      : '-rotate-45 scale-75 opacity-0',
                  )}
                />
              </div>
              <span className="text-xs font-medium text-link">{t('game_wantToPlayToggle')}</span>
            </button>

            {canSubmitBetaFeedback ? (
              <BetaFeedbackDialog betaName="react-game-page">
                <button
                  className={cn(
                    'text-link',
                    'flex items-center gap-1 rounded-full',
                    'border border-white/30 bg-black/70 px-2.5 py-1 shadow-md backdrop-blur-sm transition-all hover:bg-black/80',
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
  );
};
