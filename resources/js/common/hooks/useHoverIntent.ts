import { useRef } from 'react';

/**
 * Ensure the user is being intentful with their hover over a list of
 * items, similar to Inertia Link prefetching, so we don't overheat the
 * query cache.
 */
export function useHoverIntent(delayMs = 150) {
  const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const cancel = () => {
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
    }
  };

  const start = (callback: () => void) => {
    cancel();
    timeoutRef.current = setTimeout(callback, delayMs);
  };

  return { start, cancel };
}
