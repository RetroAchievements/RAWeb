import { screen } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import { describe, it, expect } from 'vitest';

import { toggleUserCompletedSetsVisibility } from './toggleUserCompletedSetsVisibility';

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

    expect(
      screen.getByRole('checkbox', { name: /hide user completed sets/i })
    ).toBeInTheDocument();
  });

  it('given the checkbox is checked, hides completed rows', async () => {
    // ARRANGE
    render();

    // ACT
    await userEvent.click(screen.getByRole('checkbox', { name: /hide/i }));

    // ASSERT
    const allRows = screen.getAllByRole('row');
    const visibleRows = allRows.filter((row) => row.style.display !== 'none');

    expect(visibleRows.length).toEqual(1);
  });

  it('given the checkbox is unchecked, causes the completed rows to reappear', async () => {
    // ARRANGE
    render();

    // ACT
    await userEvent.click(screen.getByRole('checkbox', { name: /hide/i }));
    await userEvent.click(screen.getByRole('checkbox', { name: /hide/i }));

    // ASSERT
    const allRows = screen.getAllByRole('row');
    const visibleRows = allRows.filter((row) => row.style.display !== 'none');

    expect(visibleRows.length).toEqual(3);
  });
});
