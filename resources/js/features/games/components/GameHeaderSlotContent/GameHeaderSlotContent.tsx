import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck, LuPlus } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { useGameBacklogState } from '@/common/hooks/useGameBacklogState';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { SubsetButtonChip } from '../SubsetButtonChip';

export const GameHeaderSlotContent: FC = () => {
  const {
    backingGame,
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

  return (
    <div className="flex items-center gap-2">
      <BaseButton
        onClick={() => toggleWantToPlay()}
        className="flex items-center gap-1.5 rounded-full !py-0 !text-xs"
        size="sm"
        aria-pressed={isOnWantToPlayList}
      >
        <div className="flex items-center gap-1">
          <div className="relative size-4">
            <LuPlus
              className={cn(
                'absolute inset-0 size-4 transition-[transform,opacity] duration-200',
                isOnWantToPlayList
                  ? 'rotate-45 scale-75 opacity-0'
                  : 'rotate-0 scale-100 opacity-100',
              )}
            />
            <LuCheck
              className={cn(
                'absolute inset-0 size-4 text-green-400 transition-[transform,opacity] duration-200',
                'light:text-green-700',
                isOnWantToPlayList
                  ? 'rotate-0 scale-100 opacity-100'
                  : '-rotate-45 scale-75 opacity-0',
              )}
            />
          </div>
          {t('game_wantToPlayToggle')}
        </div>

        {game.id !== backingGame.id ? <SubsetButtonChip className="-mr-1" /> : null}
      </BaseButton>
    </div>
  );
};
