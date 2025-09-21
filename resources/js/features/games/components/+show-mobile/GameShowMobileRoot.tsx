import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseScrollArea } from '@/common/components/+vendor/BaseScrollArea';
import {
  BaseTabs,
  BaseTabsContent,
  BaseTabsList,
  BaseTabsTrigger,
} from '@/common/components/+vendor/BaseTabs';
import { MatureContentWarningDialog } from '@/common/components/MatureContentWarningDialog';
import { PlayableAchievementDistribution } from '@/common/components/PlayableAchievementDistribution';
import { PlayableCompareProgress } from '@/common/components/PlayableCompareProgress';
import { PlayableHubsList } from '@/common/components/PlayableHubsList';
import { PlayableTopPlayers } from '@/common/components/PlayableTopPlayers';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useAllMetaRowElements } from '../../hooks/useAllMetaRowElements';
import { useGameShowTabs } from '../../hooks/useGameShowTabs';
import { getAllPageAchievements } from '../../utils/getAllPageAchievements';
import { getSidebarExcludedHubIds } from '../../utils/getSidebarExcludedHubIds';
import { AchievementSetEmptyState } from '../AchievementSetEmptyState';
import { GameAchievementSetsContainer } from '../GameAchievementSetsContainer';
import { GameCommentList } from '../GameCommentList';
import { GameMetadata } from '../GameMetadata';
import { GameMobileHeader } from '../GameMobileHeader';
import { GameRecentPlayers } from '../GameRecentPlayers';
import { GameSidebarFullWidthButtons } from '../GameSidebarFullWidthButtons';
import { MatureContentIndicator } from '../MatureContentIndicator';
import { PlaytimeStatistics } from '../PlaytimeStatistics';
import { ResetAllProgressAlertDialog } from '../ResetAllProgressAlertDialog';
import { ScrollToTopButton } from '../ScrollToTopButton';
import { SeriesHubDisplay } from '../SeriesHubDisplay';
import { SimilarGamesList } from '../SimilarGamesList';

export const GameShowMobileRoot: FC = () => {
  const {
    followedPlayerCompletions,
    game,
    hasMatureContent,
    hubs,
    isViewingPublishedAchievements,
    numMasters,
    playerAchievementChartBuckets,
    playerGame,
    seriesHub,
    similarGames,
    targetAchievementSetId,
    topAchievers,
  } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const allMetaRowElements = useAllMetaRowElements(game, hubs);

  const { currentTab, setCurrentTab } = useGameShowTabs();

  if (!game.badgeUrl || !game.system?.iconUrl) {
    return null;
  }

  const allPageAchievements = getAllPageAchievements(
    game.gameAchievementSets!,
    targetAchievementSetId,
  );

  return (
    <div data-testid="game-mobile" className="flex flex-col gap-3">
      {currentTab === 'achievements' ? <ScrollToTopButton /> : null}

      {hasMatureContent ? <MatureContentWarningDialog /> : null}
      {allPageAchievements.length ? <ResetAllProgressAlertDialog /> : null}

      <GameMobileHeader />

      <BaseTabs value={currentTab} onValueChange={setCurrentTab}>
        {/* Tabs list */}
        <BaseScrollArea className="-mx-2.5 -mt-3">
          <BaseTabsList className="mb-3 flex justify-around rounded-none border-b border-neutral-600 bg-embed py-0">
            <BaseTabsTrigger value="achievements" variant="underlined">
              {t('Achievements')}
            </BaseTabsTrigger>

            <BaseTabsTrigger value="info" variant="underlined">
              {t('Info')}
            </BaseTabsTrigger>

            <BaseTabsTrigger value="stats" variant="underlined">
              {t('Stats')}
            </BaseTabsTrigger>

            {isViewingPublishedAchievements ? (
              <BaseTabsTrigger value="community" variant="underlined">
                {t('Community')}
              </BaseTabsTrigger>
            ) : null}
          </BaseTabsList>
        </BaseScrollArea>

        {/* Tabs content */}
        <BaseTabsContent
          value="achievements"
          forceMount={true} // takes too long to unmount and remount on tab change
          className="data-[state=inactive]:hidden"
        >
          <GameAchievementSetsContainer game={game} />
          {!allPageAchievements.length ? <AchievementSetEmptyState /> : null}
        </BaseTabsContent>

        <BaseTabsContent value="info" className="flex flex-col gap-8">
          {hasMatureContent ? <MatureContentIndicator /> : null}

          <GameMetadata allMetaRowElements={allMetaRowElements} game={game} hubs={hubs} />

          <GameSidebarFullWidthButtons game={game} />

          {seriesHub ? <SeriesHubDisplay seriesHub={seriesHub} /> : null}

          <SimilarGamesList similarGames={similarGames} />
          <PlayableHubsList
            hubs={hubs}
            excludeHubIds={getSidebarExcludedHubIds(
              hubs,
              seriesHub,
              allMetaRowElements.allUsedHubIds,
            )}
          />
        </BaseTabsContent>

        {isViewingPublishedAchievements ? (
          <BaseTabsContent value="stats" className="flex flex-col gap-8">
            {game.playersTotal && allPageAchievements.length ? <PlaytimeStatistics /> : null}

            {allPageAchievements.length ? (
              <PlayableCompareProgress
                followedPlayerCompletions={followedPlayerCompletions}
                game={game}
                variant="game"
              />
            ) : null}

            <PlayableAchievementDistribution
              buckets={playerAchievementChartBuckets}
              playerGame={playerGame}
              variant="game"
            />

            <PlayableTopPlayers
              achievements={allPageAchievements}
              game={game}
              numMasters={numMasters}
              players={topAchievers}
              variant="game"
            />
          </BaseTabsContent>
        ) : null}

        {isViewingPublishedAchievements ? (
          <BaseTabsContent value="community" className="mt-0 flex flex-col gap-8">
            <GameRecentPlayers />
            <GameCommentList />
          </BaseTabsContent>
        ) : null}
      </BaseTabs>
    </div>
  );
};
