export function toggleAchievementRowsComponent() {
  return {
    isUsingHideUnlockedAchievements: false,
    isUsingOnlyShowMissables: false,

    updateRowsVisibility(): void {
      const allRows = document.querySelectorAll<HTMLLIElement>('#set-achievements-list > li');

      allRows.forEach((row) => {
        let shouldBeHidden = false;

        if (this.isUsingHideUnlockedAchievements && row.classList.contains('unlocked-row')) {
          shouldBeHidden = true;
        }

        if (this.isUsingOnlyShowMissables && !row.classList.contains('missable-row')) {
          shouldBeHidden = true;
        }

        row.classList.toggle('hidden', shouldBeHidden);
      });
    },

    toggleUnlockedRows(): void {
      this.isUsingHideUnlockedAchievements = !this.isUsingHideUnlockedAchievements;
      this.updateRowsVisibility();
    },

    toggleNonMissableRows(): void {
      this.isUsingOnlyShowMissables = !this.isUsingOnlyShowMissables;
      this.updateRowsVisibility();
    },
  };
}
