import type { FC } from 'react';

import { AchievementGamePanel } from '../AchievementGamePanel';
import { AchievementMetaDetails } from '../AchievementMetaDetails';
import { ProximityAchievements } from '../ProximityAchievements';

export const AchievementShowSidebarRoot: FC = () => {
  return (
    <div data-testid="sidebar" className="flex flex-col gap-6">
      <div className="hidden lg:block">
        <AchievementGamePanel />
      </div>

      <AchievementMetaDetails />

      {/* TODO guide references */}
      <p>{'AchievementGuideReferences'}</p>

      <ProximityAchievements />
    </div>
  );
};
