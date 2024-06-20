import { setSavedHiddenRowsVisiblity } from './setSavedHiddenRowsVisibility';
import { showSavedHiddenRowsCookie } from './showSavedHiddenRowsCookie';

/**
 * Toggles the visibility of saved hidden rows in a table based on
 * the user's preference. The preference is then stored in a cookie
 * so it can be persisted across sessions and page refreshes.
 *
 * @param {MouseEvent} event The click event from toggling the checkbox.
 */
export function handleShowSavedHiddenRowsChange(event: MouseEvent) {
  const isChecked = (event.target as HTMLInputElement).checked;
  showSavedHiddenRowsCookie.set(isChecked);

  setSavedHiddenRowsVisiblity();
}
