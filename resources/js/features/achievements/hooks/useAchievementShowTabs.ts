import { useShowPageTabs } from '@/common/hooks/useShowPageTabs';

import { currentTabAtom } from '../state/achievements.atoms';

export function useAchievementShowTabs() {
  return useShowPageTabs(currentTabAtom, 'comments');
}
