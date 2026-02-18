import { useShowPageTabs } from '@/common/hooks/useShowPageTabs';

import { currentTabAtom } from '../state/games.atoms';

export function useGameShowTabs() {
  return useShowPageTabs(currentTabAtom, 'achievements');
}
