import type { FC } from 'react';
import { Controller } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { LuCheck } from 'react-icons/lu';
import TextareaAutosize from 'react-textarea-autosize';
import { route } from 'ziggy-js';

import { BaseProgress } from '@/common/components/+vendor/BaseProgress';
import { AchievementTypeIndicator } from '@/common/components/AchievementTypeIndicator';
import { InertiaLink } from '@/common/components/InertiaLink';
import { useFormatDate } from '@/common/hooks/useFormatDate';
import { useFormatPercentage } from '@/common/hooks/useFormatPercentage';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { useAchievementHeroEditMode } from '../../hooks/useAchievementHeroEditMode';
import { AchievementPointsSelect } from './AchievementPointsSelect';
import { AchievementTypeSelect } from './AchievementTypeSelect';
import { editableAchievementClassNames } from './editableAchievementClassNames';
import { PointsLabels } from './PointsLabels';

export const AchievementHero: FC = () => {
  const { achievement, areAllAchievementsOnePoint, eventAchievement, isEventGame } =
    usePageProps<App.Platform.Data.AchievementShowPageProps>();

  const { t } = useTranslation();
  const { formatDate } = useFormatDate();
  const { formatPercentage } = useFormatPercentage();

  const {
    canEditDescription,
    canEditPoints,
    canEditTitle,
    canEditType,
    form,
    isEditMode,
    isSubset,
  } = useAchievementHeroEditMode();

  const playersTotal = achievement.game?.playersTotal as number;
  const unlocksTotal = achievement.unlocksTotal as number;
  const unlocksHardcoreTotal = achievement.unlocksHardcore as number;
  const unlocksSoftcoreTotal = unlocksTotal - unlocksHardcoreTotal;
  const unlockPercentage = achievement.unlockPercentage ? Number(achievement.unlockPercentage) : 0;

  const sourceAchievement = eventAchievement?.sourceAchievement;
  const isRevealedEventAchievement =
    isEventGame && !eventAchievement?.isObfuscated && !!sourceAchievement;

  const shouldShowPointsLabels =
    !canEditPoints && !(isEventGame && areAllAchievementsOnePoint) && achievement.points! > 0;

  const formattedUnlockPercentage = formatPercentage(unlockPercentage, {
    maximumFractionDigits: 2,
    minimumFractionDigits: 2,
  });

  return (
    <div className="rounded bg-embed p-2 light:border light:border-neutral-200 light:bg-neutral-50">
      <div className="flex flex-col gap-4">
        <div className="flex gap-4">
          <div className="shrink-0">
            <img
              src={
                achievement.unlockedAt ? achievement.badgeUnlockedUrl : achievement.badgeLockedUrl
              }
              alt={achievement.title}
              className="size-16 rounded-sm"
              width={64}
              height={64}
            />
          </div>

          <div className="min-w-0 flex-1">
            <div className="flex min-h-[30px] items-center justify-between gap-2">
              {canEditTitle ? (
                <Controller
                  control={form.control}
                  name="title"
                  render={({ field }) => (
                    <input
                      {...field}
                      aria-label={t('Achievement title')}
                      maxLength={64}
                      className={cn(
                        'm-0 w-full border-0 bg-transparent p-0 pb-[3px] text-lg font-bold leading-[1.25em] text-neutral-100 light:text-neutral-900',
                        editableAchievementClassNames.field,
                      )}
                      placeholder={t('Achievement title')}
                    />
                  )}
                />
              ) : (
                <p className="pb-[3px] text-lg font-bold leading-[1.25em] text-neutral-100 light:text-neutral-900">
                  {isRevealedEventAchievement ? (
                    <InertiaLink
                      href={route('achievement.show', {
                        achievement: sourceAchievement.id,
                      })}
                      className="text-neutral-100 transition hover:underline light:text-neutral-900"
                    >
                      {achievement.title}
                    </InertiaLink>
                  ) : (
                    achievement.title
                  )}
                </p>
              )}

              {canEditType ? (
                <AchievementTypeSelect form={form} isSubset={isSubset} />
              ) : achievement.type ? (
                <AchievementTypeIndicator
                  showLabel={true}
                  type={achievement.type}
                  wrapperClassName="hidden !bg-neutral-800 !pr-2 light:!bg-neutral-100 light:!text-neutral-800 md:inline-flex"
                />
              ) : (
                <div className="hidden h-[30px] md:block" />
              )}
            </div>

            <div className="flex flex-col gap-3">
              {canEditDescription ? (
                <Controller
                  control={form.control}
                  name="description"
                  render={({ field }) => (
                    <TextareaAutosize
                      {...field}
                      aria-label={t('Achievement description')}
                      onChange={(e) => {
                        field.onChange(e.target.value.replace(/\n/g, ''));
                      }}
                      onKeyDown={(e) => {
                        if (e.key === 'Enter') {
                          e.preventDefault();
                        }
                      }}
                      maxLength={255}
                      minRows={1}
                      className={cn(
                        'm-0 w-full resize-none border-0 bg-transparent p-0 text-sm leading-normal text-text',
                        editableAchievementClassNames.field,
                      )}
                      placeholder={t('Achievement description')}
                    />
                  )}
                />
              ) : (
                <p className="text-sm leading-normal text-text">{achievement.description}</p>
              )}

              <div className="hidden items-center gap-3 md:flex">
                {canEditPoints ? <AchievementPointsSelect form={form} /> : null}

                {shouldShowPointsLabels ? (
                  <PointsLabels
                    points={achievement.points}
                    pointsWeighted={achievement.pointsWeighted}
                    showRetroPoints={!isEventGame && achievement.isPromoted}
                  />
                ) : null}

                {!isEditMode && !achievement.isPromoted ? (
                  <>
                    <span className="text-neutral-700 light:text-neutral-300">{'·'}</span>
                    <p className="text-xs text-neutral-500">{t('Not promoted')}</p>
                  </>
                ) : null}
              </div>
            </div>
          </div>
        </div>

        {/* Mobile type + points row. Kept outside the disabled container so selects remain interactive during edit mode. */}
        <div className="flex items-center gap-3 md:hidden">
          {canEditType ? (
            <AchievementTypeSelect form={form} isSubset={isSubset} />
          ) : achievement.type ? (
            <AchievementTypeIndicator
              showLabel={true}
              type={achievement.type}
              wrapperClassName="!bg-neutral-800 !pr-2 light:!bg-neutral-100 light:!text-neutral-800"
            />
          ) : null}

          {canEditPoints ? <AchievementPointsSelect form={form} /> : null}

          {shouldShowPointsLabels ? (
            <PointsLabels
              points={achievement.points}
              pointsWeighted={achievement.pointsWeighted}
              showRetroPoints={!isEventGame && achievement.isPromoted}
            />
          ) : null}

          {!isEditMode && !achievement.isPromoted ? (
            <>
              <span className="text-neutral-700 light:text-neutral-300">{'·'}</span>
              <p className="text-xs text-neutral-500">{t('Not promoted')}</p>
            </>
          ) : null}
        </div>

        <div
          className={cn(
            'flex flex-col gap-2',
            isEditMode && editableAchievementClassNames.disabled,
          )}
        >
          {achievement.unlockedHardcoreAt || achievement.unlockedAt ? (
            <div className="flex w-full items-center gap-2 px-1.5">
              <div
                className={cn(
                  'flex items-center gap-1.5',
                  achievement.unlockedHardcoreAt
                    ? 'text-[gold] light:text-amber-500'
                    : 'text-neutral-300 light:text-neutral-700',
                )}
              >
                <LuCheck className="size-4" />
                {achievement.unlockedHardcoreAt ? t('Unlocked hardcore') : t('Unlocked')}
              </div>

              <span className="text-neutral-700 light:text-neutral-300">{'·'}</span>

              <p>{formatDate(achievement.unlockedHardcoreAt ?? achievement.unlockedAt!, 'll')}</p>
            </div>
          ) : null}
        </div>

        {achievement.isPromoted ? (
          <div
            className={cn(
              'flex flex-col gap-1',
              isEditMode && editableAchievementClassNames.disabled,
            )}
          >
            <div className="flex items-center gap-3">
              <BaseProgress
                className="h-1"
                max={playersTotal > 0 ? playersTotal : undefined}
                segments={[
                  {
                    value: unlocksHardcoreTotal,
                    className: 'bg-gradient-to-r from-amber-500 to-[gold]',
                  },
                  {
                    value: unlocksTotal - unlocksHardcoreTotal,
                    className: 'bg-neutral-500',
                  },
                ]}
              />

              <p className="-mt-px text-xs font-semibold">
                <span className="md:hidden">{formattedUnlockPercentage}</span>

                <span className="hidden whitespace-nowrap md:block">
                  {t('{{percentage}} unlock rate', {
                    percentage: formattedUnlockPercentage,
                  })}
                </span>
              </p>
            </div>

            <div className="text-2xs text-neutral-500 light:text-neutral-600">
              <p className="flex gap-1">
                {!isEventGame ? (
                  <>
                    <span>{t('{{val, number}} softcore', { val: unlocksSoftcoreTotal })}</span>
                    <span>{'·'}</span>
                  </>
                ) : null}

                {/* Events are hardcore-only, so unlocksHardcoreTotal is the true total */}
                <span>
                  {isEventGame
                    ? t('{{val, number}} unlocks', {
                        val: unlocksHardcoreTotal,
                        count: unlocksHardcoreTotal,
                      })
                    : t('{{val, number}} hardcore', { val: unlocksHardcoreTotal })}
                </span>
                <span>{'·'}</span>
                <span>{t('playerCount', { val: playersTotal, count: playersTotal })}</span>
              </p>
            </div>
          </div>
        ) : null}
      </div>
    </div>
  );
};
