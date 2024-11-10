import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { AchievementAvatar } from '@/common/components/AchievementAvatar';
import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { GameAvatar } from '@/common/components/GameAvatar';
import { SystemChip } from '@/common/components/SystemChip';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { AvatarSize } from '@/common/models';

import { HomeHeading } from '../../HomeHeading';

// TODO try different achievement description lengths
// TODO try different game title length

export const AchievementOfTheWeek: FC = () => {
  const { auth } = usePageProps();
  const { achievementOfTheWeek } = usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useTranslation();

  if (achievementOfTheWeek?.achievement === undefined) {
    return null;
  }

  const game = achievementOfTheWeek.sourceAchievement?.game;
  const system = game?.system;

  if (game === undefined || system === undefined) {
    return null;
  }

  return (
    <div>
      <HomeHeading>{t('Achievement of the Week')}</HomeHeading>

      <div className="flex flex-col gap-2">
        <div className="rounded bg-embed p-2">
          <div className="flex flex-col gap-4">
            <div className="flex items-center gap-2">
              <AchievementAvatar
                {...achievementOfTheWeek.achievement}
                hasTooltip={false}
                size={64}
                showLabel={false}
              />

              <div className="flex flex-col gap-0.5 self-start">
                <a href={route('achievement.show', { achievement: achievementOfTheWeek.achievement.id })}>
                  {achievementOfTheWeek.achievement.title}
                </a>

                <p>{achievementOfTheWeek.achievement.description}</p>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <GameAvatar {...game} size={44 as AvatarSize} showLabel={false} />

              <div className="flex flex-col gap-0.5">
                <GameAvatar {...game} showImage={false} />
                <div className="flex items-center">
                  <SystemChip {...system} className="bg-zinc-800" />

                  {achievementOfTheWeek.activeUntil ? (
                    // TODO: make this align bottom right
                    <div className="w-ful flex flex-end justify-end">
                      <span className="smalldate">
                        <span>{t('Ends')}: </span>
                        <DiffTimestamp
                          at={achievementOfTheWeek.activeUntil}
                          asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates}
                        />
                      </span>
                    </div>
                  ) : null}
                </div>
              </div>
            </div>
          </div>
        </div>

        {achievementOfTheWeek.forumTopicId ? (
          <div className="w-ful flex justify-end">
            <a className="text-xs" href={`/viewtopic.php?t=${achievementOfTheWeek.forumTopicId}`}>
              {t('Learn more about this event')}
            </a>
          </div>
        ) : null}
      </div>
    </div>
  );
};
