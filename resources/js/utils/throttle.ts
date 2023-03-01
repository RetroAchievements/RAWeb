/**
 * Creates a throttled version of a function that can be called at most
 * once per `waitMs` milliseconds.
 *
 * @param fn - The function to throttle.
 * @param waitMs - The number of milliseconds to wait before allowing the function to be called again.
 * @returns A throttled version of the original function.
 */
export const throttle = <T extends unknown[]>(
  fn: (...args: T) => unknown,
  waitMs: number
) => {
  let isThrottled = false;

  return (...args: T) => {
    if (!isThrottled) {
      isThrottled = true;

      setTimeout(() => {
        fn(...args);
        isThrottled = false;
      }, waitMs);
    }
  };
};
