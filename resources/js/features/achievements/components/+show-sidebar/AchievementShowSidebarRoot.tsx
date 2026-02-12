import type { FC } from 'react';

import { AchievementGamePanel } from '../AchievementGamePanel';

export const AchievementShowSidebarRoot: FC = () => {
  return (
    <div data-testid="sidebar" className="flex flex-col gap-6">
      <div className="hidden md:block">
        <AchievementGamePanel />
      </div>

      {/* TODO achievement meta details (created by, created date, last modified, etc) */}
      <p>{'AchievementMetaDetails'}</p>

      {/* TODO guide references */}
      <p>{'AchievementGuideReferences'}</p>

      {/* TODO more from this set */}
      <p>{'ProximityAchievements'}</p>
    </div>
  );
};
