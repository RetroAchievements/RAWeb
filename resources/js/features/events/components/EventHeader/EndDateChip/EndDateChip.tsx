import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuAlarmClockMinus } from 'react-icons/lu';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { formatDate } from '@/common/utils/l10n/formatDate';

dayjs.extend(utc);

interface EndDateChipProps {
  event: App.Platform.Data.Event;
}

export const EndDateChip: FC<EndDateChipProps> = ({ event }) => {
  const { t } = useTranslation();

  // If the event has no end date, bail.
  if (!event.activeThrough) {
    return null;
  }

  const now = dayjs.utc();
  const hasEnded = dayjs.utc(event.activeThrough).isBefore(now);

  const eventEndDate = formatDate(event.activeThrough, 'll');

  return (
    <BaseChip>
      <LuAlarmClockMinus className="size-4" />
      {hasEnded
        ? t('Ended {{eventEndDate}}', { eventEndDate })
        : t('Ends {{eventEndDate}}', { eventEndDate })}
    </BaseChip>
  );
};
