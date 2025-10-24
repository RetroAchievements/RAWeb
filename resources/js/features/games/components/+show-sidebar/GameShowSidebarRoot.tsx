import type { FC } from 'react';

import { BaseSeparator } from '@/common/components/+vendor/BaseSeparator';
import { PlayableAchievementDistribution } from '@/common/components/PlayableAchievementDistribution';
import { PlayableBoxArtImage } from '@/common/components/PlayableBoxArtImage';
import { PlayableCompareProgress } from '@/common/components/PlayableCompareProgress';
import { PlayableHubsList } from '@/common/components/PlayableHubsList';
import { PlayableTopPlayers } from '@/common/components/PlayableTopPlayers';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useAllMetaRowElements } from '../../hooks/useAllMetaRowElements';
import { getAllPageAchievements } from '../../utils/getAllPageAchievements';
import { getSidebarExcludedHubIds } from '../../utils/getSidebarExcludedHubIds';
import { FeaturedLeaderboardsList } from '../FeaturedLeaderboardsList';
import { GameContentWarnings } from '../GameContentWarnings';
import { GameMetadata } from '../GameMetadata';
import { GameSidebarFullWidthButtons } from '../GameSidebarFullWidthButtons';
import { PlaytimeStatistics } from '../PlaytimeStatistics';
import { SeriesHubDisplay } from '../SeriesHubDisplay';
import { SimilarGamesList } from '../SimilarGamesList';

export const GameShowSidebarRoot: FC = () => {
  const {
    backingGame,
    featuredLeaderboards,
    followedPlayerCompletions,
    game,
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

  const allMetaRowElements = useAllMetaRowElements(game, hubs);

  const achievements = getAllPageAchievements(game.gameAchievementSets!, targetAchievementSetId);

  return (
    <div data-testid="sidebar" className="flex flex-col gap-6">
      <PlayableBoxArtImage src={game.imageBoxArtUrl} />

      <div className="flex flex-col gap-3">
        <GameContentWarnings />
        <GameMetadata allMetaRowElements={allMetaRowElements} game={game} />
      </div>

      <GameSidebarFullWidthButtons game={game} />

      <BaseSeparator className="mb-4" />

      {isViewingPublishedAchievements && game.playersTotal && achievements.length ? (
        <PlaytimeStatistics />
      ) : null}

      {seriesHub ? <SeriesHubDisplay seriesHub={seriesHub} /> : null}

      <SimilarGamesList similarGames={similarGames} />
      <PlayableHubsList
        hubs={hubs}
        excludeHubIds={getSidebarExcludedHubIds(hubs, seriesHub, allMetaRowElements.allUsedHubIds)}
        variant="game"
      />

      {isViewingPublishedAchievements && achievements.length ? (
        <PlayableCompareProgress
          followedPlayerCompletions={followedPlayerCompletions}
          game={backingGame} // the prop is named `game` because this component is reusable in multiple contexts (ie: Events)
          variant="game"
        />
      ) : null}

      {isViewingPublishedAchievements ? (
        <PlayableAchievementDistribution
          buckets={playerAchievementChartBuckets}
          playerGame={playerGame}
          variant="game"
        />
      ) : null}

      {isViewingPublishedAchievements ? (
        <PlayableTopPlayers
          achievements={achievements}
          backingGame={backingGame}
          game={game}
          numMasters={numMasters}
          players={topAchievers}
          variant="game"
        />
      ) : null}

      {featuredLeaderboards?.length ? (
        <FeaturedLeaderboardsList featuredLeaderboards={featuredLeaderboards} />
      ) : null}
    </div>
  );
};
