import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuClock } from 'react-icons/lu';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { cn } from '@/common/utils/cn';
import { useFormatDuration } from '@/common/utils/l10n/useFormatDuration';

interface PlaytimeLabelProps {
  session: App.Platform.Data.PlayerGameActivitySession;
}

export const PlaytimeLabel: FC<PlaytimeLabelProps> = ({ session }) => {
  const { t } = useTranslation();

  const { formatDuration } = useFormatDuration();

  if (session.type === 'manual-unlock' || session.type === 'ticket-created') {
    return null;
  }

  // If the duration is exactly 60, they just started the game and then turned it off.
  // This is a quirk in how the server records playtime durations.
  const isBootOnly = session.duration === 60;

  return (
    <div
      data-testid="playtime-label"
      className={cn(
        'flex items-center gap-1.5 md:gap-0.5',

        !session.duration || isBootOnly
          ? 'text-neutral-500 light:text-neutral-400'
          : 'light:text-neutral-900',
      )}
    >
      <LuClock className="size-4 min-w-5" />

      <BaseTooltip open={isBootOnly ? undefined : false}>
        <BaseTooltipTrigger className={!isBootOnly ? 'cursor-text' : undefined}>
          <span>
            {!session.duration ? t('Unknown') : null}
            {isBootOnly ? t('Boot Only') : null}
            {session.duration && !isBootOnly ? formatDuration(session.duration) : null}
          </span>

          {session.type === 'reconstructed' && session.duration ? (
            <span className="ml-1">{t('(estimated)')}</span>
          ) : null}
        </BaseTooltipTrigger>

        <BaseTooltipContent className="max-w-[280px]">
          <p className="text-xs">
            {t(
              'The player launched the game and then either experienced network issues or closed the game within 60 seconds.',
            )}
          </p>
        </BaseTooltipContent>
      </BaseTooltip>
    </div>
  );
};
