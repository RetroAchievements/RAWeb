import type { FC } from 'react';
import { Trans } from 'react-i18next';

import { AchievementAvatar } from '@/common/components/AchievementAvatar';
import { GameAvatar } from '@/common/components/GameAvatar';
import type { AvatarSize } from '@/common/models';
import { UnlockedAtLabel } from '@/features/achievements/components/UnlockedAtLabel';

interface UnlockableAchievementAvatarProps {
  achievement: App.Platform.Data.Achievement;
  showGame?: boolean;
  imageSize?: AvatarSize;
}

export const UnlockableAchievementAvatar: FC<UnlockableAchievementAvatarProps> = ({
  achievement,
  showGame = false,
  imageSize = 48,
}) => {
  return (
    <div className="mb-2 flex items-center gap-2">
      <AchievementAvatar
        {...achievement}
        displayLockedStatus="auto"
        showLabel={false}
        size={imageSize}
      />

      <div>
        <div className="flex items-center gap-2">
          {showGame && achievement.game ? (
            <Trans
              i18nKey="<1>{{achievementTitle}}</1> from <2>{{gameTitle}}</2>"
              components={{
                1: (
                  <AchievementAvatar {...achievement} showImage={false} showPointsInTitle={true} />
                ),
                2: <GameAvatar {...achievement.game} showImage={false} />,
              }}
              values={{ achievementTitle: achievement.title, gameTitle: achievement.game.title }}
            />
          ) : (
            <AchievementAvatar {...achievement} showImage={false} showPointsInTitle={true} />
          )}
        </div>

        <span>{achievement.description}</span>

        {achievement.unlockedHardcoreAt ? (
          <UnlockedAtLabel when={achievement.unlockedHardcoreAt} />
        ) : achievement.unlockedAt ? (
          <UnlockedAtLabel when={achievement.unlockedAt} />
        ) : null}
      </div>
    </div>
  );
};
