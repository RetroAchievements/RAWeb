import type { FC } from 'react';

import { PlayableAchievementDistribution } from '@/common/components/PlayableAchievementDistribution';
import { PlayableBoxArtImage } from '@/common/components/PlayableBoxArtImage';
import { PlayableCompareProgress } from '@/common/components/PlayableCompareProgress';
import { PlayableHubsList } from '@/common/components/PlayableHubsList';
import { usePageProps } from '@/common/hooks/usePageProps';

import { EventAwardTiers } from '../EventAwardTiers';
import { EventProgress } from '../EventProgress';
import { EventSidebarFullWidthButtons } from '../EventSidebarFullWidthButtons';
import { TopEventPlayers } from '../TopEventPlayers';

export const EventShowSidebarRoot: FC = () => {
  const {
    event,
    followedPlayerCompletions,
    hubs,
    numMasters,
    playerAchievementChartBuckets,
    playerGame,
    topAchievers,
  } = usePageProps<App.Platform.Data.EventShowPageProps>();

  return (
    <div data-testid="sidebar" className="flex flex-col gap-6">
      <PlayableBoxArtImage src={event.legacyGame?.imageBoxArtUrl} />
      <EventSidebarFullWidthButtons event={event} />
      <EventProgress event={event} playerGame={playerGame} />
      <EventAwardTiers event={event} numMasters={numMasters} />
      <PlayableHubsList hubs={hubs} />
      <PlayableCompareProgress
        followedPlayerCompletions={followedPlayerCompletions}
        game={event.legacyGame!}
        variant="event"
      />
      <PlayableAchievementDistribution
        buckets={playerAchievementChartBuckets}
        playerGame={playerGame}
        variant="event"
      />
      <TopEventPlayers event={event} numMasters={numMasters} players={topAchievers} />
    </div>
  );
};
