import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck, LuPlus } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useGameBacklogState } from '@/features/game-list/components/GameListItems/useGameBacklogState';

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
          {isOnWantToPlayList ? <LuCheck className="size-4" /> : <LuPlus className="size-4" />}
          {t('game_wantToPlayToggle')}
        </div>

        {game.id !== backingGame.id ? <SubsetButtonChip className="-mr-1" /> : null}
      </BaseButton>
    </div>
  );
};
