/**
 * Shows a tab and hides all other tabs with the same `tabType`.
 * Also adds an "active" class to the clicked tab.
 *
 * @param event
 * @param destinationTabName The ID of the tab element to show.
 * @param tabType The type of tab (used to match the corresponding tab content+button).
 */
export function handleLeaderboardTabClick(
  event: MouseEvent,
  destinationTabName: string,
  tabType: 'friendstab' | 'scores' | 'globaltab',
): void {
  const tabContent = document.querySelectorAll<HTMLElement>(`.tabcontent${tabType}`);
  const tabLinks = document.querySelectorAll<HTMLElement>(`.${tabType}`);

  // Hide all the currently-visible tab content and mark all
  // the tabs as being inactive.
  tabContent.forEach((tab) => {
    tab.style.display = 'none';
  });
  tabLinks.forEach((tabLink) => {
    tabLink.classList.remove('active');
  });

  // Now show the current tab and mark it as active.
  const destinationTabEl = document.getElementById(destinationTabName) as HTMLElement;
  destinationTabEl.style.display = 'block';

  if (event.currentTarget) {
    (event.currentTarget as HTMLElement).classList.add('active');
  }
}
