import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseTabs,
  BaseTabsContent,
  BaseTabsList,
  BaseTabsTrigger,
} from '@/common/components/+vendor/BaseTabs';
import { MatureContentWarningDialog } from '@/common/components/MatureContentWarningDialog';
import { PlayableAchievementDistribution } from '@/common/components/PlayableAchievementDistribution';
import { PlayableBoxArtImage } from '@/common/components/PlayableBoxArtImage';
import { PlayableCompareProgress } from '@/common/components/PlayableCompareProgress';
import { PlayableHubsList } from '@/common/components/PlayableHubsList';
import { PlayableMainMedia } from '@/common/components/PlayableMainMedia';
import { PlayableTopPlayers } from '@/common/components/PlayableTopPlayers';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useAllMetaRowElements } from '../../hooks/useAllMetaRowElements';
import { useGameShowTabs } from '../../hooks/useGameShowTabs';
import type { GameShowTab } from '../../models';
import { getAllPageAchievements } from '../../utils/getAllPageAchievements';
import { getSidebarExcludedHubIds } from '../../utils/getSidebarExcludedHubIds';
import { AchievementSetEmptyState } from '../AchievementSetEmptyState';
import { GameAchievementSetsContainer } from '../GameAchievementSetsContainer';
import { GameCommentList } from '../GameCommentList';
import { GameContentWarnings } from '../GameContentWarnings';
import { GameMetadata } from '../GameMetadata';
import { GameMobileHeader } from '../GameMobileHeader';
import { GameRecentPlayers } from '../GameRecentPlayers';
import { GameSidebarFullWidthButtons } from '../GameSidebarFullWidthButtons';
import { PlaytimeStatistics } from '../PlaytimeStatistics';
import { ResetAllProgressDialog } from '../ResetAllProgressDialog';
import { ScrollToTopButton } from '../ScrollToTopButton';
import { SeriesHubDisplay } from '../SeriesHubDisplay';
import { SimilarGamesList } from '../SimilarGamesList';

export const GameShowMobileRoot: FC = () => {
  const {
    backingGame,
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
      {allPageAchievements.length ? <ResetAllProgressDialog /> : null}

      <GameMobileHeader />

      <BaseTabs value={currentTab} onValueChange={(value) => setCurrentTab(value as GameShowTab)}>
        {/* Tabs list */}
        <div className="-mx-2.5 -mt-3 overflow-x-auto">
          <BaseTabsList className="mb-3 flex w-max min-w-full justify-between rounded-none border-b border-neutral-600 bg-embed py-0 light:bg-white light:pt-1">
            <BaseTabsTrigger value="achievements" variant="underlined">
              {t('Achievement Set')}
            </BaseTabsTrigger>

            <BaseTabsTrigger value="info" variant="underlined">
              {t('Info')}
            </BaseTabsTrigger>

            {isViewingPublishedAchievements && game.playersTotal ? (
              <BaseTabsTrigger value="stats" variant="underlined">
                {t('Stats')}
              </BaseTabsTrigger>
            ) : null}

            {isViewingPublishedAchievements ? (
              <BaseTabsTrigger value="community" variant="underlined">
                {t('Community')}
              </BaseTabsTrigger>
            ) : null}
          </BaseTabsList>
        </div>

        {/* Achievement set tab content */}
        <BaseTabsContent
          value="achievements"
          forceMount={true} // takes too long to unmount and remount on tab change
          className="data-[state=inactive]:hidden"
        >
          <GameAchievementSetsContainer game={game} />
          {!allPageAchievements.length ? <AchievementSetEmptyState /> : null}
        </BaseTabsContent>

        {/* Info tab content */}
        <BaseTabsContent value="info" className="flex flex-col gap-8">
          <div className="-mx-2.5 -my-4 flex flex-col gap-6 p-4">
            <PlayableBoxArtImage src={game.imageBoxArtUrl} />

            <PlayableMainMedia
              imageIngameUrl={game.imageIngameUrl!}
              imageTitleUrl={game.imageTitleUrl!}
            />
          </div>

          <div className="flex flex-col gap-3">
            <GameContentWarnings />
            <GameMetadata allMetaRowElements={allMetaRowElements} game={game} />
          </div>

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
            variant="game"
          />
        </BaseTabsContent>

        {/* Stats tab content */}
        {isViewingPublishedAchievements && game.playersTotal ? (
          <BaseTabsContent value="stats" className="flex flex-col gap-8">
            {allPageAchievements.length ? <PlaytimeStatistics /> : null}

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
              backingGame={backingGame}
              game={game}
              numMasters={numMasters}
              players={topAchievers}
              variant="game"
            />
          </BaseTabsContent>
        ) : null}

        {/* Community tab content */}
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
