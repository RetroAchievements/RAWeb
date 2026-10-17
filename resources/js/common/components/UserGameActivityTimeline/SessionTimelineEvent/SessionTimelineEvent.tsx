import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuTicket } from 'react-icons/lu';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { useFormatDate } from '@/common/hooks/useFormatDate';

import { ResetEventContent } from './ResetEventContent';
import { RichPresenceEventContent } from './RichPresenceEventContent';
import { UnlockEventContent } from './UnlockEventContent';

dayjs.extend(utc);

interface SessionTimelineEventProps {
  hasReportedMarker?: boolean;
  isPreviousGrouped: boolean;
  previousEventTimestamp: string | null;
  previousEventKind: App.Enums.PlayerGameActivityEventType | 'start-session';
  sessionEvent: App.Platform.Data.PlayerGameActivityEvent;
  sessionType: App.Enums.PlayerGameActivitySessionType;
}

export const SessionTimelineEvent: FC<SessionTimelineEventProps> = ({
  hasReportedMarker,
  isPreviousGrouped,
  previousEventKind,
  previousEventTimestamp,
  sessionEvent,
  sessionType,
}) => {
  const { t } = useTranslation();
  const { formatDate } = useFormatDate();

  const eventTime = formatDate(dayjs.utc(sessionEvent.when), 'LTS');

  return (
    <div className="flex flex-col gap-1 md:flex-row md:items-center md:gap-5">
      <p className="min-w-22 light:text-neutral-600">{isPreviousGrouped ? '' : eventTime}</p>

      {sessionEvent.type === 'unlock' && sessionEvent.achievement ? (
        <UnlockEventContent
          previousEventKind={previousEventKind}
          sessionEvent={sessionEvent}
          sessionType={sessionType}
          whenPrevious={previousEventTimestamp}
        />
      ) : null}

      {hasReportedMarker ? (
        <BaseChip className="light:text-neutral-900">{t('Reported achievement')}</BaseChip>
      ) : null}

      {sessionEvent.type === 'rich-presence' && sessionEvent.description ? (
        <RichPresenceEventContent label={sessionEvent.description} />
      ) : null}

      {sessionEvent.type === 'reset' && sessionEvent.description ? (
        <ResetEventContent label={sessionEvent.description} />
      ) : null}

      {sessionEvent.type === 'custom' && sessionEvent.description ? (
        <div className="flex items-center gap-1.5 text-text">
          <p className="line-clamp-1" title={sessionEvent.description}>
            {sessionEvent.description}
          </p>
        </div>
      ) : null}

      {sessionEvent.type === 'ticket-created' ? (
        <div className="flex items-center gap-1.5 text-text">
          <LuTicket className="size-4 flex-none" aria-hidden="true" />
          <p>{t('This ticket was created.')}</p>
        </div>
      ) : null}
    </div>
  );
};
