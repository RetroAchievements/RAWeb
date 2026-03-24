import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { AchievementContributePanel } from '../AchievementContributePanel';
import { AchievementEventInfo } from '../AchievementEventInfo';
import { AchievementGamePanel } from '../AchievementGamePanel';
import { AchievementMetaDetails } from '../AchievementMetaDetails';
import { ProximityAchievements } from '../ProximityAchievements';

export const AchievementShowSidebarRoot: FC = () => {
  const { isEventGame } = usePageProps<App.Platform.Data.AchievementShowPageProps>();

  return (
    <div data-testid="sidebar" className="flex flex-col gap-6">
      <AchievementContributePanel />

      <div className="hidden lg:block">
        <AchievementGamePanel />
      </div>

      {isEventGame ? (
        <div className="hidden lg:block">
          <AchievementEventInfo />
        </div>
      ) : (
        <AchievementMetaDetails />
      )}

      {/* TODO AchievementGuideReferences */}

      <ProximityAchievements />
    </div>
  );
};
