import type { FC } from 'react';

import { AchievementOfTheWeek } from './AchievementOfTheWeek';
import { GlobalStatistics } from './GlobalStatistics';
import { RecentGameAwards } from './RecentGameAwards';
import { TopLinks } from './TopLinks';

export const HomeSidebar: FC = () => {
  return (
    <div className="flex flex-col gap-8">
      <TopLinks />
      <AchievementOfTheWeek />
      <GlobalStatistics />
      <RecentGameAwards />
    </div>
  );
};
