import type { ReactNode } from 'react';

import type { AchievementShowTab } from './achievement-show-tab.model';

export interface TabConfig {
  value: AchievementShowTab;
  label: ReactNode;
}
