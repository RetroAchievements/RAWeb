import { router } from '@inertiajs/react';
import { useEffect } from 'react';

// TODO delete this after the site UI is fully converted to Inertia

/**
 * This hook preemptively loads deferred leaderboards data when
 * navigating back via browser back button from non-React pages.
 *
 * This hook solves an issue where:
 * 1. User loads a game page, and Inertia defers leaderboards loading.
 * 2. User navigates to a Blade page, which is a full page load.
 * 3. User hits their back button, which causes the page to be restored from bfcache.
 * 4. Inertia's deferred fetch doesn't re-run.
 * 5. Leaderboards data remains undefined.
 * 6. Leaderboards now fail to appear for the user.
 *
 * By detecting back/forward navigation and preloading when needed, the data
 * is ready before the user clicks the leaderboards tab.
 */
export function usePreloadDeferredLeaderboards(
  numLeaderboards: number,
  allLeaderboards: unknown,
): void {
  useEffect(() => {
    // Check if page was loaded via back/forward button.
    const navigation = performance.getEntriesByType?.('navigation')?.[0] as
      | PerformanceNavigationTiming
      | undefined;
    const isBackForward = navigation?.type === 'back_forward';

    /**
     * Only preload if:
     * 1. The user navigated via back/forward.
     * 2. There are enough leaderboards to warrant preloading (> 5).
     * 3. The data hasn't been loaded yet.
     */
    if (isBackForward && numLeaderboards > 5 && allLeaderboards === undefined) {
      router.reload({ only: ['allLeaderboards'] });
    }
  }, [numLeaderboards, allLeaderboards]);
}
