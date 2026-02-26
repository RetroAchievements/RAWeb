import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuAlarmClockCheck } from 'react-icons/lu';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { useFormatDate } from '@/common/hooks/useFormatDate';

dayjs.extend(utc);

interface StartDateChipProps {
  event: App.Platform.Data.Event;

  className?: string;
}

export const StartDateChip: FC<StartDateChipProps> = ({ event, className }) => {
  const { t } = useTranslation();
  const { formatDate } = useFormatDate();

  if (!event.activeFrom) {
    return null;
  }

  const now = dayjs.utc();
  const hasStarted = dayjs.utc(event.activeFrom).isBefore(now);

  const eventStartDate = formatDate(event.activeFrom, 'll');

  return (
    <BaseChip className={className}>
      <LuAlarmClockCheck className="size-4" />
      {hasStarted
        ? t('Started {{eventStartDate}}', { eventStartDate })
        : t('Starts {{eventStartDate}}', { eventStartDate })}
    </BaseChip>
  );
};
