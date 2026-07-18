import { usePageNavigationTabs } from '@/common/hooks/usePageNavigationTabs';
import { usePageProps } from '@/common/hooks/usePageProps';

import type { TabConfig } from '../models';
import { currentTabAtom } from '../state/achievements.atoms';
import { useAnimatedTabIndicator } from './useAnimatedTabIndicator';

type AchievementTab = App.Platform.Enums.AchievementPageTab;

export function useAchievementShowTabs(tabConfigs: TabConfig[]) {
  const { initialTab } = usePageProps<App.Platform.Data.AchievementShowPageProps>();

  const { currentTab, setCurrentTab } = usePageNavigationTabs(currentTabAtom, initialTab);

  const tabValues = tabConfigs.map((c) => c.value);
  const initialIndex = tabValues.indexOf(currentTab);

  const { activeIndex, setActiveIndex, setHoveredIndex, ...animation } =
    useAnimatedTabIndicator(initialIndex);

  const handleValueChange = (value: string) => {
    const index = tabValues.indexOf(value as AchievementTab);
    if (index !== -1) {
      setActiveIndex(index);
    }

    setCurrentTab(value as AchievementTab);
  };

  return {
    currentTab,
    handleValueChange,
    activeIndex,
    setHoveredIndex,
    ...animation,
  };
}
