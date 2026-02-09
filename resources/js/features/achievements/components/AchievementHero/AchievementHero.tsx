import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { FaCircleCheck } from 'react-icons/fa6';

import { BaseProgress } from '@/common/components/+vendor/BaseProgress';
import { AchievementTypeIndicator } from '@/common/components/AchievementsListItem/AchievementTypeIndicator';
import { WeightedPointsContainer } from '@/common/components/WeightedPointsContainer';
import { useFormatDate } from '@/common/hooks/useFormatDate';
import { useFormatPercentage } from '@/common/hooks/useFormatPercentage';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

export const AchievementHero: FC = () => {
  const { achievement } = usePageProps<App.Platform.Data.AchievementShowPageProps>();

  const { t } = useTranslation();
  const { formatDate } = useFormatDate();
  const { formatPercentage } = useFormatPercentage();

  const playersTotal = achievement.game?.playersTotal as number;
  const unlocksTotal = achievement.unlocksTotal as number;
  const unlocksHardcoreTotal = achievement.unlocksHardcore as number;
  const unlocksSoftcoreTotal = unlocksTotal - unlocksHardcoreTotal;
  const unlockPercentage = achievement.unlockPercentage ? Number(achievement.unlockPercentage) : 0;

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
            <div className="flex items-center justify-between gap-2">
              <h1 className="text-h3 mb-0 border-b-0 text-lg font-bold text-neutral-100 light:text-neutral-900">
                {achievement.title}
              </h1>

              {achievement.type ? (
                <AchievementTypeIndicator
                  showLabel={true}
                  type={achievement.type}
                  wrapperClassName="hidden !bg-neutral-800 !pr-2 light:!bg-neutral-100 light:!text-neutral-800 md:inline-flex"
                />
              ) : (
                <span />
              )}
            </div>

            <div className="flex flex-col gap-3">
              <p>{achievement.description}</p>

              <div className="hidden md:block">
                <PointsLabels
                  points={achievement.points}
                  pointsWeighted={achievement.pointsWeighted}
                />
              </div>
            </div>
          </div>
        </div>

        <div className="flex flex-col gap-2">
          <div className="flex items-center justify-between gap-3">
            {achievement.type ? (
              <AchievementTypeIndicator
                showLabel={true}
                type={achievement.type}
                wrapperClassName="!bg-neutral-800 !pr-2 light:!bg-neutral-100 light:!text-neutral-800 md:hidden"
              />
            ) : (
              <span />
            )}

            <div className="md:hidden">
              <PointsLabels
                points={achievement.points}
                pointsWeighted={achievement.pointsWeighted}
              />
            </div>
          </div>

          {achievement.unlockedHardcoreAt || achievement.unlockedAt ? (
            <div className="flex w-full items-center gap-2 px-1.5">
              <div
                className={cn(
                  'flex items-center gap-1.5',
                  achievement.unlockedHardcoreAt ? 'text-[gold] light:text-amber-500' : null,
                )}
              >
                <FaCircleCheck className="size-4" />
                {achievement.unlockedHardcoreAt ? t('Unlocked hardcore') : t('Unlocked')}
              </div>

              <span className="text-neutral-700 light:text-neutral-300">{'·'}</span>

              <p className="md:hidden">
                {formatDate(achievement.unlockedHardcoreAt ?? achievement.unlockedAt!, 'll')}
              </p>
              <p className="hidden md:block">
                {formatDate(achievement.unlockedHardcoreAt ?? achievement.unlockedAt!, 'LLL')}
              </p>
            </div>
          ) : null}
        </div>

        <div className="flex flex-col gap-1">
          <div className="flex items-center gap-3">
            <BaseProgress
              className="h-1.5"
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

          <div className="hidden text-xs md:block">
            <p className="flex gap-1">
              <span>{t('{{val, number}} softcore', { val: unlocksSoftcoreTotal })}</span>
              <span className="text-neutral-700 light:text-neutral-300">{'·'}</span>
              <span>{t('{{val, number}} hardcore', { val: unlocksHardcoreTotal })}</span>
              <span className="text-neutral-700 light:text-neutral-300">{'·'}</span>
              <span>{t('playerCount', { val: playersTotal, count: playersTotal })}</span>
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

interface PointsLabelsProps {
  points: number | undefined;
  pointsWeighted: number | undefined;
}

const PointsLabels: FC<PointsLabelsProps> = ({ points, pointsWeighted }) => {
  return (
    <div className="flex gap-3 text-xs">
      <p className="light:text-neutral-900">
        <Trans
          i18nKey="<1>{{val, number}}</1> points"
          count={points}
          values={{ val: points }}
          components={{ 1: <span className="font-semibold" /> }}
        />
      </p>

      <WeightedPointsContainer>
        <p className="text-neutral-400">
          <Trans
            i18nKey="<1>{{val, number}}</1> RetroPoints"
            count={pointsWeighted}
            values={{ val: pointsWeighted }}
            components={{ 1: <span className="font-semibold" /> }}
          />
        </p>
      </WeightedPointsContainer>
    </div>
  );
};
