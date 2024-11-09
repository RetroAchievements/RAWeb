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
      const timeoutId = setTimeout(() => {
        setIsButtonDisabled(false);
      }, delay);

      return () => clearTimeout(timeoutId);
    } else {
      setIsButtonDisabled(true);
    }
  }, [isPending, delay]);

  return isButtonDisabled;
};
