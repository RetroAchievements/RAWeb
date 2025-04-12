import { useAtomValue } from 'jotai';
import type { FC } from 'react';

import { persistedAchievementsAtom } from '../../../state/shortcode.atoms';
import { AchievementAvatar } from '../../AchievementAvatar';

interface ShortcodeAchievementProps {
  achievementId: number;
}

export const ShortcodeAch: FC<ShortcodeAchievementProps> = ({ achievementId }) => {
  const persistedAchievements = useAtomValue(persistedAchievementsAtom);

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
        displayLockedStatus="unlocked"
        showPointsInTitle={true}
        variant="inline"
      />
    </span>
  );
};
