import type { FC } from 'react';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCircleDot, LuCircleDotDashed } from 'react-icons/lu';

import { BaseDialog, BaseDialogTrigger } from '@/common/components/+vendor/BaseDialog';
import { BaseProgress } from '@/common/components/+vendor/BaseProgress';
import { BaseSeparator } from '@/common/components/+vendor/BaseSeparator';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { AuthenticatedUser } from '@/common/models';
import { cn } from '@/common/utils/cn';
import { formatDate } from '@/common/utils/l10n/formatDate';
import { useFormatDuration } from '@/common/utils/l10n/useFormatDuration';
import { BeatenCreditDialog } from '@/features/games/components/BeatenCreditDialog';

interface BeatenProgressIndicatorProps {
  achievements: App.Platform.Data.Achievement[];
}

export const BeatenProgressIndicator: FC<BeatenProgressIndicatorProps> = ({ achievements }) => {
  const { auth, playerGame } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const { formatDuration } = useFormatDuration();

  const {
    isBeaten,
    isSoftcorePlayer,
    neededAchievementCount,
    progressionAchievements,
    unlockedAchievementCount,
    unlockedProgression,
    unlockedWin,
    winConditionAchievements,
  } = useMemo(() => getBeatenProgressData(achievements, auth?.user), [achievements, auth]);

  const Icon = isBeaten ? LuCircleDot : LuCircleDotDashed;

  // Determine which beaten date and time to beat to display.
  let beatenDate: string | null = null;
  let timeToBeat: number | null = null;
  if (playerGame?.beatenHardcoreAt) {
    beatenDate = playerGame.beatenHardcoreAt;
    timeToBeat = playerGame.timeToBeatHardcore ?? null;
  } else if (playerGame?.beatenAt) {
    beatenDate = playerGame.beatenAt;
    timeToBeat = playerGame.timeToBeat ?? null;
  }

  return (
    <BaseDialog>
      <BaseTooltip>
        <BaseTooltipTrigger asChild>
          <BaseDialogTrigger asChild>
            <button
              className={cn(
                'flex items-center gap-1 pl-3.5 text-neutral-300',
                isBeaten
                  ? 'text-opacity-100 light:text-neutral-600'
                  : 'text-opacity-30 light:text-neutral-500 light:text-opacity-40',
              )}
            >
              <Icon className="size-5" />
              <p className="font-medium">{t('Beaten')}</p>
            </button>
          </BaseDialogTrigger>
        </BaseTooltipTrigger>

        <BaseTooltipContent>
          <div className="flex flex-col gap-1">
            <p className="font-semibold">
              {isSoftcorePlayer ? t('Beaten Progress (Softcore)') : t('Beaten Progress')}
            </p>

            <div className="flex flex-col gap-0.5">
              <div className="flex w-full justify-between">
                <p>{t('Achievements')}</p>
                <p>
                  {unlockedAchievementCount}
                  {'/'}
                  {neededAchievementCount}
                </p>
              </div>

              <div className="flex gap-1">
                {progressionAchievements.length ? (
                  <BaseProgress
                    className={cn(
                      'h-2 bg-zinc-800',
                      winConditionAchievements.length ? 'w-40' : 'w-[184px]',
                    )}
                    max={progressionAchievements.length}
                    segments={[
                      {
                        value: unlockedProgression.length,
                        className: 'bg-green-600',
                      },
                    ]}
                  />
                ) : null}

                {winConditionAchievements.length ? (
                  <BaseProgress
                    className={cn(
                      'h-2 bg-zinc-800',
                      progressionAchievements.length ? 'w-6' : 'w-[184px]',
                    )}
                    max={1}
                    segments={[
                      {
                        value: unlockedWin.length ? 1 : 0,
                        className: 'bg-amber-600',
                      },
                    ]}
                  />
                ) : null}
              </div>
            </div>

            <div className="mt-2 flex flex-col leading-tight">
              {progressionAchievements.length ? (
                <p className="text-2xs">
                  <span className="font-semibold text-green-500">
                    {unlockedProgression.length}
                    {'/'}
                    {progressionAchievements.length}
                  </span>{' '}
                  {t('Progression')}
                </p>
              ) : null}

              {winConditionAchievements.length ? (
                <p className="text-2xs">
                  <span className="font-semibold text-amber-500">
                    {unlockedWin.length ? '1' : '0'}
                    {'/1'}
                  </span>{' '}
                  {t('Win Condition')}
                </p>
              ) : null}
            </div>

            {isBeaten && beatenDate ? (
              <>
                <BaseSeparator className="my-2" />

                <div className="flex flex-col gap-0.5">
                  <div className="flex justify-between text-2xs">
                    <p>{t('Beaten on')}</p>
                    <p className="font-medium">{formatDate(beatenDate, 'll')}</p>
                  </div>

                  {timeToBeat ? (
                    <div className="flex justify-between text-2xs">
                      <p>{t('Time to beat')}</p>
                      <p className="font-medium">{formatDuration(timeToBeat)}</p>
                    </div>
                  ) : null}
                </div>
              </>
            ) : null}
          </div>
        </BaseTooltipContent>
      </BaseTooltip>

      <BeatenCreditDialog />
    </BaseDialog>
  );
};

function getBeatenProgressData(
  achievements: App.Platform.Data.Achievement[],
  user: AuthenticatedUser | undefined,
) {
  const progressionAchievements = achievements.filter((ach) => ach.type === 'progression');
  const winConditionAchievements = achievements.filter((ach) => ach.type === 'win_condition');

  const unlockedProgression = progressionAchievements.filter(
    (ach) => ach.unlockedAt || ach.unlockedHardcoreAt,
  );
  const unlockedWin = winConditionAchievements.filter(
    (ach) => ach.unlockedAt || ach.unlockedHardcoreAt,
  );

  // Calculate hardcore vs softcore unlocks for beaten-related achievements.
  const unlockedProgressionHardcore = progressionAchievements.filter(
    (ach) => ach.unlockedHardcoreAt,
  ).length;
  const unlockedProgressionSoftcore = progressionAchievements.filter(
    (ach) => ach.unlockedAt && !ach.unlockedHardcoreAt,
  ).length;
  const unlockedWinHardcore = winConditionAchievements.filter(
    (ach) => ach.unlockedHardcoreAt,
  ).length;
  const unlockedWinSoftcore = winConditionAchievements.filter(
    (ach) => !ach.unlockedHardcoreAt && ach.unlockedAt,
  ).length;

  const totalHardcore = unlockedProgressionHardcore + unlockedWinHardcore;
  const totalSoftcore = unlockedProgressionSoftcore + unlockedWinSoftcore;

  // Determine if this is a softcore player.
  const isSoftcorePlayer =
    totalHardcore + totalSoftcore === 0
      ? user && user.pointsSoftcore > user.points
      : totalSoftcore > totalHardcore;

  const neededAchievementCount =
    progressionAchievements.length + (winConditionAchievements.length ? 1 : 0);
  const unlockedAchievementCount = unlockedProgression.length + (unlockedWin.length ? 1 : 0);

  // Determine if the game is beaten based on achievements (freshest data).
  // The game is beaten when all progression achievements are unlocked AND
  // a win condition achievement (if any) is unlocked.
  const isBeaten =
    progressionAchievements.length > 0
      ? unlockedProgression.length === progressionAchievements.length &&
        (winConditionAchievements.length === 0 || unlockedWin.length > 0)
      : winConditionAchievements.length > 0 && unlockedWin.length > 0;

  return {
    isBeaten,
    isSoftcorePlayer,
    neededAchievementCount,
    progressionAchievements,
    unlockedAchievementCount,
    unlockedProgression,
    unlockedProgressionHardcore,
    unlockedProgressionSoftcore,
    unlockedWin,
    unlockedWinHardcore,
    unlockedWinSoftcore,
    winConditionAchievements,
  };
}
