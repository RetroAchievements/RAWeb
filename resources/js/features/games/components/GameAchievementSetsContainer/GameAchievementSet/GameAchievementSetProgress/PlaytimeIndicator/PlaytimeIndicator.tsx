import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuClock } from 'react-icons/lu';

import {
  BasePopover,
  BasePopoverContent,
  BasePopoverTrigger,
} from '@/common/components/+vendor/BasePopover';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { formatDate } from '@/common/utils/l10n/formatDate';
import { useFormatDuration } from '@/common/utils/l10n/useFormatDuration';

export const PlaytimeIndicator: FC = () => {
  const { playerGame, ziggy } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const playtimeTotal = playerGame?.playtimeTotal ?? null;
  const hasPlaytime = playtimeTotal !== null && playtimeTotal > 0;

  // Determine the color based on whether the game has been played.
  const indicatorColorClassName = hasPlaytime
    ? 'text-neutral-200 light:text-neutral-600'
    : 'text-neutral-300/30 light:text-neutral-500/40';

  const ariaLabel = hasPlaytime ? t('Your Playtime Stats') : t('No playtime recorded.');

  if (ziggy.device === 'mobile') {
    return (
      <BasePopover>
        <BasePopoverTrigger>
          <div
            className={cn(
              'flex items-center gap-0.5 border-l border-neutral-700 pl-4 light:border-neutral-300',
              indicatorColorClassName,
            )}
            aria-label={ariaLabel}
          >
            <LuClock className="size-5" />
          </div>
        </BasePopoverTrigger>

        <BasePopoverContent
          side="top"
          className="w-auto min-w-max border-neutral-800 px-3 py-1.5 text-xs text-menu-link light:border-neutral-200"
        >
          <FloatableContent />
        </BasePopoverContent>
      </BasePopover>
    );
  }

  return (
    <BaseTooltip>
      <BaseTooltipTrigger>
        <div
          className={cn(
            'flex items-center gap-0.5 border-l border-neutral-700 pl-4 light:border-neutral-300',
            indicatorColorClassName,
          )}
          aria-label={ariaLabel}
        >
          <LuClock className="size-5" />
        </div>
      </BaseTooltipTrigger>

      <BaseTooltipContent>
        <FloatableContent />
      </BaseTooltipContent>
    </BaseTooltip>
  );
};

const FloatableContent: FC = () => {
  const { playerGame } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const { formatDuration } = useFormatDuration();

  const playtimeTotal = playerGame?.playtimeTotal ?? null;
  const lastPlayedAt = playerGame?.lastPlayedAt ?? null;
  const hasPlaytime = playtimeTotal !== null && playtimeTotal > 0;

  return (
    <div className="flex flex-col gap-1">
      <p className="font-semibold">{t('Your Playtime Stats')}</p>

      <div className="flex flex-col gap-0.5">
        {hasPlaytime ? (
          <>
            <div className="flex justify-between gap-4 text-2xs">
              <p>{t('Total playtime')}</p>
              <p className="font-medium">
                {formatDuration(playtimeTotal, { shouldTruncateSeconds: true })}
              </p>
            </div>

            {lastPlayedAt ? (
              <div className="flex justify-between gap-4 text-2xs">
                <p>{t('Last played')}</p>
                <p className="font-medium">{formatDate(lastPlayedAt, 'll')}</p>
              </div>
            ) : null}
          </>
        ) : (
          <p className="text-2xs text-neutral-400">{t('No playtime recorded.')}</p>
        )}
      </div>
    </div>
  );
};
