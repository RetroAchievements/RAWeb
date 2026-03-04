import { useShowPageTabs } from '@/common/hooks/useShowPageTabs';

import type { TabConfig } from '../models';
import { currentTabAtom } from '../state/achievements.atoms';
import { useAnimatedTabIndicator } from './useAnimatedTabIndicator';

type AchievementTab = App.Platform.Enums.AchievementPageTab;

export function useAchievementShowTabs(tabConfigs: TabConfig[]) {
  const tabValues = tabConfigs.map((c) => c.value);

  const { currentTab, setCurrentTab } = useShowPageTabs(currentTabAtom, 'comments');

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
