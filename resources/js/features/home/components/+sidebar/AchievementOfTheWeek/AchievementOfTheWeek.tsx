import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';

import { AchievementAvatar } from '@/common/components/AchievementAvatar';
import { GameAvatar } from '@/common/components/GameAvatar';
import { SystemChip } from '@/common/components/SystemChip';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { AvatarSize } from '@/common/models';

import { HomeHeading } from '../../HomeHeading';

// TODO try different achievement description lengths
// TODO try different game title length

export const AchievementOfTheWeek: FC = () => {
  const { achievementOfTheWeek, staticData } = usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useLaravelReactI18n();

  // TODO needs better empty state
  if (!achievementOfTheWeek?.game?.system) {
    return null;
  }

  const game = achievementOfTheWeek.game;
  const system = achievementOfTheWeek.game.system;

  return (
    <div>
      <HomeHeading>{t('Achievement of the Week')}</HomeHeading>

      <div className="flex flex-col gap-2">
        <div className="rounded bg-embed p-2">
          <div className="flex flex-col gap-4">
            <div className="flex items-center gap-2">
              <AchievementAvatar
                {...achievementOfTheWeek}
                hasTooltip={false}
                size={64}
                showLabel={false}
              />

              <div className="flex flex-col gap-0.5 self-start">
                <a href={route('achievement.show', { achievement: achievementOfTheWeek.id })}>
                  {achievementOfTheWeek.title}
                </a>

                <p>{achievementOfTheWeek.description}</p>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <GameAvatar {...game} size={44 as AvatarSize} showLabel={false} />

              <div className="flex flex-col gap-0.5">
                <GameAvatar {...game} showImage={false} />
                <SystemChip {...system} className="bg-zinc-800" />
              </div>
            </div>
          </div>
        </div>

        {staticData.eventAotwForumId ? (
          <div className="w-ful flex justify-end">
            <a className="text-xs" href={`/viewtopic.php?t=${staticData.eventAotwForumId}`}>
              {t('Learn more about this event')}
            </a>
          </div>
        ) : null}
      </div>
    </div>
  );
};
