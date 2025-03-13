import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { AwardTierItem } from './AwardTierItem';

interface EventAwardTiersProps {
  event: App.Platform.Data.Event;
}

export const EventAwardTiers: FC<EventAwardTiersProps> = ({ event }) => {
  const { t } = useTranslation();

  if (!event.eventAwards?.length || event.eventAwards.length === 1) {
    return null;
  }

  // Sort by most points to least points.
  const sortedByPoints = event.eventAwards.sort((a, b) => b.pointsRequired - a.pointsRequired);

  return (
    <div data-testid="award-tiers">
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('Award Tiers')}</h2>

      <div className="rounded-lg bg-embed p-2">
        <div className="flex flex-col gap-3">
          {sortedByPoints.map((eventAward) => (
            <AwardTierItem key={eventAward.label} event={event} eventAward={eventAward} />
          ))}
        </div>
      </div>
    </div>
  );
};
