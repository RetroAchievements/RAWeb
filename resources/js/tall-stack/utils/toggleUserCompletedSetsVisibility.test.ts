/* eslint-disable testing-library/no-node-access */

import { screen } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import * as CookieModule from './cookie';
import { cookieName, toggleUserCompletedSetsVisibility } from './toggleUserCompletedSetsVisibility';

function render() {
  document.body.innerHTML = /** @html */ `
    <div>
      <label>
        <input type="checkbox" id="hide-user-completed-sets-checkbox" />
        Hide user completed sets
      </label>
      <div id="usercompletedgamescomponent">
        <div id="completion-progress-all">
          <table>
            <tr><td>Game 1 (completed)</td></tr>
            <tr><td>Game 2 (completed)</td></tr>
            <tr><td>Game 3 (incomplete)</td></tr>
          </table>
        </div>
        <div id="completion-progress-incomplete" class="hidden">
          <table>
            <tr><td>Game 3 (incomplete)</td></tr>
          </table>
        </div>
      </div>
    </div>
  `;

  const checkbox = document.getElementById('hide-user-completed-sets-checkbox');
  checkbox?.addEventListener('change', toggleUserCompletedSetsVisibility);
}

describe('Util: toggleUserCompletedSetsVisibility', () => {
  it('is defined', () => {
    expect(toggleUserCompletedSetsVisibility).toBeDefined();
  });

  it('renders without crashing', () => {
    render();

    expect(screen.getByRole('checkbox', { name: /hide user completed sets/i })).toBeInTheDocument();
  });

  it('given the checkbox is checked, shows the incomplete games list and hides the all games list', async () => {
    // ARRANGE
    render();

    // ACT
    await userEvent.click(screen.getByRole('checkbox', { name: /hide/i }));

    // ASSERT
    const allGamesEl = document.getElementById('completion-progress-all');
    const incompleteGamesEl = document.getElementById('completion-progress-incomplete');

    expect(allGamesEl?.classList.contains('hidden')).toEqual(true);
    expect(incompleteGamesEl?.classList.contains('hidden')).toEqual(false);
  });

  it('given the checkbox is checked, sets a cookie value to "true"', async () => {
    // ARRANGE
    const setCookieSpy = vi.spyOn(CookieModule, 'setCookie');

    render();

    // ACT
    await userEvent.click(screen.getByRole('checkbox', { name: /hide/i }));

    // ASSERT
    expect(setCookieSpy).toHaveBeenCalledWith(cookieName, 'true');
  });

  it('given the checkbox is unchecked, shows the all games list and hides the incomplete games list', async () => {
    // ARRANGE
    render();

    // ACT
    await userEvent.click(screen.getByRole('checkbox', { name: /hide/i }));
    await userEvent.click(screen.getByRole('checkbox', { name: /hide/i }));

    // ASSERT
    const allGamesEl = document.getElementById('completion-progress-all');
    const incompleteGamesEl = document.getElementById('completion-progress-incomplete');

    expect(allGamesEl?.classList.contains('hidden')).toEqual(false);
    expect(incompleteGamesEl?.classList.contains('hidden')).toEqual(true);
  });

  it('given the checkbox is unchecked, sets a cookie value to "false"', async () => {
    // ARRANGE
    const setCookieSpy = vi.spyOn(CookieModule, 'setCookie');

    render();

    // ACT
    await userEvent.click(screen.getByRole('checkbox', { name: /hide/i }));
    await userEvent.click(screen.getByRole('checkbox', { name: /hide/i }));

    // ASSERT
    expect(setCookieSpy).toHaveBeenNthCalledWith(2, cookieName, 'false');
  });
});
