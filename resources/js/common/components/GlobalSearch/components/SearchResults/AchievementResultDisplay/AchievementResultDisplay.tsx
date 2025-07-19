import type { FC } from 'react';

import { GameTitle } from '@/common/components/GameTitle';

interface AchievementResultDisplayProps {
  achievement: App.Platform.Data.Achievement;
}

export const AchievementResultDisplay: FC<AchievementResultDisplayProps> = ({ achievement }) => {
  return (
    <div className="flex w-full items-center gap-3">
      <img src={achievement.badgeUnlockedUrl} alt={achievement.title} className="size-10 rounded" />

      <div className="flex flex-col gap-0.5">
        <div className="flex items-center gap-1.5 font-medium text-link">
          {achievement.title}

          <span className="text-xs text-text">
            {'('}
            {achievement.points}
            {')'}
          </span>

          <span className="TrueRatio text-xs light:text-neutral-400">
            {'('}
            {achievement.pointsWeighted}
            {')'}
          </span>
        </div>

        <div className="flex items-center gap-4 text-xs text-neutral-400 light:text-neutral-600">
          <div className="line-clamp-1">
            <GameTitle title={achievement.game!.title} />
          </div>
        </div>
      </div>
    </div>
  );
};
