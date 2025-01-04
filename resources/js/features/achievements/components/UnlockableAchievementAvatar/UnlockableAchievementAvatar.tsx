import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

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
  const { t } = useTranslation();

  return (
    <div className="mb-2 flex items-center gap-2">
      {achievement.unlockedHardcoreAt ? (
        <AchievementAvatar
          {...achievement}
          showHardcoreUnlockBorder={true}
          // TODO: showPointsInTitle={true}
          showLabel={false}
          size={imageSize}
        />
      ) : achievement.unlockedAt ? (
        <AchievementAvatar
          {...achievement}
          showHardcoreUnlockBorder={false}
          // TODO: showPointsInTitle={true}
          showLabel={false}
          size={imageSize}
        />
      ) : (
        <AchievementAvatar
          {...achievement}
          showHardcoreUnlockBorder={false}
          badgeUnlockedUrl={achievement.badgeLockedUrl}
          // TODO: showPointsInTitle={true}
          showLabel={false}
          size={imageSize}
        />
      )}

      <div>
        <div className="flex items-center gap-2">
          <AchievementAvatar {...achievement} showImage={false} />

          {showGame && achievement.game ? (
            <>
              <span>{t('from')}</span>
              <GameAvatar {...achievement.game} showImage={false} />
            </>
          ) : (
            <></>
          )}
        </div>

        <span>{achievement.description}</span>

        {achievement.unlockedHardcoreAt ? (
          <UnlockedAtLabel when={achievement.unlockedHardcoreAt} />
        ) : achievement.unlockedAt ? (
          <UnlockedAtLabel when={achievement.unlockedAt} />
        ) : (
          <></>
        )}
      </div>
    </div>
  );
};
