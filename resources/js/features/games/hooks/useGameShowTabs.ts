import { router } from '@inertiajs/react';
import { useAtom } from 'jotai';

import type { GameShowTab } from '../models';
import { currentTabAtom } from '../state/games.atoms';

export function useGameShowTabs() {
  const [currentTab, internal_setCurrentTab] = useAtom(currentTabAtom);

  const setCurrentTab = (value: GameShowTab) => {
    internal_setCurrentTab(value);

    const searchParams = new URLSearchParams(window.location.search);
    if (value !== 'achievements') {
      searchParams.set('tab', value);
    } else {
      searchParams.delete('tab');
    }

    const queryString = searchParams.toString();
    const newUrl = queryString
      ? `${window.location.pathname}?${queryString}`
      : window.location.pathname;

    router.visit(newUrl, {
      replace: true,
      preserveState: true,
      preserveScroll: true,
      only: [], // Don't reload any data, just update the URL.
    });
  };

  return { currentTab, setCurrentTab };
}
