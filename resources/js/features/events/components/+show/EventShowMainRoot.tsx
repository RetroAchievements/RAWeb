import type { FC } from 'react';

import { ManageButton } from '@/common/components/ManageButton';
import { usePageProps } from '@/common/hooks/usePageProps';

import { EventAchievementSets } from '../EventAchievementSets';
import { EventBreadcrumbs } from '../EventBreadcrumbs';
import { EventHeader } from '../EventHeader';
import { EventMainMedia } from '../EventMainMedia';
import { EventMobileMediaCarousel } from '../EventMobileMediaCarousel';

export const EventShowMainRoot: FC = () => {
  const { can, event } = usePageProps<App.Platform.Data.EventShowPagePropsData>();

  const { legacyGame } = event;

  if (!legacyGame) {
    return null;
  }

  return (
    <div data-testid="main" className="flex flex-col gap-3">
      <EventBreadcrumbs event={event} />
      <EventHeader event={event} />

      <div className="mt-2 hidden sm:block">
        <EventMainMedia
          imageIngameUrl={legacyGame.imageIngameUrl!}
          imageTitleUrl={legacyGame.imageTitleUrl!}
        />
      </div>

      <div className="-mx-3 sm:hidden">
        <EventMobileMediaCarousel
          imageIngameUrl={legacyGame.imageIngameUrl!}
          imageTitleUrl={legacyGame.imageTitleUrl!}
        />
      </div>

      {can.manageEvents ? (
        <ManageButton href={`/manage/events/${event.id}`} className="max-w-fit" />
      ) : null}

      <EventAchievementSets event={event} />
    </div>
  );
};
