import { useAtom } from 'jotai';

import type { GameShowTab } from '../models';
import { currentTabAtom } from '../state/games.atoms';

export function useGameShowTabs() {
  const [currentTab, internal_setCurrentTab] = useAtom(currentTabAtom);

  const setCurrentTab = (value: string) => {
    const safeValue = value as GameShowTab;
    internal_setCurrentTab(safeValue);

    const searchParams = new URLSearchParams(window.location.search);
    if (safeValue !== 'achievements') {
      searchParams.set('tab', value);
    } else {
      searchParams.delete('tab');
    }

    const newUrl = `${window.location.pathname}?${searchParams.toString()}`;

    window.history.replaceState(null, '', newUrl);
  };

  return { currentTab, setCurrentTab };
}
