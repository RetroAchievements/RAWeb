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
  const badgeUrl =
    !achievement.unlockedAt && !achievement.unlockedHardcoreAt
      ? achievement.badgeLockedUrl
      : achievement.badgeUnlockedUrl;

  return (
    <div className="mb-2 flex items-center gap-2">
      <AchievementAvatar
        {...achievement}
        showHardcoreUnlockBorder={!!achievement.unlockedHardcoreAt}
        badgeUnlockedUrl={badgeUrl}
        showLabel={false}
        size={imageSize}
      />

      <div>
        <div className="flex items-center gap-2">
          <AchievementAvatar {...achievement} showImage={false} showPointsInTitle={true} />

          {showGame && achievement.game ? (
            <>
              <Trans
                i18nKey="<1>{{achievementTitle}}</1> from <2>{{gameTitle}}</2>"
                components={{
                  1: <span className="sr-only" />,
                  2: <span className="sr-only" />,
                }}
                values={{ achievementTitle: achievement.title, gameTitle: achievement.game.title }}
              />

              <GameAvatar {...achievement.game} showImage={false} />
            </>
          ) : null}
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
