import { router } from '@inertiajs/react';
import { useAtom } from 'jotai';
import { useEffect } from 'react';

import type { GameShowTab } from '../models';
import { currentTabAtom } from '../state/games.atoms';

interface SetCurrentTabOptions {
  /**
   * If truthy, changing the tab will push to the browser history.
   * This means when the user does a "back" navigation, they'll
   * navigate to the previous tab they were on.
   *
   * @default false
   */
  shouldPushHistory?: boolean;
}

export function useGameShowTabs() {
  const [currentTab, internal_setCurrentTab] = useAtom(currentTabAtom);

  /**
   * Sync the tab state atom with the browser URL on:
   *  - Mount.
   *  - All browser history changes.
   */
  useEffect(() => {
    const syncFromUrl = () => {
      const urlParams = new URLSearchParams(window.location.search);
      const tabParam = urlParams.get('tab') as GameShowTab | null;

      internal_setCurrentTab(tabParam ?? 'achievements');
    };

    // Sync on mount.
    syncFromUrl();

    // Sync on all browser history changes.
    window.addEventListener('popstate', syncFromUrl);

    return () => window.removeEventListener('popstate', syncFromUrl);
  }, [internal_setCurrentTab]);

  const setCurrentTab = (value: GameShowTab, options: SetCurrentTabOptions = {}) => {
    const { shouldPushHistory = false } = options;

    internal_setCurrentTab(value);

    const url = new URL(window.location.href);

    if (value !== 'achievements') {
      url.searchParams.set('tab', value);
    } else {
      url.searchParams.delete('tab');
    }

    if (shouldPushHistory) {
      router.visit(url.toString(), {
        preserveScroll: true,
        preserveState: true,
      });
    } else {
      router.replace({
        url: url.toString(),
        preserveScroll: true,
        preserveState: true,
      });
    }
  };

  return { currentTab, setCurrentTab };
}
