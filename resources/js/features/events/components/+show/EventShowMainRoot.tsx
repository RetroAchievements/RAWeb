import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { PlayableHeader } from '@/common/components/PlayableHeader';
import { PlayableMainMedia } from '@/common/components/PlayableMainMedia';
import { PlayableMobileMediaCarousel } from '@/common/components/PlayableMobileMediaCarousel';
import { usePageProps } from '@/common/hooks/usePageProps';

import { EventAchievementSetContainer } from '../EventAchievementSetContainer';
import { EventBreadcrumbs } from '../EventBreadcrumbs';
import { EndDateChip } from '../EventHeader/EndDateChip';
import { IsPlayableChip } from '../EventHeader/IsPlayableChip';
import { StartDateChip } from '../EventHeader/StartDateChip';

export const EventShowMainRoot: FC = () => {
  const { event } = usePageProps<App.Platform.Data.EventShowPageProps>();

  const { t } = useTranslation();

  const { legacyGame } = event;

  if (!legacyGame?.badgeUrl) {
    return null;
  }

  return (
    <div data-testid="main" className="flex flex-col gap-3">
      <EventBreadcrumbs event={event} />
      <PlayableHeader
        badgeUrl={legacyGame.badgeUrl}
        systemLabel={t('Event')}
        systemIconUrl="/assets/images/system/events.png"
        title={legacyGame.title}
      >
        <IsPlayableChip event={event} />
        <StartDateChip event={event} />
        <EndDateChip event={event} />
      </PlayableHeader>

      <div className="mt-2 hidden sm:block">
        <PlayableMainMedia
          imageIngameUrl={legacyGame.imageIngameUrl!}
          imageTitleUrl={legacyGame.imageTitleUrl!}
        />
      </div>

      <div className="-mx-3 sm:hidden">
        <PlayableMobileMediaCarousel
          imageIngameUrl={legacyGame.imageIngameUrl!}
          imageTitleUrl={legacyGame.imageTitleUrl!}
        />
      </div>

      <EventAchievementSetContainer event={event} />
    </div>
  );
};
