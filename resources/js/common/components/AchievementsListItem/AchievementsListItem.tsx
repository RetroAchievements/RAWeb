import * as motion from 'motion/react-m';
import type { FC, ReactNode } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { BaseProgress } from '@/common/components/+vendor/BaseProgress';
import { AchievementAvatar } from '@/common/components/AchievementAvatar';
import { formatPercentage } from '@/common/utils/l10n/formatPercentage';

import { AchievementTypeIndicator } from '../AchievementTypeIndicator';
import { UserAvatar } from '../UserAvatar';
import { AchievementDateMeta } from './AchievementDateMeta';
import { AchievementGameTitle } from './AchievementGameTitle';
import { AchievementPoints } from './AchievementPoints';
import { ProgressBarMetaText } from './ProgressBarMetaText';

interface AchievementsListItemProps {
  achievement: App.Platform.Data.Achievement;
  index: number;
  isLargeList: boolean;
  playersTotal: number | null;

  beatenDialogContent?: ReactNode;

  /**
   * Wherever possible, map stuff onto `achievement`.
   * The less smart this component is, the easier it'll be to maintain long-term.
   * Only pick unique fields from this prop, such as `activeThrough`.
   */
  eventAchievement?: App.Platform.Data.EventAchievement;

  /**
   * When truthy, shows who created this achievement.
   * This is mainly intended for internal use, such as when the user is viewing
   * a list of unofficial achievements. The general public should see achievement
   * authors via the AchievementSetCredits component.
   */
  shouldShowAuthor?: boolean;

  /**
   * Weighted points don't dynamically recalculate for unpublished achievements,
   * so the values are misleading and should be hidden.
   */
  shouldShowWeightedPoints?: boolean;
}

export const AchievementsListItem: FC<AchievementsListItemProps> = ({
  achievement,
  beatenDialogContent,
  eventAchievement,
  index,
  isLargeList,
  playersTotal,
  shouldShowAuthor = false,
  shouldShowWeightedPoints = true,
}) => {
  const { t } = useTranslation();

  const { description, game, title, type, decorator } = achievement;

  const unlockPercentage = achievement.unlockPercentage ? Number(achievement.unlockPercentage) : 0;

  const unlocksHardcoreTotal = achievement.unlocksHardcore ?? 0;
  const unlocksTotal = achievement.unlocksTotal ?? 0;

  const hasVisibleUserComments = Boolean(
    (achievement as App.Platform.Data.Achievement & { hasVisibleUserComments?: boolean })
      .hasVisibleUserComments,
  );

  return (
    <motion.li
      className="game-set-item"
      initial={{ opacity: 0, transform: 'translateY(10px)' }}
      animate={{ opacity: 1, transform: 'translateY(0px)' }}
      exit={{ opacity: 0, transform: 'translateY(10px)' }}
      transition={{
        duration: 0.12,
        delay: isLargeList
          ? Math.min(index * 0.008, 0.15) // Cap at 150ms for large lists
          : Math.min(index * 0.015, 0.2), // Cap at 200ms for small lists
      }}
    >
      <div className="flex flex-col gap-y-1 md:mt-1">
        <AchievementAvatar
          {...achievement}
          showLabel={false}
          hasTooltip={false}
          size={64}
          displayLockedStatus="auto"
        />
      </div>

      <div className="grid w-full gap-x-5 gap-y-1.5 leading-4 md:grid-cols-6">
        {/* Title and description area */}
        <div className="md:col-span-4">
          <div className="mb-0.5 flex justify-between gap-x-2">
            {/* Title */}
            <div className="-mt-1 mb-0.5 md:mt-0">
              <span className="mr-2">
                <a
                  href={route('achievement.show', { achievementId: achievement.id })}
                  className="font-medium"
                >
                  {title}
                  {game?.title ? ' ' : null}
                </a>

                {hasVisibleUserComments ? (
                  <span
                    className="ml-1 inline-flex align-text-bottom text-neutral-500"
                    aria-label="This achievement has user comments"
                    title="This achievement has user comments"
                  >
                    <svg
                      aria-hidden="true"
                      className="size-3.5"
                      viewBox="0 0 16 16"
                      fill="none"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        d="M3 3.5C3 2.67157 3.67157 2 4.5 2H11.5C12.3284 2 13 2.67157 13 3.5V8.5C13 9.32843 12.3284 10 11.5 10H7.25L4.75 12V10H4.5C3.67157 10 3 9.32843 3 8.5V3.5Z"
                        stroke="currentColor"
                        strokeWidth="1.25"
                        strokeLinejoin="round"
                      />
                    </svg>
                  </span>
                ) : null}

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
                pointsWeighted={shouldShowWeightedPoints ? achievement.pointsWeighted : undefined}
              />
            </div>

            {/* Meta chips (Mobile) */}
            {type ? (
              <div className="-mt-1.5 flex items-center gap-x-1 md:hidden">
                <div className="-mt-1.5">
                  <AchievementTypeIndicator dialogContent={beatenDialogContent} type={type} />
                </div>
              </div>
            ) : null}
          </div>

          {/* Description */}
          <p className="leading-4">
            {decorator ? `${decorator}: ` : null}
            {description}
          </p>

          {/* Internal Use Author Label (for game unofficial achievement lists) */}
          {shouldShowAuthor && achievement.developer ? (
            <p className="mt-2 text-[0.6rem]">
              <Trans
                i18nKey="Author: <1>{{displayName}}</1>"
                components={{
                  1: (
                    <UserAvatar
                      {...achievement.developer}
                      showImage={false}
                      wrapperClassName="inline"
                      labelClassName="text-[0.6rem]"
                    />
                  ),
                }}
              />
            </p>
          ) : null}

          {/* Dates */}
          <AchievementDateMeta
            className="mt-1.5 hidden md:flex"
            achievement={achievement}
            eventAchievement={eventAchievement}
          />
        </div>

        {/* Progress bar and stats area */}
        {playersTotal !== null ? (
          <div className="mt-1 md:col-span-2 md:flex md:flex-col-reverse md:justify-end md:gap-y-1 md:pt-1">
            {/* Meta chips (Desktop) */}
            {type ? (
              <div className="hidden items-center justify-end gap-x-1 md:flex">
                <AchievementTypeIndicator dialogContent={beatenDialogContent} type={type} />
              </div>
            ) : null}

            <p className="-mt-1.5 hidden text-center text-2xs md:block">
              {t('{{percentage}} unlock rate', {
                percentage: formatPercentage(unlockPercentage),
              })}
            </p>

            <p className="mb-0.5 flex gap-x-1 text-2xs md:mb-0 md:justify-center md:text-center">
              <ProgressBarMetaText
                achievement={achievement}
                playersTotal={playersTotal}
                variant={eventAchievement ? 'event' : 'game'}
              />
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
