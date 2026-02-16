import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

import { createVirtualAward } from '../../utils/createVirtualAward';
import { AwardTierItem } from './AwardTierItem';
import { PreferredTierButton } from './PreferredTierButton';

interface EventAwardTiersProps {
  event: App.Platform.Data.Event;
  numMasters: number;
}

export const EventAwardTiers: FC<EventAwardTiersProps> = ({ event, numMasters }) => {
  const { earnedEventAwardTier } = usePageProps<App.Platform.Data.EventShowPageProps>();
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
      <div className="flex items-center justify-between">
        <h2 className="mb-0 border-0 text-lg font-semibold">{t('Award Tiers')}</h2>

        <PreferredTierButton />
      </div>

      <div className="rounded-lg bg-embed p-2 light:border light:border-neutral-200 light:bg-white">
        <div className="flex flex-col gap-3">
          {sortedByPoints.map((eventAward) => (
            <AwardTierItem
              key={eventAward.label}
              event={event}
              eventAward={eventAward}
              hasVirtualTier={!event.eventAwards?.length}
              isEarned={
                earnedEventAwardTier !== null && eventAward.tierIndex <= earnedEventAwardTier
              }
            />
          ))}
        </div>
      </div>
    </div>
  );
};
