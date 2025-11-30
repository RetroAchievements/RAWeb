import { useEffect, useState } from 'react';

/**
 * Throttle how fast the user can toggle the game onto their backlog.
 * If the user performs the action too quickly, the database gets
 * upset about unique constraint collisions.
 */
export const useDelayedButtonDisable = (isPending: boolean, delay = 1000) => {
  const [isButtonDisabled, setIsButtonDisabled] = useState(false);

  useEffect(() => {
    if (!isPending) {
      // When the operation completes, keep the button disabled for the delay period.
      const timeoutId = setTimeout(() => {
        setIsButtonDisabled(false);
      }, delay);

      return () => clearTimeout(timeoutId);
    }

    // When the operation starts, disable the button immediately.
    const timeoutId = setTimeout(() => {
      setIsButtonDisabled(true);
    }, 0);

    return () => clearTimeout(timeoutId);
  }, [isPending, delay]);

  return isButtonDisabled;
};
