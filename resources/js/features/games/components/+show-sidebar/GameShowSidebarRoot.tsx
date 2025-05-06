import type { FC } from 'react';

import { PlayableAchievementDistribution } from '@/common/components/PlayableAchievementDistribution';
import { PlayableBoxArtImage } from '@/common/components/PlayableBoxArtImage';
import { PlayableCompareProgress } from '@/common/components/PlayableCompareProgress';
import { PlayableHubsList } from '@/common/components/PlayableHubsList';
import { PlayableTopPlayers } from '@/common/components/PlayableTopPlayers';
import { usePageProps } from '@/common/hooks/usePageProps';

import { GameSidebarFullWidthButtons } from '../GameSidebarFullWidthButtons';

export const GameShowSidebarRoot: FC = () => {
  const {
    followedPlayerCompletions,
    game,
    hubs,
    playerAchievementChartBuckets,
    playerGame,
    numMasters,
    topAchievers,
  } = usePageProps<App.Platform.Data.GameShowPageProps>();

  let coreAchievements: App.Platform.Data.Achievement[] = [];
  const coreSet = game.gameAchievementSets?.find((s) => s.type === 'core');
  if (coreSet) {
    coreAchievements = coreSet.achievementSet.achievements;
  }

  return (
    <div data-testid="sidebar" className="flex flex-col gap-6">
      <PlayableBoxArtImage src={game.imageBoxArtUrl} />
      <GameSidebarFullWidthButtons game={game} />
      <PlayableHubsList hubs={hubs} />
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
