import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck, LuFolder, LuFolderLock, LuPlus, LuWrench } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { PlayableSidebarButton } from '@/common/components/PlayableSidebarButton';
import { useGameBacklogState } from '@/common/hooks/useGameBacklogState';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useGameShowTabs } from '@/features/games/hooks/useGameShowTabs';

import { SidebarClaimButtons } from './SidebarClaimButtons';
import { SidebarToggleInReviewButton } from './SidebarToggleInReviewButton';

export const SidebarDevelopmentSection: FC = () => {
  const {
    auth,
    backingGame,
    can,
    game,
    isViewingPublishedAchievements,
    isOnWantToDevList: isInitiallyOnWantToDevList,
  } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const { toggleBacklog: toggleWantToDevelop, isInBacklogMaybeOptimistic: isOnWantToDevList } =
    useGameBacklogState({
      game: backingGame,
      isInitiallyInBacklog: isInitiallyOnWantToDevList,
      userGameListType: 'develop',
    });

  const { setCurrentTab } = useGameShowTabs();

  const isDeveloper = auth?.user.roles.includes('developer');

  // Build the query parameters for toggling between published and unpublished achievements.
  const buildToggleHref = () => {
    const queryParams = { ...route().queryParams };

    if (isViewingPublishedAchievements) {
      // Currently viewing published, switch to unpublished.
      queryParams['unpublished'] = 'true';
    } else {
      // Currently viewing unpublished, remove the filter to view published.
      delete queryParams['unpublished'];
    }

    return route('game2.show', { game: game.id, _query: queryParams });
  };

  const handleTogglePublishedAchievementsClick = () => {
    setCurrentTab('achievements');
    window.scrollTo({
      top: 0,
      behavior: 'instant',
    });
  };

  return (
    <>
      {!isViewingPublishedAchievements || backingGame.achievementsUnpublished ? (
        <PlayableSidebarButton
          IconComponent={isViewingPublishedAchievements ? LuFolderLock : LuFolder}
          href={buildToggleHref()}
          onClick={handleTogglePublishedAchievementsClick}
          isInertiaLink={true}
          showSubsetIndicator={game.id !== backingGame.id}
          count={
            isViewingPublishedAchievements
              ? backingGame.achievementsUnpublished
              : backingGame.achievementsPublished
          }
        >
          {isViewingPublishedAchievements
            ? t('View Unpublished Achievements')
            : t('View Published Achievements')}
        </PlayableSidebarButton>
      ) : null}

      <SidebarClaimButtons />

      {can.manageAchievementSetClaims ? (
        <PlayableSidebarButton
          href={route('game.claims', { game: backingGame.id })}
          IconComponent={LuWrench}
        >
          {t('View Claim History')}
        </PlayableSidebarButton>
      ) : null}

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
