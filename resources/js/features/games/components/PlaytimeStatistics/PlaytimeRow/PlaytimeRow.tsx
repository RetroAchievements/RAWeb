import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import type { IconType } from 'react-icons/lib';
import { LuInfo } from 'react-icons/lu';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { useFormatPercentage } from '@/common/hooks/useFormatPercentage';
import { cn } from '@/common/utils/cn';
import { useFormatDuration } from '@/common/utils/l10n/useFormatDuration';
import type { TranslatedString } from '@/types/i18next';

/**
 * We need at least this many players in our playtime samples to show
 * playtime data. Otherwise, the sample size is too small for it to
 * be reasonably accurate.
 */
const MIN_SAMPLES_FOR_MEDIAN = 5;

interface PlaytimeRowProps {
  headingLabel: TranslatedString;
  Icon: IconType;
  iconContainerClassName: string;
  iconClassName: string;

  rowPlayers?: number;
  rowSeconds?: number;
  totalPlayers?: number;
  /** How many tracked playtimes do we have stored in the DB for this milestone? */
  totalSamples?: number;
}

export const PlaytimeRow: FC<PlaytimeRowProps> = ({
  headingLabel,
  Icon,
  iconClassName,
  iconContainerClassName,
  rowPlayers,
  rowSeconds,
  totalPlayers,
  totalSamples,
}) => {
  const { t } = useTranslation();

  const { formatDuration } = useFormatDuration();
  const { formatPercentage } = useFormatPercentage();

  return (
    <div className="flex items-center justify-between gap-3 rounded-lg bg-zinc-800/30 p-2 light:bg-neutral-50">
      <div className="flex items-center gap-2">
        <div
          className={cn(
            'flex size-10 items-center justify-center rounded-full',
            iconContainerClassName,
          )}
        >
          <Icon className={cn('size-5', iconClassName)} />
        </div>

        <div className="flex flex-col">
          <p className="text-xs">{headingLabel}</p>

          <p className="text-xs text-neutral-500">
            {totalPlayers && rowPlayers && rowPlayers !== totalPlayers ? (
              <span>
                {t('{{val, number}} players ({{playersPercentage}})', {
                  count: rowPlayers,
                  val: rowPlayers,
                  playersPercentage: formatPercentage(rowPlayers / totalPlayers, {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1,
                  }),
                })}
              </span>
            ) : (
              <span>{t('{{val, number}} players', { count: rowPlayers, val: rowPlayers })}</span>
            )}
          </p>
        </div>
      </div>

      {totalSamples && totalSamples >= MIN_SAMPLES_FOR_MEDIAN && !!rowSeconds ? (
        <div className="flex flex-col text-right">
          <p className="text-sm text-neutral-300 light:text-neutral-700">
            {formatDuration(rowSeconds, { shouldTruncateSeconds: true })}
          </p>
          <p className="text-xs text-neutral-600">{t('median time')}</p>
        </div>
      ) : null}

      {totalSamples === undefined || totalSamples < MIN_SAMPLES_FOR_MEDIAN ? (
        <BaseTooltip>
          <BaseTooltipTrigger>
            <div className="flex items-center justify-end gap-1 text-right text-xs text-neutral-600">
              <LuInfo className="-mt-px" />
              {t('Not enough data')}
            </div>
          </BaseTooltipTrigger>

          <BaseTooltipContent className="max-w-[300px]">
            {t(
              'Not enough players have completed this milestone with time tracking enabled yet. Check back later as more data becomes available.',
            )}
          </BaseTooltipContent>
        </BaseTooltip>
      ) : null}
    </div>
  );
};
