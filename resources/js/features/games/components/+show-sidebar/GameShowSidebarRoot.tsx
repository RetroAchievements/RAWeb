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
import { GameMetadata } from '../GameMetadata';
import { GameSidebarFullWidthButtons } from '../GameSidebarFullWidthButtons';
import { MatureContentIndicator } from '../MatureContentIndicator';
import { PlaytimeStatistics } from '../PlaytimeStatistics';
import { SeriesHubDisplay } from '../SeriesHubDisplay';
import { SimilarGamesList } from '../SimilarGamesList';

export const GameShowSidebarRoot: FC = () => {
  const {
    followedPlayerCompletions,
    game,
    hasMatureContent,
    hubs,
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

      {hasMatureContent ? <MatureContentIndicator /> : null}

      <GameMetadata allMetaRowElements={allMetaRowElements} game={game} hubs={hubs} />
      <GameSidebarFullWidthButtons game={game} />

      <BaseSeparator className="mb-4" />

      {game.playersTotal ? <PlaytimeStatistics /> : null}

      {seriesHub ? <SeriesHubDisplay seriesHub={seriesHub} /> : null}

      <SimilarGamesList similarGames={similarGames} />
      <PlayableHubsList
        hubs={hubs}
        excludeHubIds={getSidebarExcludedHubIds(hubs, seriesHub, allMetaRowElements.allUsedHubIds)}
      />

      {achievements.length ? (
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
        achievements={achievements}
        game={game}
        numMasters={numMasters}
        players={topAchievers}
        variant="game"
      />
    </div>
  );
};
