export const safeRequestIdleCallback = (callback: (...args: unknown[]) => unknown) => {
  if ('requestIdleCallback' in window) {
    window.requestIdleCallback(callback);
  } else {
    callback();
  }
};
