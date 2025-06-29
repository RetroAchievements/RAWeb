import { useCookie } from 'react-use';

const MAX_GAME_IDS = 10;

export function usePersistedGameIdsCookie(cookieName: string, gameId: number) {
  const [cookieValue, setCookieValue] = useCookie(cookieName);

  /**
   * Convert the current cookie value (a string) to a list of integer game IDs.
   */
  const parseGameIds = (): number[] => {
    if (!cookieValue) {
      return [];
    }

    return cookieValue
      .split(',')
      .map((id) => parseInt(id, 10))
      .filter((id) => !isNaN(id));
  };

  const isGameIdInCookie = (): boolean => {
    const gameIds = parseGameIds();

    return gameIds.includes(gameId);
  };

  const toggleGameId = (shouldInclude: boolean): void => {
    const currentGameIds = parseGameIds();
    let updatedGameIds: number[];

    if (shouldInclude) {
      // Add the game ID if it's not already present.
      if (!currentGameIds.includes(gameId)) {
        updatedGameIds = [...currentGameIds, gameId];

        // Keep only the most recent MAX_GAME_IDS (FIFO).
        if (updatedGameIds.length > MAX_GAME_IDS) {
          updatedGameIds = updatedGameIds.slice(-MAX_GAME_IDS);
        }
      } else {
        updatedGameIds = currentGameIds;
      }
    } else {
      // Remove the game ID from the list.
      updatedGameIds = currentGameIds.filter((id) => id !== gameId);
    }

    // Update the cookie with the new list.
    setCookieValue(updatedGameIds.join(','));
  };

  return {
    isGameIdInCookie,
    toggleGameId,
  };
}
