import { useSyncExternalStore } from 'react';

const noopSubscribe = () => () => {};

/**
 * Detects whether the component has hydrated on the client.
 * Returns false during SSR and true after hydration.
 *
 * Uses useSyncExternalStore to avoid the cascading render issue
 * that occurs when calling setState synchronously in useEffect.
 */
export function useIsHydrated(): boolean {
  return useSyncExternalStore(
    noopSubscribe,
    () => true, // client: always hydrated.
    () => false, // server: never hydrated.
  );
}
