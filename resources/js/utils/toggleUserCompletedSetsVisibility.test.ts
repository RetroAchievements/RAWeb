import { screen } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import * as CookieModule from '@/utils/cookie';

import { cookieName, toggleUserCompletedSetsVisibility } from './toggleUserCompletedSetsVisibility';

function render() {
  (document as any).toggleUserCompletedSetsVisibility = toggleUserCompletedSetsVisibility;

  document.body.innerHTML = /** @html */ `
    <div>
      <label>
        <input type="checkbox" id="hide-user-completed-sets-checkbox" onchange="toggleUserCompletedSetsVisibility()" />
        Hide user completed sets
      </label>
      <table id="usercompletedgamescomponent">
        <tr class="completion-progress-completed-row"></tr>
        <tr class="completion-progress-completed-row"></tr>
        <tr class=""></tr>
      </table>
    </div>
  `;
}

describe('Util: toggleUserCompletedSetsVisibility', () => {
  it('is defined #sanity', () => {
    expect(toggleUserCompletedSetsVisibility).toBeDefined();
  });

  it('renders without crashing #sanity', () => {
    render();

    expect(screen.getByRole('checkbox', { name: /hide user completed sets/i })).toBeInTheDocument();
  });

  it('given the checkbox is checked, hides completed rows', async () => {
    // ARRANGE
    render();

    // ACT
    await userEvent.click(screen.getByRole('checkbox', { name: /hide/i }));

    // ASSERT
    const allRows = screen.getAllByRole('row');
    const visibleRows = allRows.filter((row) => !row.classList.contains('hidden'));

    expect(visibleRows.length).toEqual(1);
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

  it('given the checkbox is unchecked, causes the completed rows to reappear', async () => {
    // ARRANGE
    render();

    // ACT
    await userEvent.click(screen.getByRole('checkbox', { name: /hide/i }));
    await userEvent.click(screen.getByRole('checkbox', { name: /hide/i }));

    // ASSERT
    const allRows = screen.getAllByRole('row');
    const visibleRows = allRows.filter((row) => !row.classList.contains('hidden'));

    expect(visibleRows.length).toEqual(3);
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
