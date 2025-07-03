import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck, LuPlus } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useGameBacklogState } from '@/features/game-list/components/GameListItems/useGameBacklogState';

export const GameHeaderSlotContent: FC = () => {
  const { game, isOnWantToPlayList: isInitiallyOnWantToPlayList } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  const { t } = useTranslation();

  const { toggleBacklog: toggleWantToPlay, isInBacklogMaybeOptimistic: isOnWantToPlayList } =
    useGameBacklogState({
      game,
      isInitiallyInBacklog: isInitiallyOnWantToPlayList,
      userGameListType: 'play',
    });

  return (
    <div className="flex items-center gap-2">
      <BaseButton
        onClick={() => toggleWantToPlay()}
        className="flex items-center gap-1 rounded-full !py-0 !text-xs"
        size="sm"
        aria-pressed={isOnWantToPlayList}
      >
        {isOnWantToPlayList ? <LuCheck className="size-4" /> : <LuPlus className="size-4" />}
        {t('game_wantToPlayToggle')}
      </BaseButton>
    </div>
  );
};
