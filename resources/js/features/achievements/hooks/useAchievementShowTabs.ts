import { useCallback } from 'react';

import { useShowPageTabs } from '@/common/hooks/useShowPageTabs';

import type { AchievementShowTab } from '../models';
import { currentTabAtom } from '../state/achievements.atoms';
import { useAnimatedTabIndicator } from './useAnimatedTabIndicator';

const tabValues: AchievementShowTab[] = ['comments', 'unlocks', 'changelog'];

export function useAchievementShowTabs() {
  const { currentTab, setCurrentTab } = useShowPageTabs(currentTabAtom, 'comments');

  const initialIndex = tabValues.indexOf(currentTab as AchievementShowTab);

  const { activeIndex, setActiveIndex, setHoveredIndex, ...animation } =
    useAnimatedTabIndicator(initialIndex);

  const handleValueChange = useCallback(
    (value: string) => {
      const index = tabValues.indexOf(value as AchievementShowTab);
      if (index !== -1) {
        setActiveIndex(index);
      }

      setCurrentTab(value as AchievementShowTab);
    },
    [setActiveIndex, setCurrentTab],
  );

  return {
    currentTab,
    handleValueChange,
    activeIndex,
    setHoveredIndex,
    ...animation,
  };
}
