import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { BoxArtImage } from '../BoxArtImage';
import { EventAwardTiers } from '../EventAwardTiers';
import { EventProgress } from '../EventProgress';
import { HubsList } from '../HubsList';
import { OfficialForumTopicButton } from '../OfficialForumTopicButton';

export const EventShowSidebarRoot: FC = () => {
  const { event, hubs, playerGame } = usePageProps<App.Platform.Data.EventShowPagePropsData>();

  return (
    <div data-testid="sidebar" className="flex flex-col gap-6">
      <BoxArtImage event={event} />
      <OfficialForumTopicButton event={event} />
      <EventProgress event={event} playerGame={playerGame} />
      <EventAwardTiers event={event} />
      <HubsList hubs={hubs} />
    </div>
  );
};
