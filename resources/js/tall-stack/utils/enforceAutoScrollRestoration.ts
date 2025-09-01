/**
 * Enforces browser's native scroll restoration behavior.
 *
 * Inertia.js v2 automatically sets window.history.scrollRestoration to 'manual'
 * to support encrypted history. @see https://github.com/inertiajs/inertia/pull/2051
 *
 * This is undesirable for RAWeb and breaks scroll restoration throughout the app.
 *
 * This util overrides any attempts to set scrollRestoration to 'manual',
 * ensuring it stays on 'auto' for both Inertia and traditional page navigation.
 */
export function enforceAutoScrollRestoration(): void {
  // Bail unless we're running in a compatible environment.
  if (typeof window === 'undefined' || !window.history?.scrollRestoration) {
    return;
  }

  const setToAuto = () => {
    if (window.history.scrollRestoration !== 'auto') {
      window.history.scrollRestoration = 'auto';
    }
  };

  // Run immediately on mount ...
  setToAuto();

  // ... and also stack some runs at the end of a few event loop cycles.
  // This naively tries to catch any (unlikely) changes made by stray dynamic modules.
  setTimeout(setToAuto, 0);
  setTimeout(setToAuto, 1000);

  // Override the scrollRestoration property to prevent runtime changes to 'manual'.
  try {
    const originalDescriptor = Object.getOwnPropertyDescriptor(
      History.prototype,
      'scrollRestoration',
    );

    if (originalDescriptor) {
      Object.defineProperty(History.prototype, 'scrollRestoration', {
        get() {
          return originalDescriptor.get?.call(this);
        },
        set(value: ScrollRestoration) {
          if (value === 'manual') {
            return originalDescriptor.set?.call(this, 'auto');
          }

          return originalDescriptor.set?.call(this, value);
        },
        configurable: true,
        enumerable: true,
      });
    }
  } catch {
    // We don't care if it errors. Just silently fail.
  }

  // Listen for various navigation events to ensure scroll restoration stays on.
  const events = ['pageshow', 'popstate', 'load', 'DOMContentLoaded'] as const;
  for (const eventName of events) {
    window.addEventListener(eventName, setToAuto);
  }

  // Also listen for Inertia navigation events.
  if (typeof document !== 'undefined') {
    const inertiaEvents = ['inertia:navigate', 'inertia:start', 'inertia:finish'] as const;
    for (const eventName of inertiaEvents) {
      document.addEventListener(eventName, setToAuto);
    }
  }
}
