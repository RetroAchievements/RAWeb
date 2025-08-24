/* eslint-disable react/jsx-no-literals -- don't care about translating this content */

import dayjs from 'dayjs';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { cn } from '@/common/utils/cn';
import { formatDate } from '@/common/utils/l10n/formatDate';

interface MilestonePointsBoxProps {
  totalPoints: number;
}

export const MilestonePointsBox: FC<MilestonePointsBoxProps> = ({ totalPoints }) => {
  const { t } = useTranslation();
  const { formatNumber } = useFormatNumber();

  const formattedPoints = formatNumber(totalPoints);

  const firstChar = formattedPoints.charAt(0);
  const remainingChars = formattedPoints.slice(1);

  return (
    <BaseTooltip>
      <BaseTooltipTrigger asChild>
        <div
          data-testid="milestone-points"
          className={cn(
            'group flex h-full cursor-help flex-col rounded bg-embed px-2 py-2.5',
            'relative overflow-hidden',
            'border border-amber-400/30 bg-gradient-to-br from-embed to-amber-950/10',
            'before:absolute before:inset-0 before:bg-gradient-to-r',
            'before:from-transparent before:via-amber-400/10 before:to-transparent',
            'before:bg-[length:200%_100%] before:motion-safe:animate-shimmer',
            'light:border-amber-500/30 light:to-amber-100/10 light:before:via-amber-500/10',
          )}
        >
          <p className="text-xs leading-4 text-neutral-400/90 light:text-neutral-950 lg:text-2xs">
            {t('Points Earned Since {{date}}', {
              date: formatDate(dayjs('2013-03-02'), 'LL'),
            })}
          </p>

          <div className="!text-[20px] leading-7 text-neutral-300 light:text-neutral-950">
            <span>
              <span
                className={cn(
                  'inline-block text-amber-400 motion-safe:animate-pulse-glow',
                  'light:text-amber-500',
                )}
              >
                {firstChar}
              </span>

              <span>{remainingChars}</span>
            </span>
          </div>
        </div>
      </BaseTooltipTrigger>

      <BaseTooltipContent side="top" className="max-w-xs">
        <div className="flex flex-col gap-2 text-center">
          <p className="text-balance">Thank you for helping us reach 1 billion points!</p>
          <p className="text-balance">
            This incredible milestone is thanks to our amazing community of developers and players.
          </p>
        </div>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
