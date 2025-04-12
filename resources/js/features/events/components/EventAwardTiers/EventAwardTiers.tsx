import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { createVirtualAward } from '../../utils/createVirtualAward';
import { AwardTierItem } from './AwardTierItem';

interface EventAwardTiersProps {
  event: App.Platform.Data.Event;
  numMasters: number;
}

export const EventAwardTiers: FC<EventAwardTiersProps> = ({ event, numMasters }) => {
  const { t } = useTranslation();

  if (!event.legacyGame?.badgeUrl || (!event.eventAchievements?.length && numMasters === 0)) {
    return null;
  }

  const eventAwards: (App.Platform.Data.EventAward | null)[] = event.eventAwards?.length
    ? event.eventAwards
    : [createVirtualAward(event, numMasters)];

  // createVirtualAward can return null if we're missing some lazy properties.
  if (eventAwards[0] === null) {
    return null;
  }
  const safeEventAwards = eventAwards as App.Platform.Data.EventAward[];

  // Sort by most points to least points.
  const sortedByPoints = safeEventAwards.sort((a, b) => b.pointsRequired - a.pointsRequired);

  return (
    <div data-testid="award-tiers">
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('Award Tiers')}</h2>

      <div className="rounded-lg bg-embed p-2">
        <div className="flex flex-col gap-3">
          {sortedByPoints.map((eventAward) => (
            <AwardTierItem
              key={eventAward.label}
              event={event}
              eventAward={eventAward}
              hasVirtualTier={!event.eventAwards?.length}
            />
          ))}
        </div>
      </div>
    </div>
  );
};
