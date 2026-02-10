import type { FC } from 'react';

import { AchievementGamePanel } from '../AchievementGamePanel';
import { AchievementMetaDetails } from '../AchievementMetaDetails';

export const AchievementShowSidebarRoot: FC = () => {
  return (
    <div data-testid="sidebar" className="flex flex-col gap-6">
      <AchievementGamePanel />
      <AchievementMetaDetails />

      {/* TODO guide references */}
      <p>{'AchievementGuideReferences'}</p>

      {/* TODO more from this set */}
      <p>{'ProximityAchievements'}</p>
    </div>
  );
};
