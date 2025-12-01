import { router } from '@inertiajs/react';
import { useAtom } from 'jotai';

import type { GameShowTab } from '../models';
import { currentTabAtom } from '../state/games.atoms';

export function useGameShowTabs() {
  const [currentTab, internal_setCurrentTab] = useAtom(currentTabAtom);

  const setCurrentTab = (value: GameShowTab) => {
    internal_setCurrentTab(value);

    const url = new URL(window.location.href);

    if (value !== 'achievements') {
      url.searchParams.set('tab', value);
    } else {
      url.searchParams.delete('tab');
    }

    router.replace({
      url: url.toString(),
      preserveScroll: true,
      preserveState: true,
    });
  };

  return { currentTab, setCurrentTab };
}
