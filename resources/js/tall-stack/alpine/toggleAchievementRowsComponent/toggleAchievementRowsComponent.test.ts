import { screen } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import {
  // @prettier-ignore
  beforeEach,
  describe,
  expect,
  it,
} from 'vitest';

import { toggleAchievementRowsComponent } from './toggleAchievementRowsComponent';

function render() {
  (document as any).updateRowsVisibility = toggleAchievementRowsComponent().updateRowsVisibility;
  (document as any).toggleUnlockedRows = toggleAchievementRowsComponent().toggleUnlockedRows;
  (document as any).toggleNonMissableRows = toggleAchievementRowsComponent().toggleNonMissableRows;
  (document as any).toggleInactiveRows = toggleAchievementRowsComponent().toggleInactiveRows;

  document.body.innerHTML = /** @html */ `
    <div>
      <label class="flex items-center gap-x-1">
        <input 
          type="checkbox"
          name="toggleUnlocked"
          onchange="toggleUnlockedRows()"
        >
          Hide unlocked achievements
        </input>
      </label>

      <label class="flex items-center gap-x-1">
        <input 
          type="checkbox"
          name="toggleMissables"
          onchange="toggleNonMissableRows()"
        >
          Only show missables
        </input>
      </label>

      <label class="flex items-center gap-x-1">
        <input 
          type="checkbox"
          name="toggleInactive"
          onchange="toggleInactiveRows()"
        >
          Hide inactive achievements
        </input>
      </label>

      <ul id="set-achievements-list">
        <li class="unlocked-row"></li>
        <li class="active-row unlocked-row"></li>
        <li class="unlocked-row"></li>
        <li class=""></li>
        <li class="unlocked-row"></li>
        <li class=""></li>
        <li class="missable-row"></li>
        <li class="missable-row unlocked-row"></li>
        <li class="missable-row active-row"></li>
        <li class=""></li>
      </ul>
    </div>
  `;
}

// NOTE: Vitest is persisting JSDOM between each test case.
describe('Component: toggleAchievementRowsComponent', () => {
  beforeEach(() => {
    render();
  });

  it('is defined #sanity', () => {
    expect(toggleAchievementRowsComponent).toBeDefined();
  });

  describe('Util: toggleUnlockedRows', () => {
    it('renders without crashing #sanity', () => {
      expect(
        screen.getByRole('checkbox', {
          name: /hide unlocked achievements/i,
        }),
      ).toBeInTheDocument();
    });

    it('given the checkbox is checked, hides unlocked achievements', async () => {
      // ACT
      await userEvent.click(screen.getByRole('checkbox', { name: /unlocked/i }));

      // ASSERT
      const allRows = screen.getAllByRole('listitem');
      const visibleRows = allRows.filter((row) => !row.classList.contains('hidden'));

      expect(visibleRows.length).toEqual(5);
    });

    it('given the checkbox is unchecked, makes unlocked achievements visible again', async () => {
      // ACT
      await userEvent.click(screen.getByRole('checkbox', { name: /unlocked/i }));

      // ASSERT
      const allRows = screen.getAllByRole('listitem');
      const visibleRows = allRows.filter((row) => !row.classList.contains('hidden'));

      expect(visibleRows.length).toEqual(10);
    });

    it('given the checkbox is checked, hides non-missable achievements', async () => {
      // ACT
      await userEvent.click(screen.getByRole('checkbox', { name: /missable/i }));

      // ASSERT
      const allRows = screen.getAllByRole('listitem');
      const visibleRows = allRows.filter((row) => !row.classList.contains('hidden'));

      expect(visibleRows.length).toEqual(3);
    });

    it('given the checkbox is unchecked, makes non-missable achievements visible again', async () => {
      // ACT
      await userEvent.click(screen.getByRole('checkbox', { name: /missable/i }));

      // ASSERT
      const allRows = screen.getAllByRole('listitem');
      const visibleRows = allRows.filter((row) => !row.classList.contains('hidden'));

      expect(visibleRows.length).toEqual(10);
    });

    it('given the checkbox is checked, hides inactive achievements', async () => {
      // ACT
      await userEvent.click(screen.getByRole('checkbox', { name: /inactive/i }));

      // ASSERT
      const allRows = screen.getAllByRole('listitem');
      const visibleRows = allRows.filter((row) => !row.classList.contains('hidden'));

      expect(visibleRows.length).toEqual(2);
    });

    it('given the checkbox is unchecked, makes inactive achievements visible again', async () => {
      // ACT
      await userEvent.click(screen.getByRole('checkbox', { name: /inactive/i }));

      // ASSERT
      const allRows = screen.getAllByRole('listitem');
      const visibleRows = allRows.filter((row) => !row.classList.contains('hidden'));

      expect(visibleRows.length).toEqual(10);
    });
  });
});
