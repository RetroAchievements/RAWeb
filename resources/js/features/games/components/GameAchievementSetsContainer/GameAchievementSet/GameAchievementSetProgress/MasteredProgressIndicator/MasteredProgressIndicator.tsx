import { useSetAtom } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuAward } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BasePopover,
  BasePopoverContent,
  BasePopoverTrigger,
} from '@/common/components/+vendor/BasePopover';
import { BaseProgress } from '@/common/components/+vendor/BaseProgress';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { useFormatPercentage } from '@/common/hooks/useFormatPercentage';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { isResetAllProgressDialogOpenAtom } from '@/features/games/state/games.atoms';

interface MasteredProgressIndicatorProps {
  achievements: App.Platform.Data.Achievement[];
}

export const MasteredProgressIndicator: FC<MasteredProgressIndicatorProps> = ({ achievements }) => {
  const { auth, backingGame, game, playerGameProgressionAwards, ziggy } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  const { formatPercentage } = useFormatPercentage();

  if (!auth?.user) {
    return null;
  }

  const totalUnlockedCount = achievements.filter(
    (ach) => ach.unlockedAt || ach.unlockedHardcoreAt,
  ).length;

  // Calculate completion percentage with special rounding rules.
  const rawPercentage =
    achievements.length > 0 ? (totalUnlockedCount / achievements.length) * 100 : 0;
  const completionPercentage =
    rawPercentage >= 99 && rawPercentage < 100
      ? Math.floor(rawPercentage)
      : Math.ceil(rawPercentage);

  const formattedPercentage = formatPercentage(completionPercentage / 100, {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  });

  const isMastered = playerGameProgressionAwards?.mastered;
  const isCompleted = playerGameProgressionAwards?.completed;

  const isSubsetPage = backingGame.id !== game.id;

  const indicatorColorClassName = getIndicatorColorClassName(
    !!isMastered,
    !!isCompleted,
    completionPercentage,
  );

  if (ziggy.device === 'mobile') {
    return (
      <BasePopover>
        <BasePopoverTrigger>
          <div
            className={cn(
              'flex items-center gap-0.5',
              !isSubsetPage ? 'border-r border-neutral-700 pr-4 light:border-neutral-300' : null,
              indicatorColorClassName,
            )}
          >
            <LuAward className="size-5" />
            <p className="font-medium">{formattedPercentage}</p>
          </div>
        </BasePopoverTrigger>

        <BasePopoverContent
          side="top"
          className="w-auto min-w-max border-neutral-800 px-3 py-1.5 text-xs text-menu-link light:border-neutral-200"
        >
          <FloatableContent achievements={achievements} />
        </BasePopoverContent>
      </BasePopover>
    );
  }

  return (
    <BaseTooltip>
      <BaseTooltipTrigger>
        <div
          className={cn(
            'flex items-center gap-0.5',
            !isSubsetPage ? 'border-r border-neutral-700 pr-4 light:border-neutral-300' : null,
            indicatorColorClassName,
          )}
        >
          <LuAward className="size-5" />
          <p className="font-medium">{formattedPercentage}</p>
        </div>
      </BaseTooltipTrigger>

      <BaseTooltipContent>
        <FloatableContent achievements={achievements} />
      </BaseTooltipContent>
    </BaseTooltip>
  );
};

function getIndicatorColorClassName(
  isMastered: boolean,
  isCompleted: boolean,
  completionPercentage: number,
): string {
  if (isMastered && completionPercentage > 0) {
    return 'text-amber-400 light:text-amber-500';
  }

  if (isCompleted && completionPercentage > 0) {
    return 'text-neutral-200 light:text-neutral-600';
  }

  return 'text-neutral-300/30 light:text-neutral-500/40';
}

interface FloatableContentProps {
  achievements: App.Platform.Data.Achievement[];
}

const FloatableContent: FC<FloatableContentProps> = ({ achievements }) => {
  const { t } = useTranslation();

  // The dialog is mounted way higher than the tooltip.
  // This prevents the dialog from unmounting when the tooltip closes.
  const setIsResetAllProgressDialogOpen = useSetAtom(isResetAllProgressDialogOpenAtom);

  const unlockedHardcoreCount = achievements.filter((ach) => ach.unlockedHardcoreAt).length;
  const unlockedSoftcoreCount = achievements.filter(
    (ach) => ach.unlockedAt && !ach.unlockedHardcoreAt,
  ).length;

  return (
    <div className="flex flex-col gap-1">
      <p className="font-semibold">{t('Overall Set Progress')}</p>

      <div className="flex flex-col gap-0.5">
        <div className="flex w-full justify-between">
          <p>{t('Achievements')}</p>
          <p>
            {unlockedHardcoreCount + unlockedSoftcoreCount}
            {'/'}
            {achievements.length}
          </p>
        </div>

        <BaseProgress
          className="h-2 w-[184px] bg-zinc-800"
          max={achievements.length}
          segments={[
            {
              value: unlockedHardcoreCount,
              className: 'bg-gradient-to-r from-amber-500 to-[gold]',
            },
            {
              value: unlockedSoftcoreCount,
              className: 'bg-neutral-500',
            },
          ]}
        />
      </div>

      {unlockedHardcoreCount > 0 && unlockedSoftcoreCount > 0 ? (
        <div className="mt-2 flex flex-col leading-tight">
          <p className="text-2xs">
            <span className="font-semibold text-amber-500">
              {unlockedHardcoreCount}
              {'/'}
              {achievements.length}
            </span>{' '}
            {t('Hardcore')}
          </p>

          <p className="text-2xs">
            <span className="font-semibold text-neutral-500">
              {unlockedSoftcoreCount}
              {'/'}
              {achievements.length}
            </span>{' '}
            {t('Softcore')}
          </p>
        </div>
      ) : null}

      {unlockedHardcoreCount || unlockedSoftcoreCount ? (
        <BaseButton
          size="sm"
          variant="destructive"
          className="mt-3 h-fit py-0.5"
          onClick={() => setIsResetAllProgressDialogOpen(true)}
        >
          {t('Reset all progress')}
        </BaseButton>
      ) : null}
    </div>
  );
};
