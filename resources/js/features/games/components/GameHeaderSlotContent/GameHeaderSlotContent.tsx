import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck, LuMegaphone, LuPlus } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BetaFeedbackDialog } from '@/common/components/BetaFeedbackDialog';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useGameBacklogState } from '@/features/game-list/components/GameListItems/useGameBacklogState';

import { SubsetButtonChip } from '../SubsetButtonChip';

export const GameHeaderSlotContent: FC = () => {
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

      {canSubmitBetaFeedback ? (
        <BetaFeedbackDialog betaName="react-game-page">
          <BaseButton className="flex items-center gap-1.5 rounded-full !py-0 !text-xs" size="sm">
            <LuMegaphone className="size-4" />
            {t('Give Beta Feedback')}
          </BaseButton>
        </BetaFeedbackDialog>
      ) : null}
    </div>
  );
};
