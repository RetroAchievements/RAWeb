import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { AchievementDistribution } from '../AchievementDistribution';
import { BoxArtImage } from '../BoxArtImage';
import { CompareProgress } from '../CompareProgress';
import { EventAwardTiers } from '../EventAwardTiers';
import { EventProgress } from '../EventProgress';
import { EventSidebarFullWidthButtons } from '../EventSidebarFullWidthButtons';
import { HubsList } from '../HubsList';
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
  } = usePageProps<App.Platform.Data.EventShowPagePropsData>();

  return (
    <div data-testid="sidebar" className="flex flex-col gap-6">
      <BoxArtImage event={event} />
      <EventSidebarFullWidthButtons event={event} />
      <EventProgress event={event} playerGame={playerGame} />
      <EventAwardTiers event={event} numMasters={numMasters} />
      <HubsList hubs={hubs} />
      <CompareProgress
        followedPlayerCompletions={followedPlayerCompletions}
        game={event.legacyGame!}
      />
      <AchievementDistribution buckets={playerAchievementChartBuckets} playerGame={playerGame} />
      <TopEventPlayers event={event} numMasters={numMasters} players={topAchievers} />
    </div>
  );
};
