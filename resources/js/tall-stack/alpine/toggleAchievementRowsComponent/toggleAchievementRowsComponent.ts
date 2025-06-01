export function toggleAchievementRowsComponent(
  gameId?: number | null,
  initialHideUnlocked = false,
) {
  const COOKIE_NAME = 'hide_unlocked_achievements_games';
  /**
   * Don't store more than this count of game IDs.
   * We want to keep the cookie size small.
   */
  const MAX_GAME_IDS = 10;

  // Get the list of game IDs from cookie.
  function getHiddenGameIds(): number[] {
    const cookieValue = window.getCookie(COOKIE_NAME);
    if (!cookieValue) {
      return [];
    }

    try {
      const gameIds = cookieValue
        .split(',')
        .map((id) => parseInt(id, 10))
        .filter((id) => !isNaN(id));

      return gameIds;
    } catch {
      return [];
    }
  }

  /**
   * Save the list of game IDs to a the cookie.
   */
  function saveHiddenGameIds(gameIds: number[]): void {
    // Keep only the most recent MAX_GAME_IDS.
    const trimmedIds = gameIds.slice(-MAX_GAME_IDS);
    window.setCookie(COOKIE_NAME, trimmedIds.join(','));
  }

  /**
   * Check if the current game's unlocked achievements should be hidden.
   */
  function isGameHidden(): boolean {
    if (!gameId) {
      return false;
    }

    const hiddenGameIds = getHiddenGameIds();

    return hiddenGameIds.includes(gameId);
  }

  /**
   * Toggle the current game in the hidden game IDs list.
   */
  function toggleGameInHiddenList(shouldHide: boolean): void {
    if (!gameId) {
      return;
    }

    let hiddenGameIds = getHiddenGameIds();

    if (shouldHide) {
      // Add the game ID if it's not already present.
      if (!hiddenGameIds.includes(gameId)) {
        hiddenGameIds.push(gameId);
      }
    } else {
      // Remove the game ID from the list.
      hiddenGameIds = hiddenGameIds.filter((id) => id !== gameId);
    }

    saveHiddenGameIds(hiddenGameIds);
  }

  return {
    isUsingHideUnlockedAchievements: initialHideUnlocked || isGameHidden(),
    isUsingHideInactiveAchievements: false,
    isUsingOnlyShowMissables: false,

    init(): void {
      // Check if all achievements are unlocked.
      const allRows = document.querySelectorAll<HTMLLIElement>('#set-achievements-list > li');
      const unlockedRows = document.querySelectorAll<HTMLLIElement>(
        '#set-achievements-list > li.unlocked-row',
      );

      // If all achievements are unlocked, disable the hide unlocked filter.
      if (allRows.length > 0 && allRows.length === unlockedRows.length) {
        this.isUsingHideUnlockedAchievements = false;
        toggleGameInHiddenList(false);
      }

      // Respect whatever the initial state from the server is.
      if (this.isUsingHideUnlockedAchievements) {
        this.updateRowsVisibility();
      }
    },

    updateRowsVisibility(): void {
      const allRows = document.querySelectorAll<HTMLLIElement>('#set-achievements-list > li');

      for (const row of allRows) {
        let shouldBeHidden = false;

        if (this.isUsingHideUnlockedAchievements && row.classList.contains('unlocked-row')) {
          shouldBeHidden = true;
        }

        if (this.isUsingOnlyShowMissables && !row.classList.contains('missable-row')) {
          shouldBeHidden = true;
        }

        if (this.isUsingHideInactiveAchievements && !row.classList.contains('active-row')) {
          shouldBeHidden = true;
        }

        row.classList.toggle('hidden', shouldBeHidden);
      }
    },

    toggleUnlockedRows(): void {
      this.isUsingHideUnlockedAchievements = !this.isUsingHideUnlockedAchievements;
      toggleGameInHiddenList(this.isUsingHideUnlockedAchievements);
      this.updateRowsVisibility();
    },

    toggleNonMissableRows(): void {
      this.isUsingOnlyShowMissables = !this.isUsingOnlyShowMissables;
      this.updateRowsVisibility();
    },

    toggleInactiveRows(): void {
      this.isUsingHideInactiveAchievements = !this.isUsingHideInactiveAchievements;
      this.updateRowsVisibility();
    },
  };
}
