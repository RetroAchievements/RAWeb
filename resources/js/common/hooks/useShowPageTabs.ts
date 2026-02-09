import { router } from '@inertiajs/react';
import type { PrimitiveAtom } from 'jotai';
import { useAtom } from 'jotai';
import { useEffect } from 'react';

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

export function useShowPageTabs<T extends string>(
  tabAtom: PrimitiveAtom<T>,
  defaultTab: NoInfer<T>,
) {
  const [currentTab, internal_setCurrentTab] = useAtom(tabAtom);

  // Keep the atom in sync with the URL on mount and browser back/forward.
  useEffect(() => {
    const syncFromUrl = () => {
      const urlParams = new URLSearchParams(window.location.search);
      const tabParam = urlParams.get('tab') as T | null;

      internal_setCurrentTab(tabParam ?? defaultTab);
    };

    syncFromUrl();

    window.addEventListener('popstate', syncFromUrl);

    return () => window.removeEventListener('popstate', syncFromUrl);
  }, [defaultTab, internal_setCurrentTab]);

  const setCurrentTab = (value: T, options: SetCurrentTabOptions = {}) => {
    const { shouldPushHistory = false } = options;

    internal_setCurrentTab(value);

    const url = new URL(window.location.href);

    if (value !== defaultTab) {
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
