import { screen } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import { describe, it, expect } from 'vitest';

import { hideEarnedCheckboxComponent } from './hideEarnedCheckboxComponent';

function render() {
  (document as any).toggleUnlockedRows = hideEarnedCheckboxComponent().toggleUnlockedRows;

  document.body.innerHTML = /** @html */ `
    <div>
      <label class="flex items-center gap-x-1">
        <input 
          type="checkbox"
          onchange="toggleUnlockedRows()"
        >
          Hide unlocked achievements
        </input>
      </label>

      <ul>
        <li class="unlocked-row"></li>
        <li class="unlocked-row"></li>
        <li class=""></li>
      </ul>
    </div>
  `;
}

describe('Component: hideEarnedCheckbox', () => {
  it('is defined #sanity', () => {
    expect(hideEarnedCheckboxComponent).toBeDefined();
  });

  describe('Util: toggleUnlockedRows', () => {
    it('renders without crashing #sanity', () => {
      render();

      expect(screen.getByRole('checkbox', { name: /hide unlocked achievements/i })).toBeInTheDocument();
    });

    it('given the checkbox is checked, hides unlocked achievements', async () => {
      // ARRANGE
      render();

      // ACT
      await userEvent.click(screen.getByRole('checkbox', { name: /hide/i }));

      // ASSERT
      const allRows = screen.getAllByRole('listitem');
      const visibleRows = allRows.filter((row) => !row.classList.contains('hidden'));

      expect(visibleRows.length).toEqual(1);
    });

    it('given the checkbox is unchecked, makes unlocked achievements visible again', async () => {
      // ARRANGE
      render();

      // ACT
      await userEvent.click(screen.getByRole('checkbox', { name: /hide/i }));
      await userEvent.click(screen.getByRole('checkbox', { name: /hide/i }));

      // ASSERT
      const allRows = screen.getAllByRole('listitem');
      const visibleRows = allRows.filter((row) => !row.classList.contains('hidden'));

      expect(visibleRows.length).toEqual(3);
    });
  });
});
