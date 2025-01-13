import { useAtom } from 'jotai';
import type { FC } from 'react';

import { AchievementAvatar } from '@/common/components/AchievementAvatar';
import { persistedAchievementsAtom } from '@/features/forums/state/forum.atoms';

interface ShortcodeAchievementProps {
  achievementId: number;
}

export const ShortcodeAch: FC<ShortcodeAchievementProps> = ({ achievementId }) => {
  const [persistedAchievements] = useAtom(persistedAchievementsAtom);

  const foundAchievement = persistedAchievements?.find(
    (achievement) => achievement.id === achievementId,
  );

  if (!foundAchievement) {
    return null;
  }

  return (
    <span data-testid="achievement-embed" className="ml-0.5 inline-block">
      <AchievementAvatar
        {...foundAchievement}
        size={24}
        showHardcoreUnlockBorder={false}
        showPointsInTitle={true}
        variant="inline"
      />
    </span>
  );
};
