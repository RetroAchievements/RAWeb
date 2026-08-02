import type { Page } from '@inertiajs/core';
import { router } from '@inertiajs/react';

/**
 * Request the deferred props that a back/forward cache restore lost.
 *
 * The browser keeps a document in the back/forward cache when the user leaves
 * it. The document then resumes with the same client state it had before. The
 * browser cancels each request that was in progress at that moment.
 *
 * Inertia requests missing deferred props again only for same-document history
 * navigation. It does not do this for a back/forward cache restore. A prop lost
 * this way never arrives, and its UI stays in the loading state permanently.
 */
export function restoreDeferredPropsOnPageShow(event: PageTransitionEvent): void {
  if (!event.persisted) {
    return;
  }

  for (const only of getUnresolvedDeferredPropGroups(window.history.state?.page)) {
    // Keep the errors. This matches how Inertia requests deferred props.
    // A prop that arrives late must not remove the errors on the page.
    router.reload({ only, preserveErrors: true });
  }
}

/**
 * Find the value at a deferred prop path. A path can use dots. A path with
 * dots points to a value inside another prop.
 */
function getPropAtPath(props: Page['props'] | undefined, path: string): unknown {
  let current: unknown = props;

  for (const segment of path.split('.')) {
    if (typeof current !== 'object' || current === null) {
      return undefined;
    }

    current = (current as Record<string, unknown>)[segment];
  }

  return current;
}

/**
 * Collect the deferred props that the page declared but did not receive. Keep
 * the groups that the page declared. Each group gets its own request, so a
 * fast group does not wait for a slow group.
 */
function getUnresolvedDeferredPropGroups(page: unknown): string[][] {
  // An encrypted history entry is not readable. Do nothing with it.
  if (!page || typeof page !== 'object') {
    return [];
  }

  const { props, initialDeferredProps, deferredProps } = page as Page;

  return Object.values(initialDeferredProps ?? deferredProps ?? {})
    .map((paths) => paths.filter((path) => getPropAtPath(props, path) === undefined))
    .filter((paths) => paths.length > 0);
}
