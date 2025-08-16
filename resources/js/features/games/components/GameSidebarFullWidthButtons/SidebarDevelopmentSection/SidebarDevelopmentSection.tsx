import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck, LuPlus } from 'react-icons/lu';

import { PlayableSidebarButton } from '@/common/components/PlayableSidebarButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useGameBacklogState } from '@/features/game-list/components/GameListItems/useGameBacklogState';

import { SidebarClaimButtons } from './SidebarClaimButtons';
import { SidebarToggleInReviewButton } from './SidebarToggleInReviewButton';

export const SidebarDevelopmentSection: FC = () => {
  const {
    auth,
    backingGame,
    game,
    isOnWantToDevList: isInitiallyOnWantToDevList,
  } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const { t } = useTranslation();

  const { toggleBacklog: toggleWantToDevelop, isInBacklogMaybeOptimistic: isOnWantToDevList } =
    useGameBacklogState({
      game: backingGame,
      isInitiallyInBacklog: isInitiallyOnWantToDevList,
      userGameListType: 'develop',
    });

  const isDeveloper = auth?.user.roles.includes('developer');

  return (
    <>
      <SidebarClaimButtons />
      <SidebarToggleInReviewButton />

      {isDeveloper ? (
        <PlayableSidebarButton
          aria-pressed={isOnWantToDevList}
          IconComponent={isOnWantToDevList ? LuCheck : LuPlus}
          onClick={() => toggleWantToDevelop()}
          showSubsetIndicator={game.id !== backingGame.id}
        >
          {backingGame.achievementsPublished ? t('Want to Revise') : t('Want to Develop')}
        </PlayableSidebarButton>
      ) : null}
    </>
  );
};
