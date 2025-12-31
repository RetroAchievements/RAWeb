import { setCookie } from './cookie';

export const cookieName = 'prefers_hidden_user_completed_sets';

/**
 * Toggles between the "all games" and "incomplete games only" lists
 * based on the state of the "Hide completed games" checkbox.
 *
 * When checked, shows the incomplete games list.
 * When unchecked, shows the full list including completed games.
 */
export function toggleUserCompletedSetsVisibility() {
  const checkboxEl = document.getElementById(
    'hide-user-completed-sets-checkbox',
  ) as HTMLInputElement | null;

  const allGamesEl = document.getElementById('completion-progress-all');
  const incompleteGamesEl = document.getElementById('completion-progress-incomplete');

  if (checkboxEl && allGamesEl && incompleteGamesEl) {
    const isChecked = checkboxEl.checked;
    setCookie(cookieName, isChecked ? 'true' : 'false');

    allGamesEl.classList.toggle('hidden', isChecked);
    incompleteGamesEl.classList.toggle('hidden', !isChecked);
  }
}
