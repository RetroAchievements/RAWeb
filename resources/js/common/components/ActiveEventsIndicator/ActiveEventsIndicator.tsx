import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import { BaseDialog, BaseDialogTrigger } from '../+vendor/BaseDialog';
import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';
import { RaEvent } from '../RaEvent';

interface ActiveEventsIndicatorProps {
  activeEvents: NonNullable<App.Platform.Data.ActiveEventAchievement['type']>;
}

export const ActiveEventsIndicator: FC<ActiveEventsIndicatorProps> = ({
  activeEvents,
}) => {
  const link = activeEvents.link;
  const label = activeEvents.summary.replace('\n', '<br/>');
  const stroke = activeEvents.userUnlocked ? 'yellow' : 'currentColor';

  return (
    <BaseTooltip>
      <BaseTooltipTrigger asChild>
        <a href={link}>
          <div
            data-testid='type-active_event'
            className={cn(
              'type-ind group',
              'cursor-pointer',
              'flex items-center gap-1',
            )}
            >
            <div aria-label='active_event'>
              <RaEvent className="size-4.5" stroke={stroke} border="none" />
            </div>
          </div>
        </a>
      </BaseTooltipTrigger>

      <BaseTooltipContent>{label}</BaseTooltipContent>
    </BaseTooltip>
  );
};
