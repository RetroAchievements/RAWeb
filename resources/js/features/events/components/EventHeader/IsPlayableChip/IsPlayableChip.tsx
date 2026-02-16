import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { useEventStateMeta } from '@/common/hooks/useEventStateMeta';
import { cn } from '@/common/utils/cn';

interface IsPlayableChipProps {
  event: App.Platform.Data.Event;

  className?: string;
}

export const IsPlayableChip: FC<IsPlayableChipProps> = ({ event, className }) => {
  const { t } = useTranslation();

  const { eventStateMeta } = useEventStateMeta();

  if (!event.eventAchievements) {
    return null;
  }

  const { label, icon: Icon } = eventStateMeta[event.state!];

  const isActiveState = event.state === 'active' || event.state === 'evergreen';

  let tooltipText: string;
  if (event.state === 'active') {
    tooltipText = t(
      'This event is currently running and has a set end date. Join now to participate and earn an award before it ends.',
    );
  } else if (event.state === 'evergreen') {
    tooltipText = t(
      'This event has no end date. You can start participating at any time and progress at your own pace.',
    );
  } else {
    tooltipText = t('This event has ended and is no longer accepting new participants.');
  }

  return (
    <BaseTooltip>
      <BaseTooltipTrigger>
        <BaseChip
          className={cn(
            isActiveState && 'text-green-400',
            event.state === 'active' &&
              'light:border light:border-neutral-300 light:text-green-700',
            className,
          )}
          data-testid="playable"
        >
          <Icon className="size-4" />
          {label}
        </BaseChip>
      </BaseTooltipTrigger>

      <BaseTooltipContent className="max-w-72 text-center">{tooltipText}</BaseTooltipContent>
    </BaseTooltip>
  );
};
