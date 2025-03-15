import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { AchievementAvatar } from '@/common/components/AchievementAvatar';
import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { GameAvatar } from '@/common/components/GameAvatar';
import { InertiaLink } from '@/common/components/InertiaLink';
import { SystemChip } from '@/common/components/SystemChip';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { AvatarSize } from '@/common/models';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';
import { cn } from '@/common/utils/cn';

import { HomeHeading } from '../../HomeHeading';
import { AotwUnlockedIndicator } from './AotwUnlockedIndicator';

export const AchievementOfTheWeek: FC = () => {
  const { achievementOfTheWeek, auth } = usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useTranslation();

  if (!achievementOfTheWeek) {
    return null;
  }

  const { currentEventAchievement } = achievementOfTheWeek;

  const game = currentEventAchievement?.sourceAchievement?.game;
  const system = game?.system;

  if (!currentEventAchievement?.achievement || !game || !system) {
    return null;
  }

  return (
    <div>
      <HomeHeading>{t('Achievement of the Week')}</HomeHeading>

      <div className="flex flex-col gap-2">
        <div className="overflow-hidden rounded bg-embed px-2 pt-2">
          <div className="flex flex-col gap-4">
            <div className="flex items-center gap-3">
              <AchievementAvatar
                {...currentEventAchievement.achievement}
                hasTooltip={false}
                size={64}
                showLabel={false}
                displayLockedStatus={
                  achievementOfTheWeek?.doesUserHaveUnlock ? 'unlocked-hardcore' : 'unlocked'
                }
              />

              <div className="flex flex-col gap-0.5 self-start">
                <a
                  href={route('achievement.show', {
                    achievement: currentEventAchievement.achievement.id,
                  })}
                >
                  {currentEventAchievement.achievement.title}
                </a>

                <p>{currentEventAchievement.achievement.description}</p>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <GameAvatar {...game} size={44 as AvatarSize} showLabel={false} />

              <div className="flex w-full flex-col gap-0.5">
                <GameAvatar {...game} showImage={false} />
                <div className="flex w-full items-center justify-between">
                  <SystemChip {...system} className="bg-zinc-800" />

                  {currentEventAchievement.activeThrough ? (
                    <span className="smalldate !min-w-fit self-end">
                      <Trans
                        i18nKey="Ends <1>{{when}}</1>"
                        values={{ when: currentEventAchievement.activeThrough }}
                        components={{
                          1: (
                            <DiffTimestamp
                              at={currentEventAchievement.activeThrough}
                              asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates}
                            />
                          ),
                        }}
                      />
                    </span>
                  ) : null}
                </div>
              </div>
            </div>
          </div>

          <div className="-mx-2 mt-2">
            <AotwUnlockedIndicator />
          </div>
        </div>

        {currentEventAchievement.event?.legacyGame ? (
          <div className="w-ful flex justify-end">
            <InertiaLink
              className={cn('text-xs', buildTrackingClassNames('Click AOTW Link'))}
              href={route('event.show', { event: currentEventAchievement.event.id })}
            >
              {t("View this year's event")}
            </InertiaLink>
          </div>
        ) : null}
      </div>
    </div>
  );
};
