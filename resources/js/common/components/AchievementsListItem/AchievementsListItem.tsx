import * as motion from 'motion/react-m';
import { type FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { BaseProgress } from '@/common/components/+vendor/BaseProgress';
import { AchievementAvatar } from '@/common/components/AchievementAvatar';
import { formatPercentage } from '@/common/utils/l10n/formatPercentage';

import { AchievementDateMeta } from './AchievementDateMeta';
import { AchievementGameTitle } from './AchievementGameTitle';
import { AchievementPoints } from './AchievementPoints';
import { ProgressBarMetaText } from './ProgressBarMetaText';

interface AchievementsListItemProps {
  achievement: App.Platform.Data.Achievement;
  index: number;
  isLargeList: boolean;
  playersTotal: number | null;

  /**
   * Wherever possible, map stuff onto `achievement`.
   * The less smart this component is, the easier it'll be to maintain long-term.
   * Only pick unique fields from this prop, such as `activeThrough`.
   */
  eventAchievement?: App.Platform.Data.EventAchievement;
}

export const AchievementsListItem: FC<AchievementsListItemProps> = ({
  achievement,
  index,
  isLargeList,
  eventAchievement,
  playersTotal,
}) => {
  const { t } = useTranslation();

  const { title, description, game } = achievement;

  const unlockPercentage = achievement.unlockPercentage ? Number(achievement.unlockPercentage) : 0;

  const unlocksHardcoreTotal = achievement.unlocksHardcoreTotal ?? 0;
  const unlocksTotal = achievement.unlocksTotal ?? 0;

  return (
    <motion.li
      className="flex w-full gap-x-3 px-2 py-3 odd:bg-[rgba(50,50,50,0.4)] light:odd:bg-neutral-100 md:py-1"
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: 10 }}
      transition={{
        duration: 0.12,
        delay: isLargeList
          ? Math.min(index * 0.008, 0.15) // Cap at 150ms for large lists
          : Math.min(index * 0.015, 0.2), // Cap at 200ms for small lists
      }}
    >
      <div className="flex flex-col gap-y-1 self-center">
        <AchievementAvatar
          {...achievement}
          showLabel={false}
          hasTooltip={false}
          size={64}
          displayLockedStatus="auto"
        />
      </div>

      <div className="mt-1 grid w-full gap-x-5 gap-y-1.5 leading-4 md:grid-cols-6">
        {/* Title and description area */}
        <div className="md:col-span-4">
          <div className="mb-0.5 flex justify-between gap-x-2">
            {/* Title */}
            <div className="-mt-2 mb-0.5 md:mt-0">
              <span className="mr-2">
                <a href={route('achievement.show', { achievement })} className="font-medium">
                  {title}
                  {game?.title ? ' ' : null}
                </a>

                {game?.title ? (
                  <Trans
                    i18nKey="<1>from</1> <2>{{gameTitle}}</2>"
                    components={{
                      1: <span />,
                      2: <AchievementGameTitle game={game} />,
                    }}
                  />
                ) : null}
              </span>

              <AchievementPoints
                isEvent={!!eventAchievement}
                points={achievement.points ?? 0}
                pointsWeighted={achievement.pointsWeighted}
              />
            </div>

            {/* Meta chips (Mobile) */}
            {/* <div className="-mt-1.5 flex items-center gap-x-1 md:hidden">
              <div className="-mt-1.5">

              </div>
            </div> */}
          </div>

          {/* Description */}
          <p className="leading-4">{description}</p>

          {/* Dates */}
          <AchievementDateMeta
            className="mt-1.5 hidden md:flex"
            achievement={achievement}
            eventAchievement={eventAchievement}
          />
        </div>

        {/* Progress bar and stats area */}
        {playersTotal !== null ? (
          <div className="md:col-span-2 md:flex md:flex-col-reverse md:justify-end md:gap-y-1 md:pt-1">
            {/* Meta chips */}
            {/* <div className="hidden items-center justify-end gap-x-1 md:flex"></div> */}

            <p className="-mt-1.5 hidden text-center text-2xs md:block">
              {t('{{percentage}} unlock rate', {
                percentage: formatPercentage(unlockPercentage),
              })}
            </p>

            <p className="mb-0.5 flex gap-x-1 text-2xs md:mb-0 md:justify-center md:text-center">
              <ProgressBarMetaText achievement={achievement} playersTotal={playersTotal} />
            </p>

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
          </div>
        ) : null}

        {/* Dates (mobile) */}
        <AchievementDateMeta
          className="md:hidden"
          achievement={achievement}
          eventAchievement={eventAchievement}
        />
      </div>
    </motion.li>
  );
};
