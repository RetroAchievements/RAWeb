/**
 * This function is a safe wrapper for the native `requestIdleCallback()` function.
 * If `requestIdleCallback()` is available in the `window` object, it will be used.
 * Otherwise, the provided callback will be called immediately.
 *
 * At the time of writing, Safari has no roadmap to implement `requestIdleCallback()`.
 *
 * @see {@link https://developer.mozilla.org/en-US/docs/Web/API/Window/requestIdleCallback MDN Documentation for requestIdleCallback}
 *
 * @param {(...args: unknown[]) => unknown} callback - The function to be called when the event loop is idle.
 */
export const safeRequestIdleCallback = (callback: (...args: unknown[]) => unknown) => {
  if ('requestIdleCallback' in window) {
    window.requestIdleCallback(callback);
  } else {
    callback();
  }
};
