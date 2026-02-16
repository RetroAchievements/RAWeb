import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuAlarmClockMinus } from 'react-icons/lu';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { useFormatDate } from '@/common/hooks/useFormatDate';

dayjs.extend(utc);

interface EndDateChipProps {
  event: App.Platform.Data.Event;

  className?: string;
}

export const EndDateChip: FC<EndDateChipProps> = ({ event, className }) => {
  const { t } = useTranslation();
  const { formatDate } = useFormatDate();

  if (!event.activeThrough) {
    return null;
  }

  const now = dayjs.utc();
  const hasEnded = dayjs.utc(event.activeThrough).endOf('day').isBefore(now);

  const eventEndDate = formatDate(event.activeThrough, 'll');

  return (
    <BaseChip className={className}>
      <LuAlarmClockMinus className="size-4" />
      {hasEnded
        ? t('Ended {{eventEndDate}}', { eventEndDate })
        : t('Ends {{eventEndDate}}', { eventEndDate })}
    </BaseChip>
  );
};
