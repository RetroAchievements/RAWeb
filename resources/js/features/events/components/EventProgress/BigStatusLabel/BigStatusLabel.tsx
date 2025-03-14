import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import { cleanEventAwardLabel } from '../../../utils/cleanEventAwardLabel';

interface BigStatusLabelProps {
  event: App.Platform.Data.Event;
  isMastered: boolean;
}

export const BigStatusLabel: FC<BigStatusLabelProps> = ({ event, isMastered }) => {
  const { t } = useTranslation();

  const eventAwards = event.eventAwards ?? [];

  const isAwarded = isMastered || eventAwards.some((award) => award.earnedAt);

  let colorClassName = 'text-text-muted';
  let statusLabel: string | TranslatedString = t('Unfinished');

  if (isAwarded && eventAwards.length <= 1) {
    colorClassName = 'text-yellow-400 light:text-yellow-600';
    statusLabel = t('Awarded');
  } else if (isAwarded && eventAwards.length > 1) {
    colorClassName = 'text-neutral-300 light:text-neutral-700';

    const highestAwardLabel = eventAwards
      .sort((a, b) => b.pointsRequired - a.pointsRequired)
      .find((award) => award.earnedAt)?.label as string;

    statusLabel = cleanEventAwardLabel(highestAwardLabel, event);
  }

  // If the user has earned every award, always show gold text.
  if (isMastered) {
    colorClassName = 'text-yellow-400 light:text-yellow-600';
  }

  return (
    <div className={cn('mb-1.5 mt-0.5 flex items-center gap-x-1 text-lg', colorClassName)}>
      <p>{statusLabel}</p>
    </div>
  );
};
