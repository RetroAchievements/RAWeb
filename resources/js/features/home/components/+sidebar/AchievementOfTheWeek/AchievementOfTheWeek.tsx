import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { AchievementAvatar } from '@/common/components/AchievementAvatar';
import { GameAvatar } from '@/common/components/GameAvatar';
import { SystemChip } from '@/common/components/SystemChip';
import type { AvatarSize } from '@/common/models';

import { HomeHeading } from '../../HomeHeading';

// TODO try different achievement description lengths
// TODO try different game title length
// TODO thread link
// TODO learn more link

const mockAchievement: App.Platform.Data.Achievement = {
  id: 87552,
  title: "Maybe... It's Time... The Legend Repeats Itself",
  description: 'Save the Earth spirit.',
  badgeLockedUrl: 'http://media.retroachievements.org/Badge/85195_lock.png',
  badgeUnlockedUrl: 'http://media.retroachievements.org/Badge/85195.png',
  game: {
    id: 1432,
    title: 'Monster World IV',
    badgeUrl: 'http://media.retroachievements.org/Images/020058.png',
    system: {
      id: 1,
      name: 'Genesis/Mega Drive',
      iconUrl: 'http://localhost:64000/assets/images/system/md.png',
      nameShort: 'MD',
    },
  },
};

export const AchievementOfTheWeek: FC = () => {
  const { t } = useTranslation();

  const game = mockAchievement.game as App.Platform.Data.Game;
  const system = mockAchievement.game?.system as App.Platform.Data.System;

  return (
    <div>
      <HomeHeading>{t('Achievement of the Week')}</HomeHeading>

      <div className="flex flex-col gap-2">
        <div className="rounded bg-embed p-2">
          <div className="flex flex-col gap-4">
            <div className="flex items-center gap-2">
              <AchievementAvatar
                {...mockAchievement}
                hasTooltip={false}
                size={64}
                showLabel={false}
              />

              <div className="flex flex-col gap-0.5">
                <a href={route('achievement.show', { achievement: mockAchievement.id })}>
                  {mockAchievement.title}
                </a>
                <p>{mockAchievement.description}</p>
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

        <div className="w-ful flex justify-end">
          <a className="text-xs" href="#">
            {t('Learn more about this event')}
          </a>
        </div>
      </div>
    </div>
  );
};
