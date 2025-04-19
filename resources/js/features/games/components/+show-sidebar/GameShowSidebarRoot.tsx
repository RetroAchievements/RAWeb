import type { FC } from 'react';

import { PlayableAchievementDistribution } from '@/common/components/PlayableAchievementDistribution';
import { PlayableBoxArtImage } from '@/common/components/PlayableBoxArtImage';
import { PlayableCompareProgress } from '@/common/components/PlayableCompareProgress';
import { PlayableHubsList } from '@/common/components/PlayableHubsList';
import { usePageProps } from '@/common/hooks/usePageProps';

export const GameShowSidebarRoot: FC = () => {
  const { followedPlayerCompletions, game, hubs, playerAchievementChartBuckets, playerGame } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  return (
    <div data-testid="sidebar" className="flex flex-col gap-6">
      <PlayableBoxArtImage src={game.imageBoxArtUrl} />
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
    </div>
  );
};
