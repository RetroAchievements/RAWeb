import { router } from '@inertiajs/react';
import { useEffect } from 'react';

// TODO delete this after the site UI is fully converted to Inertia

/**
 * Reloads specific deferred props when a page is restored via the
 * browser back/forward button from a non-Inertia (Blade) page.
 *
 * When a user navigates from an Inertia page to a Blade page (full
 * page load) and then presses back, the browser restores the page
 * from bfcache. Inertia's deferred fetch doesn't re-run in this
 * scenario, leaving deferred props stuck as `undefined`.
 *
 * @param deferredProps - Record of prop names to their current values.
 *                        Any prop that is `undefined` during a back/forward
 *                        navigation will be refetched from the server.
 */
export function useReloadDeferredOnBackForward(deferredProps: Record<string, unknown>): void {
  const propValues = Object.values(deferredProps);

  useEffect(() => {
    const navigation = performance.getEntriesByType?.('navigation')?.[0] as
      | PerformanceNavigationTiming
      | undefined;

    if (navigation?.type !== 'back_forward') {
      return;
    }

    const unresolvedKeys = Object.entries(deferredProps)
      .filter(([_, value]) => value === undefined)
      .map(([key]) => key);

    if (unresolvedKeys.length > 0) {
      router.reload({ only: unresolvedKeys });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps -- intentionally keyed on prop values, not the object reference.
  }, propValues);
}
