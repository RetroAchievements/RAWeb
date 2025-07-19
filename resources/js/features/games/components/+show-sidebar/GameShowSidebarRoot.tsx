import type { FC } from 'react';

import { BaseSeparator } from '@/common/components/+vendor/BaseSeparator';
import { PlayableAchievementDistribution } from '@/common/components/PlayableAchievementDistribution';
import { PlayableBoxArtImage } from '@/common/components/PlayableBoxArtImage';
import { PlayableCompareProgress } from '@/common/components/PlayableCompareProgress';
import { PlayableHubsList } from '@/common/components/PlayableHubsList';
import { PlayableTopPlayers } from '@/common/components/PlayableTopPlayers';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useAllMetaRowElements } from '../../hooks/useAllMetaRowElements';
import { getSidebarExcludedHubIds } from '../../utils/getSidebarExcludedHubIds';
import { GameMetadata } from '../GameMetadata';
import { GameSidebarFullWidthButtons } from '../GameSidebarFullWidthButtons';
import { MatureContentIndicator } from '../MatureContentIndicator';
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
    topAchievers,
  } = usePageProps<App.Platform.Data.GameShowPageProps>();

  let coreAchievements: App.Platform.Data.Achievement[] = [];
  const coreSet = game.gameAchievementSets?.find((s) => s.type === 'core');
  if (coreSet) {
    coreAchievements = coreSet.achievementSet.achievements;
  }

  const allMetaRowElements = useAllMetaRowElements(game, hubs);

  return (
    <div data-testid="sidebar" className="flex flex-col gap-6">
      <PlayableBoxArtImage src={game.imageBoxArtUrl} />

      {hasMatureContent ? <MatureContentIndicator /> : null}

      <GameMetadata allMetaRowElements={allMetaRowElements} game={game} hubs={hubs} />
      <GameSidebarFullWidthButtons game={game} />

      <BaseSeparator className="mb-4" />

      {seriesHub ? <SeriesHubDisplay seriesHub={seriesHub} /> : null}

      <SimilarGamesList similarGames={similarGames} />
      <PlayableHubsList
        hubs={hubs}
        excludeHubIds={getSidebarExcludedHubIds(hubs, seriesHub, allMetaRowElements.allUsedHubIds)}
      />
      <PlayableCompareProgress
        followedPlayerCompletions={followedPlayerCompletions}
        game={game}
        variant="game"
      />
      <PlayableAchievementDistribution
        buckets={playerAchievementChartBuckets}
        playerGame={playerGame}
        variant="game"
      />
      <PlayableTopPlayers
        achievements={coreAchievements}
        game={game}
        numMasters={numMasters}
        players={topAchievers}
        variant="game"
      />
    </div>
  );
};
