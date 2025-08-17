import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck, LuFolder, LuFolderLock, LuPlus } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { PlayableSidebarButton } from '@/common/components/PlayableSidebarButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useGameBacklogState } from '@/features/game-list/components/GameListItems/useGameBacklogState';

export const SidebarDevelopmentSection: FC = () => {
  const {
    backingGame,
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

  // TODO const isDeveloper = auth?.user.roles.includes('developer');

  // Build the query parameters for toggling between published and unpublished achievements.
  const buildToggleHref = () => {
    const queryParams: Record<string, any> = { ...route().queryParams };

    if (isViewingPublishedAchievements) {
      // Currently viewing published, switch to unpublished.
      queryParams['unpublished'] = 'true';
    } else {
      // Currently viewing unpublished, remove the filter to view published.
      delete queryParams['unpublished'];
    }

    return route('game2.show', { game: game.id, _query: queryParams });
  };

  return (
    <>
      {/* TODO hide this to jr devs when more buttons are added to this section */}
      <PlayableSidebarButton
        aria-pressed={isOnWantToDevList}
        IconComponent={isOnWantToDevList ? LuCheck : LuPlus}
        onClick={() => toggleWantToDevelop()}
        showSubsetIndicator={game.id !== backingGame.id}
      >
        {backingGame.achievementsPublished ? t('Want to Revise') : t('Want to Develop')}
      </PlayableSidebarButton>

      {!isViewingPublishedAchievements || backingGame.achievementsUnpublished ? (
        <PlayableSidebarButton
          IconComponent={isViewingPublishedAchievements ? LuFolderLock : LuFolder}
          href={buildToggleHref()}
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
    </>
  );
};
