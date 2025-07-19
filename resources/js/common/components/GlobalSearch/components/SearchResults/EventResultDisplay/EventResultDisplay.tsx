import type { FC } from 'react';

import { useEventStateMeta } from '@/common/hooks/useEventStateMeta';

interface EventResultDisplayProps {
  event: App.Platform.Data.Event;
}

export const EventResultDisplay: FC<EventResultDisplayProps> = ({ event }) => {
  const { eventStateMeta } = useEventStateMeta();

  const { label: stateLabel, icon: StateIcon } = eventStateMeta[event.state!];

  return (
    <div className="flex items-center gap-3">
      <img
        src={event.legacyGame!.badgeUrl}
        alt={event.legacyGame!.title}
        className="size-10 rounded"
      />

      <div className="flex flex-col gap-0.5">
        <div className="line-clamp-1 font-medium text-link">{event.legacyGame!.title}</div>

        <div className="text-xs text-neutral-400 light:text-neutral-600">
          <div className="flex items-center gap-1">
            <StateIcon className="!size-3" />
            {stateLabel}
          </div>
        </div>
      </div>
    </div>
  );
};
