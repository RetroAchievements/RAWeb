export function toggleAchievementRowsComponent() {
  return {
    originalButtonLabel: '',

    disableHideUnlockedAchievementsFilter(): void {
      if (window.isHideUnlockedAchievementsActive) {
        this.toggleUnlockedRows();
      }

      const filterCheckbox = document.querySelector<HTMLInputElement>(
        'div[x-data="toggleAchievementRowsComponent()"] input[type="checkbox"]',
      );
      if (filterCheckbox) {
        filterCheckbox.checked = false;
      }
    },

    toggleUnlockedRows(): void {
      // If the user already has the missables filter enabled, disable it.
      if (window.isShowOnlyMissablesActive) {
        this.toggleMissablesFilter();
      }

      const allFoundRows = document.querySelectorAll<HTMLLIElement>('li.unlocked-row');

      allFoundRows.forEach((rowEl) => {
        if (rowEl.classList.contains('hidden')) {
          rowEl.classList.remove('hidden');
        } else {
          rowEl.classList.add('hidden');
        }
      });

      window.isHideUnlockedAchievementsActive = !window.isHideUnlockedAchievementsActive;
    },

    toggleMissablesFilter(): void {
      // If the user already has the "Hide unlocked achievements" filter enabled, disable it.
      if (window.isHideUnlockedAchievementsActive) {
        this.disableHideUnlockedAchievementsFilter();
      }

      const allRows = document.querySelectorAll<HTMLLIElement>('#set-achievements-list > li');

      allRows.forEach((row) => {
        if (!row.classList.contains('missable-row')) {
          row.classList.toggle('hidden');
        }
      });

      window.isShowOnlyMissablesActive = !window.isShowOnlyMissablesActive;
      this.updateMissableButtonText();
    },

    updateMissableButtonText(): void {
      const textContentEl = document.querySelector('#missable-toggle-button-content');
      if (textContentEl) {
        console.log('update button text');
        textContentEl.innerHTML = window.isShowOnlyMissablesActive
          ? 'Reset view to all achievements'
          : this.originalButtonLabel;
      }
    },

    init() {
      const textContentEl = document.querySelector('#missable-toggle-button-content');
      if (textContentEl) {
        this.originalButtonLabel = textContentEl.innerHTML;
      }
    },
  };
}
