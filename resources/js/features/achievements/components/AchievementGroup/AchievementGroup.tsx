import type { FC } from 'react';

import { UnlockableAchievementAvatar } from '@/features/achievements/components/UnlockableAchievementAvatar/UnlockableAchievementAvatar';

interface AchievementGroupProps {
  group: App.Community.Data.AchievementGroup;
  showGame?: boolean;
}

export const AchievementGroup: FC<AchievementGroupProps> = ({ group, showGame = false }) => {
  return (
    <div className="mb-0 mt-4">
      <h4>{group.header}</h4>
      {group.achievements.map((achievement) => (
        <UnlockableAchievementAvatar
          key={`ach-${achievement.id}-avatar`}
          achievement={achievement}
          showGame={showGame}
        />
      ))}
    </div>
  );
};
