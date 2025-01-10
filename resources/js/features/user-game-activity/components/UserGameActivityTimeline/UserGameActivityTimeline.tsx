import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import type { FC } from 'react';
import { FaFileArchive } from 'react-icons/fa';

import { Timeline, TimelineItem } from '@/common/components/Timeline';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { formatDate } from '@/common/utils/l10n/formatDate';

import { getShouldAchievementSessionBeVisible } from '../../utils/getShouldAchievementSessionBeVisible';
import { ClientLabel } from './ClientLabel';
import { HashLabel } from './HashLabel';
import { PlaytimeLabel } from './PlaytimeLabel';
import { SessionTimelineEvent } from './SessionTimelineEvent';

dayjs.extend(utc);

interface UserGameActivityTimelineProps {
  isOnlyShowingAchievementSessions: boolean;
}

export const UserGameActivityTimeline: FC<UserGameActivityTimelineProps> = ({
  isOnlyShowingAchievementSessions,
}) => {
  const { activity } = usePageProps<App.Platform.Data.PlayerGameActivityPageProps>();

  const { sessions } = activity;

  // If it's a manual unlock session but there are no events in the session,
  // then there's nothing we can show.
  const filteredSessions = sessions.filter((s) => {
    if (s.type === 'manual-unlock' && !s.events.length) {
      return false;
    }

    return true;
  });

  return (
    <div className="rounded-lg border border-embed-highlight bg-embed p-4 text-gray-200">
      <Timeline>
        {filteredSessions.map((session, index) => {
          const isVisible = getShouldAchievementSessionBeVisible(
            session,
            isOnlyShowingAchievementSessions,
          );

          if (!isVisible) {
            return null;
          }

          return (
            <TimelineItem key={`session-${index}`} label={formatDate(session.startTime, 'lll')}>
              <SessionHeader session={session} />
              <SessionEvents session={session} sessionIndex={index} />
            </TimelineItem>
          );
        })}
      </Timeline>
    </div>
  );
};

interface SessionHeaderProps {
  session: App.Platform.Data.PlayerGameActivitySession;
}

const SessionHeader: FC<SessionHeaderProps> = ({ session }) => {
  return (
    <div
      data-testid="session-header"
      className={cn(
        'flex flex-col gap-1.5 rounded-t-lg border-b p-3',
        'border-neutral-700 bg-neutral-800 light:border-neutral-200 light:bg-neutral-100',
      )}
    >
      <div className="flex w-full flex-col gap-1.5 md:flex-row md:justify-between">
        <ClientLabel session={session} />
        <PlaytimeLabel session={session} />
      </div>

      {session.type === 'player-session' ? (
        <div
          data-testid="rom-info"
          className={cn(
            'flex items-center gap-1.5',
            !session.gameHash
              ? 'text-neutral-500 light:text-neutral-400'
              : 'light:text-neutral-900',
          )}
        >
          <FaFileArchive className="size-4 min-w-5" />
          <HashLabel session={session} />
        </div>
      ) : null}
    </div>
  );
};

interface SessionEventsProps {
  session: App.Platform.Data.PlayerGameActivitySession;
  sessionIndex: number;
}

const SessionEvents: FC<SessionEventsProps> = ({ session, sessionIndex }) => {
  return (
    <div>
      <ol className="flex flex-col">
        {session.events.map((event, eventIndex) => {
          const currentEventTimestamp = event.when;

          const previousEventTimestamp =
            eventIndex === 0 ? session.startTime : session.events[eventIndex - 1].when;

          const nextEventTimestamp =
            eventIndex === session.events.length - 1 ? null : session.events[eventIndex + 1].when;

          // Grouped events won't have any borders between them and will only show a single timestamp.
          const isGrouped = currentEventTimestamp === nextEventTimestamp;

          // Check if the previous event was grouped with the current event. If it was and we're
          // rendering a bunch of stuff in a group, then we'll only show a timestamp on the row
          // for when the group starts.
          const isPreviousGrouped =
            eventIndex !== 0 && previousEventTimestamp === currentEventTimestamp;

          return (
            <li
              key={`sessionEvent-${sessionIndex}-${eventIndex}`}
              className={cn(
                isGrouped ? 'px-3 pt-3' : 'px-3 pb-2.5 pt-3',

                eventIndex !== session.events.length - 1 && !isGrouped
                  ? 'border-b border-neutral-700 light:border-neutral-200'
                  : null,
              )}
            >
              <SessionTimelineEvent
                isPreviousGrouped={isPreviousGrouped}
                sessionEvent={event}
                sessionType={session.type}
                previousEventTimestamp={previousEventTimestamp}
                previousEventKind={
                  eventIndex === 0 ? 'start-session' : session.events[eventIndex - 1].type
                }
              />
            </li>
          );
        })}
      </ol>
    </div>
  );
};
