import type { FC } from 'react';

import { AchievementContributePanel } from '../AchievementContributePanel';
import { AchievementGamePanel } from '../AchievementGamePanel';
import { AchievementMetaDetails } from '../AchievementMetaDetails';
import { ProximityAchievements } from '../ProximityAchievements';

export const AchievementShowSidebarRoot: FC = () => {
  return (
    <div data-testid="sidebar" className="flex flex-col gap-6">
      <AchievementContributePanel />

      <div className="hidden lg:block">
        <AchievementGamePanel />
      </div>

      <AchievementMetaDetails />

      {/* TODO AchievementGuideReferences */}

      <ProximityAchievements />
    </div>
  );
};
